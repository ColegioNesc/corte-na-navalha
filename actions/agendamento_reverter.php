<?php
/**
 * Reversão de Cancelamento de Agendamento (Exclusivo do Dono)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$redirect = $_POST['redirect'] ?? (BASE_URL . '/admin/dashboard.php');

if (empty($agendamentoId)) {
    setMensagemToast('danger', 'Erro', 'Agendamento inválido.');
    header('Location: ' . $redirect);
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, codigo_reserva, status_atendimento FROM agendamentos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $agendamentoId]);
    $ag = $stmt->fetch();

    if (!$ag) {
        $pdo->rollBack();
        setMensagemToast('danger', 'Não Encontrado', 'Agendamento não localizado.');
        header('Location: ' . $redirect);
        exit;
    }

    if ($ag['status_atendimento'] !== 'cancelado') {
        $pdo->rollBack();
        setMensagemToast('info', 'Aviso', 'Este agendamento não está cancelado.');
        header('Location: ' . $redirect);
        exit;
    }

    // Reverte o agendamento para 'agendado' e limpa metadados de cancelamento
    $stmtUp = $pdo->prepare("
        UPDATE agendamentos
        SET status_atendimento = 'agendado',
            cancelado_em = NULL,
            taxa_cancelamento = 0.00,
            valor_reembolsado = 0.00,
            taxa_isenta = 0,
            motivo_cancelamento = NULL
        WHERE id = :id
    ");
    $stmtUp->execute([':id' => $agendamentoId]);

    // Anula eventual transação de estorno gerada no cancelamento
    $stmtEst = $pdo->prepare("
        UPDATE transacoes
        SET status = 'cancelada',
            detalhes = CONCAT(COALESCE(detalhes, ''), ' [Cancelamento revertido pelo Dono]')
        WHERE agendamento_id = :id AND tipo = 'estorno' AND status = 'concluida'
    ");
    $stmtEst->execute([':id' => $agendamentoId]);

    $pdo->commit();

    setMensagemToast('success', 'Cancelamento Revertido!', 'O agendamento #' . htmlspecialchars($ag['codigo_reserva']) . ' foi restaurado para o status Agendado.');
    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setMensagemToast('danger', 'Erro ao Reverter', 'Falha no processamento: ' . $e->getMessage());
    header('Location: ' . $redirect);
    exit;
}
