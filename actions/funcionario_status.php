<?php
/**
 * Alternar Status (Ativar/Inativar) de Funcionário
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
$novoStatus = (int)($_POST['ativo'] ?? 1);

if ($id <= 0) {
    setMensagemToast('warning', 'Atenção', 'Colaborador inválido.');
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Não permitir inativar a si mesmo (dono logado)
    $usuario = getUsuarioLogado();
    if ($usuario['id'] == $id) {
        setMensagemToast('danger', 'Ação Não Permitida', 'Você não pode inativar sua própria conta de proprietário.');
        header('Location: ' . BASE_URL . '/admin/funcionarios.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id AND perfil IN ('barbeiro', 'recepcao')");
    $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);

    $msg = ($novoStatus === 1) ? 'Colaborador ativado com sucesso.' : 'Colaborador inativado com sucesso. Ele não poderá acessar o sistema.';
    setMensagemToast('success', 'Status Atualizado', $msg);
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro', 'Falha ao atualizar status: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/funcionarios.php');
exit;

