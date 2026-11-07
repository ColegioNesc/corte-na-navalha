<?php
/**
 * Processamento de Caixa - Recebimento Presencial com Troco
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['recepcao', 'barbeiro', 'dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/recepcao/dashboard.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$metodo        = trim($_POST['metodo_caixa'] ?? 'dinheiro');
$valorRecebido = (float)($_POST['valor_recebido'] ?? 0);
$redirect      = trim($_POST['redirect'] ?? '');
$operador      = getUsuarioLogado();

if ($agendamentoId <= 0) {
    setMensagemToast('warning', 'Dados Inválidos', 'Identificador de agendamento ausente.');
    header('Location: ' . ($redirect ?: BASE_URL . '/recepcao/dashboard.php'));
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // 1. Buscar dados do agendamento
    $stmt = $pdo->prepare("
        SELECT a.*, c.nome as cliente_nome, c.email as cliente_email, b.nome as barbeiro_nome,
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
    $stmt->execute([':id' => $agendamentoId]);
    $ag = $stmt->fetch();

    if (!$ag) {
        throw new Exception('Agendamento não localizado.');
    }

    $valorTotal = (float)$ag['valor_total'];
    $troco = 0.00;

    if ($metodo === 'dinheiro') {
        if ($valorRecebido < $valorTotal) {
            throw new Exception("O valor entregue em dinheiro (R$ " . number_format($valorRecebido, 2, ',', '.') . ") é menor que o total a pagar (R$ " . number_format($valorTotal, 2, ',', '.') . ").");
        }
        $troco = round($valorRecebido - $valorTotal, 2);
    } else {
        $valorRecebido = $valorTotal;
        $troco = 0.00;
    }

    // 2. Gerar código de transação
    $codigoTransacao = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // 3. Registrar na tabela transacoes (Registro contábil de comprovante)
    $stmtTrx = $pdo->prepare("
        INSERT INTO transacoes (
            codigo_transacao, agendamento_id, tipo, metodo, valor_total,
            valor_recebido, troco, cliente_id, funcionario_id, operador_id,
            status, detalhes
        ) VALUES (
            :codigo, :agendamento_id, 'recebimento', :metodo, :valor_total,
            :valor_recebido, :troco, :cliente_id, :funcionario_id, :operador_id,
            'concluida', :detalhes
        )
    ");
    
    $detalhesTxt = "Recebido no caixa por {$operador['nome']}. ";
    if ($metodo === 'dinheiro') {
        $detalhesTxt .= "Dinheiro entregue: R$ " . number_format($valorRecebido, 2, ',', '.') . ". Troco devolvido: R$ " . number_format($troco, 2, ',', '.') . ".";
    } else {
        $detalhesTxt .= "Método: " . strtoupper($metodo) . ".";
    }

    $stmtTrx->execute([
        ':codigo'         => $codigoTransacao,
        ':agendamento_id' => $ag['id'],
        ':metodo'         => ($metodo === 'dinheiro') ? 'dinheiro' : 'pix_fixo',
        ':valor_total'    => $valorTotal,
        ':valor_recebido' => $valorRecebido,
        ':troco'          => $troco,
        ':cliente_id'     => $ag['cliente_id'],
        ':funcionario_id' => $ag['barbeiro_id'],
        ':operador_id'    => $operador['id'],
        ':detalhes'       => $detalhesTxt
    ]);

    // 4. Atualizar o agendamento para pago
    $stmtUp = $pdo->prepare("
        UPDATE agendamentos 
        SET status_pagamento = 'pago', 
            pix_pago_em = NOW(), 
            recebido_por_id = :operador_id
        WHERE id = :id
    ");
    $stmtUp->execute([
        ':operador_id' => $operador['id'],
        ':id'          => $ag['id']
    ]);

    $pdo->commit();

    // 5. Enviar e-mail de comprovante se ativado
    try {
        require_once __DIR__ . '/../includes/mailer.php';
        enviarEmailComprovante($ag, $valorRecebido, $troco, $metodo, $codigoTransacao);
    } catch (Throwable $mailEx) {
        // Envio de e-mail é assíncrono/tolerante a falhas
    }

    $msgSucesso = ($metodo === 'dinheiro' && $troco > 0)
        ? "Pagamento de R$ " . number_format($valorTotal, 2, ',', '.') . " registrado com sucesso! Devolva o troco de R$ " . number_format($troco, 2, ',', '.') . " ao cliente."
        : "Pagamento de R$ " . number_format($valorTotal, 2, ',', '.') . " registrado com sucesso no caixa!";

    setMensagemToast('success', 'Recebimento Confirmado', $msgSucesso);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setMensagemToast('danger', 'Erro no Caixa', $e->getMessage());
}

header('Location: ' . ($redirect ?: BASE_URL . '/recepcao/dashboard.php'));
exit;

