<?php
/**
 * Conexão com o Banco de Dados MySQL via PDO
 * Sistema: Alarme a Distância
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'alarme_db');
define('DB_USER', getenv('DB_USER') ?: 'alarme_user');
define('DB_PASS', getenv('DB_PASS') ?: 'alarme_pass123');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção/API, retornar erro genérico ou tratar graciosamente
            error_log("Erro de Conexão PDO: " . $e->getMessage());
            throw new Exception("Falha na conexão com o banco de dados.");
        }
    }

    return $pdo;
}

// Inicializa conexão padrão para arquivos que incluem diretamente
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    $pdo = null;
    $db_error = $e->getMessage();
}
