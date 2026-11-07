<?php
/**
 * Salvar Configurações da Barbearia (Dono)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$usuario = getUsuarioLogado();
if (!$usuario || $usuario['perfil'] !== 'dono') {
    http_response_code(403);
    setMensagemToast('danger', 'Acesso Negado', 'Apenas o proprietário da barbearia tem autorização para alterar estes dados.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

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
$taxaCancelamento = (float)($_POST['taxa_cancelamento_percentual'] ?? 20.00);
if ($taxaCancelamento > 20.0) {
    $taxaCancelamento = 20.0;
}
if ($taxaCancelamento < 0.0) {
    $taxaCancelamento = 0.0;
}
$horasMinimas  = (int)($_POST['horas_minimas_cancelamento'] ?? 2);
$textoPolitica = trim($_POST['politica_cancelamento_texto'] ?? '');

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
            tipo_chave_pix = :tipo_chave,
            taxa_cancelamento_percentual = :taxa_cancelamento,
            horas_minimas_cancelamento = :horas_minimas,
            politica_cancelamento_texto = :texto_politica
        WHERE id = 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'               => $nomeBarbearia,
        ':slogan'             => $slogan,
        ':cnpj'               => $cnpj,
        ':telefone'           => $telefone,
        ':email'              => $email,
        ':endereco'           => $endereco,
        ':chave_pix'          => $chavePix,
        ':tipo_chave'         => $tipoChavePix,
        ':taxa_cancelamento'  => $taxaCancelamento,
        ':horas_minimas'      => $horasMinimas,
        ':texto_politica'     => $textoPolitica
    ]);

    setMensagemToast('success', 'Dados da Empresa Salvos!', 'As informações da barbearia, chave PIX e política de cancelamento foram atualizadas.');
    header('Location: ' . BASE_URL . '/admin/empresa.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar as configurações: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/admin/empresa.php');
    exit;
}
