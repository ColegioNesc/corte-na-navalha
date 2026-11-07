<?php
/**
 * Excluir Funcionário
 * Apenas Dono
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setMensagemToast('warning', 'Atenção', 'Colaborador inválido.');
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $usuario = getUsuarioLogado();

    if ($usuario['id'] == $id) {
        setMensagemToast('danger', 'Ação Não Permitida', 'Você não pode excluir sua própria conta de proprietário.');
        header('Location: ' . BASE_URL . '/admin/funcionarios.php');
        exit;
    }

    // Verificar se possui agendamentos vinculados
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE barbeiro_id = :id");
    $stmtCheck->execute([':id' => $id]);
    $totalAgendamentos = (int)$stmtCheck->fetchColumn();

    if ($totalAgendamentos > 0) {
        // Se possui histórico de agendamentos, inativamos para manter a integridade fiscal/histórica
        $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = :id")->execute([':id' => $id]);
        setMensagemToast('warning', 'Colaborador Inativado', "O colaborador possui {$totalAgendamentos} agendamento(s) no histórico e não pode ser deletado fisicamente. Ele foi inativado e teve o acesso revogado.");
    } else {
        // Se não possui agendamentos, pode deletar com segurança
        $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = :id AND perfil IN ('barbeiro', 'recepcao')");
        $stmtDel->execute([':id' => $id]);
        setMensagemToast('success', 'Excluído com Sucesso', 'Colaborador removido do sistema.');
    }
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Excluir', 'Não foi possível excluir o colaborador: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/funcionarios.php');
exit;

