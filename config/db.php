<?php
/**
 * Conexão com o Banco de Dados MySQL via PDO
 * Barbearia Corte na Navalha
 */

// Definir fuso horário padrão do Brasil e codificação UTF-8 nativa
date_default_timezone_set('America/Sao_Paulo');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
if (!headers_sent() && php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=UTF-8');
}

// Constantes de conexão
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'corte_na_navalha');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// URL Base do Sistema (detectada dinamicamente)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(dirname($script), '/\\');
    
    // Normalizar base para apontar para a raiz do projeto corte
    if (strpos($dir, '/corte') !== false) {
        $parts = explode('/corte', $dir);
        $baseUrl = $protocol . $host . $parts[0] . '/corte';
    } else {
        $baseUrl = $protocol . $host . '/corte';
    }
    define('BASE_URL', $baseUrl);
}

/**
 * Retorna a conexão PDO com o MySQL
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Se o banco ainda não existir, tenta criar automaticamente
            try {
                $pdoRoot = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
                $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                
                $sqlPath = __DIR__ . '/../database/schema.sql';
                if (file_exists($sqlPath)) {
                    $sqlContent = file_get_contents($sqlPath);
                    $pdoRoot->exec("USE `" . DB_NAME . "`; " . $sqlContent);
                }
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (Exception $ex) {
                die("<div style='font-family:sans-serif; background:#1c1917; color:#f87171; padding:25px; border-radius:8px; margin:40px auto; max-width:600px; border:1px solid #7f1d1d;'>
                    <h3 style='margin-top:0;'>Erro de Conexão com o Banco de Dados</h3>
                    <p>Não foi possível conectar ao MySQL do XAMPP: " . htmlspecialchars($ex->getMessage()) . "</p>
                    <p style='color:#cbd5e1;'>Certifique-se de que o MySQL está iniciado no painel de controle do XAMPP.</p>
                </div>");
            }
        }
    }

    return $pdo;
}

/**
 * Retorna os dados da barbearia configurados no sistema
 */
function getEmpresaConfig() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT * FROM configuracao_empresa LIMIT 1");
        $config = $stmt->fetch();
        if (!$config) {
            return [
                'nome_barbearia' => 'Corte na Navalha',
                'slogan' => 'Tradição e Estilo em Cada Detalhe',
                'cnpj' => '32.198.472/0001-55',
                'telefone' => '(11) 98765-4321',
                'email' => 'contato@cortenanavalha.com.br',
                'endereco' => 'Av. Paulista, 1500 - Bela Vista, São Paulo - SP',
                'chave_pix_principal' => 'pix@cortenanavalha.com.br'
            ];
        }
        return $config;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Gerador de Payload PIX Padrão BACEN (BR Code Copia e Cola)
 */
function gerarPixCopiaECola($chave, $nomeBeneficiario, $cidade, $valor, $identificador = '***') {
    // Tratamento de dados
    $nome = substr(mb_strtoupper(preg_replace('/[^a-zA-Z0-9 ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nomeBeneficiario))), 0, 25);
    $cidade = substr(mb_strtoupper(preg_replace('/[^a-zA-Z0-9 ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $cidade))), 0, 15);
    $valorFormatado = number_format($valor, 2, '.', '');
    $id = substr(preg_replace('/[^a-zA-Z0-9]/', '', $identificador), 0, 25);
    if (empty($id)) $id = '***';

    $formataCampo = function($id, $valor) {
        return sprintf('%02d%02d%s', $id, strlen($valor), $valor);
    };

    // Merchant Account Information (GUI + Chave)
    $gui = $formataCampo('00', 'br.gov.bcb.pix');
    $chaveField = $formataCampo('01', $chave);
    $merchantAccount = $gui . $chaveField;

    // Additional Data Field (TXID)
    $txidField = $formataCampo('05', $id);

    $payload = 
        $formataCampo('00', '01') . // Payload Format Indicator
        $formataCampo('26', $merchantAccount) . // Merchant Account Information
        $formataCampo('52', '0000') . // Merchant Category Code
        $formataCampo('53', '986') . // Transaction Currency (Real 986)
        $formataCampo('54', $valorFormatado) . // Transaction Amount
        $formataCampo('58', 'BR') . // Country Code
        $formataCampo('59', $nome) . // Merchant Name
        $formataCampo('60', $cidade) . // Merchant City
        $formataCampo('62', $txidField) . // Additional Data Field
        '6304'; // CRC16 Header

    // Cálculo do CRC16-CCITT (0xFFFF)
    $crc = 0xFFFF;
    for ($c = 0; $c < strlen($payload); $c++) {
        $crc ^= (ord($payload[$c]) << 8);
        for ($i = 0; $i < 8; $i++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }

    $crcFinal = strtoupper(sprintf('%04x', $crc));
    return $payload . $crcFinal;
}

/**
 * Retorna URL de imagem do QR Code para o código Copia e Cola
 */
function gerarUrlQrCode($texto, $tamanho = 280) {
    return "https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data=" . urlencode($texto) . "&color=1e293b&bgcolor=ffffff";
}
