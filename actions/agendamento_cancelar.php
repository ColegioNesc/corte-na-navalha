<?php
/**
 * Ação de Cancelamento de Agendamento com Política do CDC
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

exigirLogin(['cliente', 'barbeiro', 'recepcao', 'dono', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$motivo = trim($_POST['motivo_cancelamento'] ?? 'Cancelado pelo usuário');
$redirect = $_POST['redirect'] ?? '';
$usuario = getUsuarioLogado();

if (empty($agendamentoId)) {
    setMensagemToast('danger', 'Erro', 'Agendamento inválido.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Busca o agendamento e as regras da barbearia
    $stmt = $pdo->prepare("
        SELECT a.*, 
               b.taxa_cancelamento_percentual, 
               b.horas_minimas_cancelamento,
               c.nome as cliente_nome,
               c.email as cliente_email,
               f.nome as barbeiro_nome
        FROM agendamentos a 
        CROSS JOIN configuracao_empresa b
        LEFT JOIN usuarios c ON a.cliente_id = c.id
        LEFT JOIN usuarios f ON a.barbeiro_id = f.id
        WHERE a.id = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => $agendamentoId]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        setMensagemToast('danger', 'Não encontrado', 'Agendamento não localizado.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    if ($agendamento['status_atendimento'] === 'cancelado') {
        setMensagemToast('info', 'Aviso', 'Este agendamento já se encontra cancelado.');
        header('Location: ' . (!empty($redirect) ? $redirect : BASE_URL . '/index.php'));
        exit;
    }

    // Validação de permissões de quem está cancelando
    $perfil = $usuario['perfil'];
    if ($perfil === 'cliente' && $agendamento['cliente_id'] != $usuario['id']) {
        setMensagemToast('danger', 'Acesso Negado', 'Você só pode cancelar seus próprios agendamentos.');
        header('Location: ' . BASE_URL . '/cliente/dashboard.php');
        exit;
    }
    if ($perfil === 'barbeiro' && $agendamento['barbeiro_id'] != $usuario['id']) {
        setMensagemToast('danger', 'Acesso Negado', 'Você não é o profissional deste agendamento.');
        header('Location: ' . BASE_URL . '/barbeiro/dashboard.php');
        exit;
    }

    // Cálculo do tempo restante
    $dataHoraStr = $agendamento['data_agendamento'] . ' ' . $agendamento['horario_inicio'];
    $agendamentoTs = strtotime($dataHoraStr);
    $agoraTs = time();
    $horasRestantes = ($agendamentoTs - $agoraTs) / 3600.0;

    $horasMinimas = (float)($agendamento['horas_minimas_cancelamento'] ?? 2.0);
    $taxaPadraoPercent = (float)($agendamento['taxa_cancelamento_percentual'] ?? 20.0);
    $valorTotal = (float)$agendamento['valor_total'];

    $taxaAplicadaPercent = 0.0;
    $taxaIsenta = 0;

    // Regras de taxa:
    // 0. Se o agendamento foi originado no BALCÃO, NÃO GERA TAXA DE CANCELAMENTO
    if ($agendamento['origem'] === 'balcao' || !empty($agendamento['cliente_balcao'])) {
        $taxaAplicadaPercent = 0.0;
        $taxaIsenta = 1;
    }
    // 1. Se quem cancela é o barbeiro/salao, o cliente NUNCA paga taxa (reembolso total)
    elseif ($perfil === 'barbeiro') {
        $taxaAplicadaPercent = 0.0;
        $taxaIsenta = 1;
    }
    // 2. Cancelamento pelo Dono ou Administrador (Possui autorização para personalizar ou isentar)
    elseif ($perfil === 'dono' || $perfil === 'admin') {
        $isentar = !empty($_POST['isentar_taxa']) ? 1 : 0;
        if ($isentar) {
            $taxaAplicadaPercent = 0.0;
            $taxaIsenta = 1;
        } elseif (isset($_POST['taxa_personalizada']) && $_POST['taxa_personalizada'] !== '') {
            // Apenas o Dono tem autorização para definir uma taxa personalizada
            $taxaAplicadaPercent = min(20.0, max(0.0, (float)$_POST['taxa_personalizada']));
            $taxaIsenta = ($taxaAplicadaPercent == 0) ? 1 : 0;
        } else {
            $taxaAplicadaPercent = $taxaPadraoPercent;
            $taxaIsenta = 0;
        }
    }
    // 3. Cancelamento pela Recepção
    // SEGURANÇA: Outros usuários NUNCA podem alterar a porcentagem da taxa.
    // Mesmo que tentem injetar 'taxa_personalizada' via console/DOM no navegador, o backend rejeita e ignora.
    elseif ($perfil === 'recepcao') {
        $isentar = !empty($_POST['isentar_taxa']) ? 1 : 0;
        if ($isentar) {
            $taxaAplicadaPercent = 0.0;
            $taxaIsenta = 1;
        } else {
            // Aplica impreterivelmente a taxa oficial cadastrada pelo Dono
            $taxaAplicadaPercent = $taxaPadraoPercent;
            $taxaIsenta = 0;
        }
    }
    // 4. Cancelamento pelo próprio Cliente
    else {
        if ($horasRestantes >= $horasMinimas) {
            // Cancelamento com antecedência adequada -> Sem retenção
            $taxaAplicadaPercent = 0.0;
            $taxaIsenta = 0;
        } else {
            // Cancelamento tardio -> Aplica estritamente a taxa oficial do salão
            $taxaAplicadaPercent = $taxaPadraoPercent;
            $taxaIsenta = 0;
        }
    }

    $valorTaxa = round($valorTotal * ($taxaAplicadaPercent / 100.0), 2);
    $valorReembolsado = round($valorTotal - $valorTaxa, 2);

    // Se o agendamento não havia sido pago ainda, nada a reembolsar financeiramente
    $jaEstavaPago = ($agendamento['status_pagamento'] === 'pago');
    if (!$jaEstavaPago) {
        $valorReembolsado = 0.0;
    }

    // Atualiza agendamento
    $stmtUpdate = $pdo->prepare("
        UPDATE agendamentos 
        SET status_atendimento = 'cancelado',
            cancelado_em = NOW(),
            taxa_cancelamento = :taxa,
            valor_reembolsado = :reembolso,
            taxa_isenta = :isenta,
            motivo_cancelamento = :motivo
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':taxa'      => $valorTaxa,
        ':reembolso' => $valorReembolsado,
        ':isenta'    => $taxaIsenta,
        ':motivo'    => $motivo,
        ':id'        => $agendamentoId
    ]);

    // Se estava pago, registra a transação contábil de estorno/cancelamento
    if ($jaEstavaPago) {
        $codTrx = 'EST-' . strtoupper(bin2hex(random_bytes(4)));
        $stmtTrx = $pdo->prepare("
            INSERT INTO transacoes (
                codigo_transacao, agendamento_id, cliente_id, funcionario_id, operador_id,
                tipo, metodo, valor_total, taxa_gateway, valor_recebido, troco, valor_liquido,
                status, detalhes
            ) VALUES (
                :cod, :agendamento_id, :cliente_id, :barbeiro_id, :operador_id,
                'estorno', 'estorno_pix', :reembolso, :taxa, 0.0, 0.0, :valor_liquido,
                'concluida', :detalhes
            )
        ");
        $stmtTrx->execute([
            ':cod'            => $codTrx,
            ':agendamento_id' => $agendamentoId,
            ':cliente_id'     => $agendamento['cliente_id'],
            ':barbeiro_id'    => $agendamento['barbeiro_id'],
            ':operador_id'    => $usuario['id'],
            ':reembolso'      => $valorReembolsado,
            ':taxa'           => $valorTaxa,
            ':valor_liquido'  => $valorReembolsado,
            ':detalhes'       => 'Cancelamento: ' . $motivo . ($taxaIsenta ? ' (Taxa Isenta)' : " (Taxa de {$taxaAplicadaPercent}%)")
        ]);
    }

    // Envia e-mail de cancelamento para o cliente
    if (!empty($agendamento['cliente_email'])) {
        $mailer = new CustomMailer();
        $corpoEmail = "Olá <strong>" . htmlspecialchars($agendamento['cliente_nome']) . "</strong>,<br><br>"
            . "Confirmamos o cancelamento do seu agendamento <strong>#" . htmlspecialchars($agendamento['codigo_reserva']) . "</strong> "
            . "agendado para o dia " . date('d/m/Y', strtotime($agendamento['data_agendamento'])) . " às " . htmlspecialchars($agendamento['horario_inicio']) . ".<br><br>";

        if ($jaEstavaPago) {
            if ($valorTaxa > 0) {
                $corpoEmail .= "Conforme a política de cancelamento informada no agendamento e o Código de Defesa do Consumidor, como o cancelamento ocorreu com menos de {$horasMinimas}h de antecedência, foi retida uma taxa de retenção operacional de <strong>R$ " . number_format($valorTaxa, 2, ',', '.') . "</strong> ({$taxaAplicadaPercent}%). O valor a ser estornado é de <strong>R$ " . number_format($valorReembolsado, 2, ',', '.') . "</strong>.<br><br>";
            } else {
                $corpoEmail .= "O cancelamento foi realizado sem retenção de taxas. O valor integral de <strong>R$ " . number_format($valorReembolsado, 2, ',', '.') . "</strong> será estornado.<br><br>";
            }
        }
        $corpoEmail .= "Motivo registrado: " . htmlspecialchars($motivo) . "<br><br>Atenciosamente,<br>Equipe Barbearia Corte na Navalha";

        $mailer->enviarEmail($agendamento['cliente_email'], $agendamento['cliente_nome'], "Cancelamento de Agendamento #" . $agendamento['codigo_reserva'], $corpoEmail);
    }

    // Feedback ao usuário
    $msgSucesso = 'Agendamento cancelado com sucesso.';
    if ($valorTaxa > 0) {
        $msgSucesso .= ' Taxa de retenção aplicada: R$ ' . number_format($valorTaxa, 2, ',', '.') . ' (' . $taxaAplicadaPercent . '%). Reembolso: R$ ' . number_format($valorReembolsado, 2, ',', '.') . '.';
    } elseif ($jaEstavaPago) {
        $msgSucesso .= ' Reembolso integral aprovado: R$ ' . number_format($valorReembolsado, 2, ',', '.') . '.';
    }
    setMensagemToast('warning', 'Cancelamento Efetuado', $msgSucesso);

    if (!empty($redirect)) {
        header('Location: ' . $redirect);
    } else {
        header('Location: ' . getHomeUrl());
    }
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Cancelar', 'Falha no processamento: ' . $e->getMessage());
    header('Location: ' . (!empty($redirect) ? $redirect : BASE_URL . '/index.php'));
    exit;
}
