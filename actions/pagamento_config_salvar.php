<?php
/**
 * Salvar Configurações de Pagamentos e Gateway Mercado Pago
 * Apenas Dono (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/pagamentos.php');
    exit;
}

$metodoAtivo        = trim($_POST['metodo_pagamento_ativo'] ?? 'pix_fixo');
$chavePixPrincipal  = trim($_POST['chave_pix_principal'] ?? '');
$tipoChavePix       = trim($_POST['tipo_chave_pix'] ?? 'E-mail');
$mpPublicKey        = trim($_POST['mp_public_key'] ?? '');
$mpAccessToken      = trim($_POST['mp_access_token'] ?? '');
$mpWebhookSecret    = trim($_POST['mp_webhook_secret'] ?? '');
$mpSandbox          = isset($_POST['mp_sandbox']) ? 1 : 0;
$comissaoAutomatica = isset($_POST['comissao_automatica']) ? 1 : 0;

if (!in_array($metodoAtivo, ['pix_fixo', 'mercadopago'])) {
    $metodoAtivo = 'pix_fixo';
}

try {
    $pdo = getDBConnection();
    $sql = "
        UPDATE configuracao_empresa 
        SET metodo_pagamento_ativo = :metodo_ativo,
            chave_pix_principal    = :chave_pix,
            tipo_chave_pix         = :tipo_chave,
            mp_public_key          = :mp_public,
            mp_access_token        = :mp_token,
            mp_webhook_secret      = :mp_secret,
            mp_sandbox             = :mp_sandbox,
            comissao_automatica    = :comissao_auto
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':metodo_ativo'  => $metodoAtivo,
        ':chave_pix'     => $chavePixPrincipal,
        ':tipo_chave'    => $tipoChavePix,
        ':mp_public'     => $mpPublicKey,
        ':mp_token'      => $mpAccessToken,
        ':mp_secret'     => $mpWebhookSecret,
        ':mp_sandbox'    => $mpSandbox,
        ':comissao_auto' => $comissaoAutomatica
    ]);

    setMensagemToast('success', 'Configurações Salvas!', 'As definições financeiras e do gateway de pagamento foram atualizadas.');
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar as configurações: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/pagamentos.php');
exit;

