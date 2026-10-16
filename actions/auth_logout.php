<?php
/**
 * Logout do Sistema
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

iniciarSessao();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

iniciarSessao();
setMensagemToast('info', 'Sessão Encerrada', 'Você saiu com segurança do sistema. Até logo!');
header('Location: ' . BASE_URL . '/index.php');
exit;
