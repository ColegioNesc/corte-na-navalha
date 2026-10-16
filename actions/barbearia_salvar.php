<?php
/**
 * Salvar Configurações da Barbearia (Dono)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/empresa.php');
    exit;
}

$nomeBarbearia = trim($_POST['nome_barbearia'] ?? 'Corte na Navalha Barbearia');
$slogan        = trim($_POST['slogan'] ?? '');
$cnpj          = trim($_POST['cnpj'] ?? '');
$telefone      = trim($_POST['telefone'] ?? '');
$email         = trim($_POST['email'] ?? '');
$endereco      = trim($_POST['endereco'] ?? '');
$chavePix      = trim($_POST['chave_pix_principal'] ?? '');
$tipoChavePix  = trim($_POST['tipo_chave_pix'] ?? 'E-mail');

try {
    $pdo = getDBConnection();

    $sql = "
        UPDATE configuracao_empresa 
        SET nome_barbearia = :nome,
            slogan = :slogan,
            cnpj = :cnpj,
            telefone = :telefone,
            email = :email,
            endereco = :endereco,
            chave_pix_principal = :chave_pix,
            tipo_chave_pix = :tipo_chave
        WHERE id = 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'      => $nomeBarbearia,
        ':slogan'    => $slogan,
        ':cnpj'      => $cnpj,
        ':telefone'  => $telefone,
        ':email'     => $email,
        ':endereco'  => $endereco,
        ':chave_pix' => $chavePix,
        ':tipo_chave'=> $tipoChavePix
    ]);

    setMensagemToast('success', 'Dados da Empresa Salvos!', 'As informações da barbearia e a chave PIX foram atualizadas com sucesso.');
    header('Location: ' . BASE_URL . '/admin/empresa.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar as configurações: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/admin/empresa.php');
    exit;
}
