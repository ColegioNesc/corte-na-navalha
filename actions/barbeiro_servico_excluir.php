<?php
/**
 * Excluir Procedimento do Catálogo do Barbeiro
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$usuario = getUsuarioLogado();

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM barbeiro_servicos WHERE id = :id AND barbeiro_id = :barbeiro_id");
    $stmt->execute([':id' => $id, ':barbeiro_id' => $usuario['id']]);
    setMensagemToast('success', 'Removido', 'Procedimento removido do seu catálogo.');
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro', 'Não foi possível remover: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
exit;

