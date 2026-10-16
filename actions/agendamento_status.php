<?php
/**
 * Atualização de Status do Atendimento
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro', 'recepcao', 'dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$novoStatus    = trim($_POST['status'] ?? '');
$redirect      = $_POST['redirect'] ?? '';
$usuario       = getUsuarioLogado();

$statusValidos = ['agendado', 'em_atendimento', 'concluido', 'cancelado'];
if (!in_array($novoStatus, $statusValidos) || empty($agendamentoId)) {
    setMensagemToast('warning', 'Ação Inválida', 'Status ou agendamento inválido.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Se for barbeiro, só pode alterar agendamentos dele próprio
    if ($usuario['perfil'] === 'barbeiro') {
        $stmt = $pdo->prepare("SELECT id FROM agendamentos WHERE id = :id AND barbeiro_id = :barbeiro_id LIMIT 1");
        $stmt->execute([':id' => $agendamentoId, ':barbeiro_id' => $usuario['id']]);
        if (!$stmt->fetch()) {
            setMensagemToast('danger', 'Acesso Negado', 'Este agendamento não está sob sua responsabilidade.');
            header('Location: ' . BASE_URL . '/barbeiro/dashboard.php');
            exit;
        }
    }

    $stmtUpdate = $pdo->prepare("UPDATE agendamentos SET status_atendimento = :status WHERE id = :id");
    $stmtUpdate->execute([':status' => $novoStatus, ':id' => $agendamentoId]);

    $statusNomes = [
        'agendado'       => 'Agendado',
        'em_atendimento' => 'Em Atendimento na Cadeira',
        'concluido'      => 'Concluído com Sucesso',
        'cancelado'      => 'Cancelado'
    ];

    setMensagemToast('success', 'Status Atualizado!', 'O atendimento foi alterado para: ' . ($statusNomes[$novoStatus] ?? $novoStatus));

    if (!empty($redirect)) {
        header('Location: ' . $redirect);
    } elseif ($usuario['perfil'] === 'barbeiro') {
        header('Location: ' . BASE_URL . '/barbeiro/dashboard.php');
    } elseif ($usuario['perfil'] === 'recepcao') {
        header('Location: ' . BASE_URL . '/recepcao/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    }
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Atualizar', 'Falha ao atualizar status: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
