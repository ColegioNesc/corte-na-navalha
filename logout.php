<?php
/**
 * Logout do Usuário
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
header('Location: ' . BASE_URL . '/index.php');
exit;
