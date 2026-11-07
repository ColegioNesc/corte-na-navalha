<?php
/**
 * API: Testar Conexão com Mercado Pago
 * Apenas Dono
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mercadopago.php';

if (!usuarioLogado() || getUsuarioLogado()['perfil'] !== 'dono') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$token = trim($_POST['token'] ?? '');
if (empty($token)) {
    $empresa = getEmpresaConfig();
    $token = trim($empresa['mp_access_token'] ?? '');
}

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'Informe o Access Token para realizar o teste.']);
    exit;
}

$resultado = MercadoPagoService::testarCredenciais($token);
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;

