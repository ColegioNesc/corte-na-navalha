<?php
/**
 * Endpoint de Autenticação (JSON API)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($metodo === 'GET') {
    if ($action === 'check') {
        $user = getCurrentUser($pdo);
        jsonResponse(true, 'Status de autenticação', [
            'isLoggedIn' => isLoggedIn(),
            'user' => $user
        ]);
    }
    jsonResponse(false, 'Ação não permitida via GET.', [], 405);
}

// Recebe JSON ou POST form-data
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $inputData['action'] ?? $action;

try {
    switch ($action) {
        case 'login':
            $email = $inputData['email'] ?? '';
            $senha = $inputData['senha'] ?? '';
            $res = loginUser($pdo, $email, $senha);
            if ($res['success']) {
                jsonResponse(true, $res['message'], ['user' => $res['user']]);
            } else {
                jsonResponse(false, $res['message'], [], 400);
            }
            break;

        case 'register':
            $nome = $inputData['nome'] ?? '';
            $email = $inputData['email'] ?? '';
            $senha = $inputData['senha'] ?? '';
            $raio = (int)($inputData['raio_padrao'] ?? 300);
            $tipo = in_array($inputData['tipo_alarme_padrao'] ?? '', ['som', 'vibracao', 'ambos']) ? $inputData['tipo_alarme_padrao'] : 'ambos';
            $som = in_array($inputData['som_alarme'] ?? '', ['sirene', 'beep', 'toque']) ? $inputData['som_alarme'] : 'sirene';

            $res = registerUser($pdo, $nome, $email, $senha, $raio, $tipo, $som);
            if ($res['success']) {
                jsonResponse(true, $res['message'], ['user' => $res['user']], 201);
            } else {
                jsonResponse(false, $res['message'], [], 400);
            }
            break;

        case 'logout':
            logoutUser();
            jsonResponse(true, 'Sessão encerrada com sucesso.');
            break;

        default:
            jsonResponse(false, 'Ação de autenticação inválida.', [], 400);
    }
} catch (Exception $e) {
    error_log("Erro em api/auth.php: " . $e->getMessage());
    jsonResponse(false, 'Ocorreu um erro interno no servidor.', [], 500);
}
