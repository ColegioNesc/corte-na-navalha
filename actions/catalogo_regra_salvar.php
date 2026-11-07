<?php
/**
 * Salvar Regra de Catálogo de Serviços (Global vs Individual)
 * Apenas Dono
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/servicos.php');
    exit;
}

$tipoCatalogo = trim($_POST['tipo_catalogo'] ?? 'global');
if (!in_array($tipoCatalogo, ['global', 'individual'])) {
    $tipoCatalogo = 'global';
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE configuracao_empresa SET tipo_catalogo = :tipo LIMIT 1");
    $stmt->execute([':tipo' => $tipoCatalogo]);

    $nomeRegra = ($tipoCatalogo === 'global') 
        ? 'Tabela Geral Unificada' 
        : 'Catálogos Individuais por Barbeiro';
        
    setMensagemToast('success', 'Regra de Catálogo Atualizada', 'A regra vigente foi alterada para: ' . $nomeRegra);
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível atualizar a regra: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/servicos.php');
exit;

