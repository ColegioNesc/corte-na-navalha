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

// URL Base do Sistema (detectada dinamicamente para raiz do domínio ou qualquer subpasta)
if (!defined('BASE_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Raiz do projeto no disco (diretório pai de /config)
    $projectRoot = str_replace('\\', '/', rtrim(realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'), '/\\'));
    $scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']) : '';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';

    $basePath = null;

    // 1. Derivar caminho relativo a partir do script executado
    if ($scriptFilename && $scriptName && str_starts_with(strtolower($scriptFilename), strtolower($projectRoot))) {
        $relPath = substr($scriptFilename, strlen($projectRoot));
        if ($relPath === '' || $relPath === '/') {
            $basePath = dirname($scriptName);
        } elseif (str_ends_with(strtolower($scriptName), strtolower($relPath))) {
            $basePath = substr($scriptName, 0, strlen($scriptName) - strlen($relPath));
        }
    }

    // 2. Comparar raiz do projeto com DOCUMENT_ROOT
    if ($basePath === null && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = str_replace('\\', '/', rtrim(realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT'], '/\\'));
        if ($docRoot !== '' && str_starts_with(strtolower($projectRoot), strtolower($docRoot))) {
            $basePath = substr($projectRoot, strlen($docRoot));
        }
    }

    // 3. Fallback seguro
    if ($basePath === null) {
        $basePath = str_replace('\\', '/', dirname($scriptName));
    }

    // Normalizar basePath (remover barras duplicadas e garantir que raiz seja vazia)
    $basePath = '/' . trim((string)$basePath, '/\\');
    if ($basePath === '/') {
        $basePath = '';
    }

    $baseUrl = rtrim($protocol . $host . $basePath, '/');
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
    if (empty($texto)) return '';
    return "https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data=" . urlencode($texto) . "&color=b91c1c&bgcolor=ffffff";
}

/**
 * Garante e obtém o PIX Copia e Cola e QR Code de um agendamento
 */
function obterOuGerarPixAgendamento($agendamentoId, $pdo = null) {
    if (!$pdo) $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $agendamentoId]);
    $ag = $stmt->fetch();
    
    if (!$ag) return null;
    
    $pixCopiaCola = $ag['pix_copia_cola'];
    if (empty($pixCopiaCola)) {
        $empresa = getEmpresaConfig();
        $chavePix = $empresa['chave_pix_principal'] ?? 'pix@cortenanavalha.com.br';
        $pixTxId = 'CN' . $ag['id'] . time();
        $pixCopiaCola = gerarPixCopiaECola(
            $chavePix,
            $empresa['nome_barbearia'] ?? 'Corte na Navalha Barbearia',
            'SAO PAULO',
            (float)$ag['valor_total'],
            $pixTxId
        );
        
        $up = $pdo->prepare("UPDATE agendamentos SET pix_copia_cola = :copia, pix_txid = :txid, metodo_pagamento = 'pix' WHERE id = :id");
        $up->execute([':copia' => $pixCopiaCola, ':txid' => $pixTxId, ':id' => $ag['id']]);
        $ag['pix_copia_cola'] = $pixCopiaCola;
    }
    
    return [
        'id'               => $ag['id'],
        'codigo_reserva'   => $ag['codigo_reserva'],
        'valor_total'      => (float)$ag['valor_total'],
        'status_pagamento' => $ag['status_pagamento'],
        'status_atendimento' => $ag['status_atendimento'],
        'pix_copia_cola'   => $pixCopiaCola,
        'qr_code_url'      => gerarUrlQrCode($pixCopiaCola, 260)
    ];
}
