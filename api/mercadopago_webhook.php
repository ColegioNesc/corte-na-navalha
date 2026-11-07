<?php
/**
 * Webhook Oficial Mercado Pago - Baixa Automática de Pagamentos
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/mercadopago.php';
require_once __DIR__ . '/../includes/mailer.php';

$rawBody = file_get_contents('php://input');
$bodyData = json_decode($rawBody, true) ?: [];

// Identificar o ID do pagamento no webhook
$paymentId = null;
if (isset($bodyData['data']['id'])) {
    $paymentId = $bodyData['data']['id'];
} elseif (isset($bodyData['id']) && ($bodyData['type'] ?? '') === 'payment') {
    $paymentId = $bodyData['id'];
} elseif (isset($_GET['data_id'])) {
    $paymentId = $_GET['data_id'];
} elseif (isset($_GET['id'])) {
    $paymentId = $_GET['id'];
}

if (!$paymentId) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'Notificação sem ID de pagamento.']);
    exit;
}

try {
    $pdo = getDBConnection();
    $empresa = getEmpresaConfig();
    $accessToken = trim($empresa['mp_access_token'] ?? '');

    if (empty($accessToken)) {
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => 'Gateway sem token configurado.']);
        exit;
    }

    // Consultar o pagamento na API do Mercado Pago
    $paymentInfo = MercadoPagoService::consultarPagamento($paymentId, $accessToken);
    if (!$paymentInfo || !isset($paymentInfo['status'])) {
        http_response_code(200);
        echo json_encode(['status' => 'not_found']);
        exit;
    }

    $statusMp = $paymentInfo['status'];
    $externalRef = (int)($paymentInfo['external_reference'] ?? 0);

    if ($statusMp === 'approved' && $externalRef > 0) {
        $stmtAg = $pdo->prepare("
            SELECT a.*, c.nome as cliente_nome, c.email as cliente_email, b.nome as barbeiro_nome,
                   b.chave_pix as barbeiro_chave_pix, b.tipo_chave_pix as barbeiro_tipo_pix,
                   b.comissao_percentual as barbeiro_comissao,
                   GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
            FROM agendamentos a
            JOIN usuarios c ON a.cliente_id = c.id
            JOIN usuarios b ON a.barbeiro_id = b.id
            LEFT JOIN agendamento_itens ai ON a.id = ai.agendamento_id
            LEFT JOIN servicos s ON ai.servico_id = s.id
            WHERE a.id = :id
            GROUP BY a.id
            LIMIT 1
        ");
        $stmtAg->execute([':id' => $externalRef]);
        $ag = $stmtAg->fetch();

        if ($ag && $ag['status_pagamento'] !== 'pago') {
            $pdo->beginTransaction();

            // 1. Atualizar agendamento para pago
            $up = $pdo->prepare("
                UPDATE agendamentos 
                SET status_pagamento = 'pago', 
                    pix_pago_em = NOW(), 
                    pix_txid = :txid
                WHERE id = :id
            ");
            $up->execute([
                ':txid' => 'MP-' . $paymentId,
                ':id'   => $ag['id']
            ]);

            // 2. Registrar transação contábil
            $codigoTrx = 'TRX-MP-' . date('Ymd') . '-' . substr($paymentId, -6);
            $stmtTrx = $pdo->prepare("
                INSERT INTO transacoes (
                    codigo_transacao, agendamento_id, tipo, metodo, valor_total,
                    valor_recebido, troco, cliente_id, funcionario_id, status, detalhes
                ) VALUES (
                    :codigo, :ag_id, 'recebimento', 'mercadopago_pix', :valor,
                    :valor, 0.00, :cli_id, :barb_id, 'concluida', :detalhes
                )
            ");
            $stmtTrx->execute([
                ':codigo'   => $codigoTrx,
                ':ag_id'    => $ag['id'],
                ':valor'    => $ag['valor_total'],
                ':cli_id'   => $ag['cliente_id'],
                ':barb_id'  => $ag['barbeiro_id'],
                ':detalhes' => "Baixa automática via Webhook Mercado Pago (Payment ID: {$paymentId})"
            ]);

            // 3. Repasse de comissão automático se configurado pelo dono (mods.md)
            if (!empty($empresa['comissao_automatica'])) {
                $percComissao = (float)($ag['barbeiro_comissao'] ?? 50.0);
                $valorComissao = round(((float)$ag['valor_total'] * $percComissao) / 100, 2);

                if ($valorComissao > 0) {
                    $stmtComissao = $pdo->prepare("
                        INSERT INTO pagamentos_funcionarios (
                            funcionario_id, dono_id, valor, chave_pix_utilizada,
                            tipo_chave, comprovante_transacao, referencia_periodo, observacoes
                        ) VALUES (
                            :func_id, 1, :valor, :chave_pix,
                            :tipo_chave, :comprovante, 'Repasse Automático Gateway',
                            :obs
                        )
                    ");
                    $stmtComissao->execute([
                        ':func_id'     => $ag['barbeiro_id'],
                        ':valor'       => $valorComissao,
                        ':chave_pix'   => $ag['barbeiro_chave_pix'] ?: 'Não informada',
                        ':tipo_chave'  => $ag['barbeiro_tipo_pix'] ?: 'Gateway',
                        ':comprovante' => 'COM-' . $paymentId,
                        ':obs'         => "Baixa automática de comissão ({$percComissao}%) originada pelo Mercado Pago."
                    ]);
                }
            }

            $pdo->commit();

            // 4. Enviar e-mail de confirmação ao cliente
            try {
                enviarEmailComprovante($ag, (float)$ag['valor_total'], 0.00, 'mercadopago_pix', $codigoTrx);
            } catch (Throwable $mailEx) {}

            http_response_code(200);
            echo json_encode(['status' => 'approved_and_processed', 'agendamento_id' => $ag['id']]);
            exit;
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'received', 'mp_status' => $statusMp]);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

