<?php
/**
 * Confirmação de Pagamento de Agendamento (PIX / Recepção)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente', 'recepcao', 'barbeiro', 'dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$redirect = $_POST['redirect'] ?? '';
$usuario = getUsuarioLogado();

if (empty($agendamentoId)) {
    setMensagemToast('warning', 'Identificador Inválido', 'Agendamento não informado.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Buscar agendamento
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $agendamentoId]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        setMensagemToast('danger', 'Não Encontrado', 'Agendamento inexistente.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    // Atualizar status para pago
    $stmtUpdate = $pdo->prepare("
        UPDATE agendamentos 
        SET status_pagamento = 'pago',
            pix_pago_em = NOW(),
            recebido_por_id = :recebido_por
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':recebido_por' => ($usuario['perfil'] !== 'cliente') ? $usuario['id'] : null,
        ':id'           => $agendamentoId
    ]);

    setMensagemToast('success', 'Pagamento Confirmado!', 'O pagamento de R$ ' . number_format($agendamento['valor_total'], 2, ',', '.') . ' foi registrado com sucesso.');

    if (!empty($redirect)) {
        header('Location: ' . $redirect);
    } elseif ($usuario['perfil'] === 'recepcao') {
        header('Location: ' . BASE_URL . '/recepcao/dashboard.php');
    } elseif ($usuario['perfil'] === 'dono') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/cliente/comprovante.php?codigo=' . urlencode($agendamento['codigo_reserva']));
    }
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro no Pagamento', 'Não foi possível processar a baixa do pagamento: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
