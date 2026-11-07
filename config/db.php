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

// Carrega credenciais geradas pelo instalador (/install), caso existam
if (file_exists(__DIR__ . '/db_credentials.php')) {
    require_once __DIR__ . '/db_credentials.php';
}

// Constantes de conexão (Fallback padrão para XAMPP caso ainda não configurado pelo /install)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'corte_na_navalha');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// PALAVRA-CHAVE CONFORME MODS.MD: ACABOUOTESTE
// Defina como true quando o projeto for para produção para remover todos os atalhos de teste
if (!defined('ACABOUOTESTE')) {
    define('ACABOUOTESTE', false);
}

/**
 * Verifica se os testes foram finalizados (produção ativa)
 */
function acabouOTeste() {
    return defined('ACABOUOTESTE') && ACABOUOTESTE === true;
}

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
 * Redireciona automaticamente para o assistente de instalação (/install)
 * caso a pasta exista e o banco ainda não esteja conectado/migrado.
 */
function redirecionarParaInstaladorSeDisponivel() {
    $installFile = __DIR__ . '/../install/index.php';
    if (file_exists($installFile) && php_sapi_name() !== 'cli') {
        $scriptAtual = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($scriptAtual, '/install/') === false) {
            $urlInstalador = BASE_URL . '/install/index.php';
            if (!headers_sent()) {
                header('Location: ' . $urlInstalador);
                exit;
            } else {
                echo "<script>window.location.href = " . json_encode($urlInstalador) . ";</script>";
                exit;
            }
        }
    }
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
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $eSocket) {
                // Se o host for 'localhost' e o socket Unix falhar (erro 2002), tenta via TCP 127.0.0.1
                if (strtolower(DB_HOST) === 'localhost' && strpos($eSocket->getMessage(), '2002') !== false) {
                    $dsnTcp = "mysql:host=127.0.0.1;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                    $pdo = new PDO($dsnTcp, DB_USER, DB_PASS, $options);
                } else {
                    throw $eSocket;
                }
            }
            
            // Verifica se as tabelas principais já foram migradas no banco
            $checkTable = $pdo->query("SHOW TABLES LIKE 'configuracao_empresa'");
            if ($checkTable->rowCount() === 0) {
                redirecionarParaInstaladorSeDisponivel();
            }
        } catch (PDOException $e) {
            // Se a pasta /install existir, redireciona o aluno direto para o Assistente de Instalação
            redirecionarParaInstaladorSeDisponivel();

            // Traduz o erro para linguagem amigável caso a pasta /install já tenha sido removida
            $msgOriginal = $e->getMessage();
            if (strpos($msgOriginal, '1045') !== false) {
                $motivoAmigavel = "O usuário ou a senha do banco de dados estão incorretos.";
            } elseif (strpos($msgOriginal, '1049') !== false) {
                $motivoAmigavel = "O banco de dados '<strong>" . htmlspecialchars(DB_NAME) . "</strong>' não foi encontrado no servidor.";
            } elseif (strpos($msgOriginal, '2002') !== false) {
                $motivoAmigavel = "O servidor MySQL parece estar desligado ou o endereço do Host ('<strong>" . htmlspecialchars(DB_HOST) . "</strong>') está indisponível.";
            } else {
                $motivoAmigavel = "Não foi possível estabelecer comunicação com o servidor de banco de dados.";
            }

            die("<div style='font-family:sans-serif; background:#111319; color:#f87171; padding:28px; border-radius:10px; margin:50px auto; max-width:620px; border:1px solid #dc2626; box-shadow:0 10px 30px rgba(0,0,0,0.5);'>
                <h3 style='margin-top:0; color:#ffffff;'>⚠️ Banco de Dados Indisponível</h3>
                <p style='line-height:1.5; color:#f1f5f9;'>{$motivoAmigavel}</p>
                <p style='color:#94a3b8; font-size:0.9rem; margin-bottom:0;'>Dica: se estiver no computador local (XAMPP), verifique se o módulo <strong>MySQL</strong> está iniciado. Em hospedagem online, confira os dados em <code>config/db_credentials.php</code>.</p>
            </div>");
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
                'chave_pix_principal' => 'pix@cortenanavalha.com.br',
                'tipo_catalogo' => 'global',
                'metodo_pagamento_ativo' => 'pix_fixo',
                'mp_public_key' => '',
                'mp_access_token' => '',
                'mp_webhook_secret' => '',
                'mp_sandbox' => 1,
                'comissao_automatica' => 0,
                'taxa_cancelamento_percentual' => 20.00,
                'horas_minimas_cancelamento' => 2,
                'politica_cancelamento_texto' => 'Cancelamentos com mais de 2 horas de antecedência contam com reembolso integral (100%). Cancelamentos realizados fora deste prazo estão sujeitos à taxa de retenção de até 20% conforme o Código de Defesa do Consumidor para cobrir custos de reserva de horário.'
            ];
        }
        $config['tipo_catalogo'] = $config['tipo_catalogo'] ?? 'global';
        $config['metodo_pagamento_ativo'] = $config['metodo_pagamento_ativo'] ?? 'pix_fixo';
        $config['mp_public_key'] = $config['mp_public_key'] ?? '';
        $config['mp_access_token'] = $config['mp_access_token'] ?? '';
        $config['mp_webhook_secret'] = $config['mp_webhook_secret'] ?? '';
        $config['mp_sandbox'] = (int)($config['mp_sandbox'] ?? 1);
        $config['comissao_automatica'] = (int)($config['comissao_automatica'] ?? 0);
        $config['taxa_cancelamento_percentual'] = (float)($config['taxa_cancelamento_percentual'] ?? 20.00);
        $config['horas_minimas_cancelamento'] = (int)($config['horas_minimas_cancelamento'] ?? 2);
        $config['politica_cancelamento_texto'] = $config['politica_cancelamento_texto'] ?? 'Cancelamentos com mais de 2 horas de antecedência contam com reembolso integral (100%). Cancelamentos realizados fora deste prazo estão sujeitos à taxa de retenção de até 20% conforme o Código de Defesa do Consumidor para cobrir custos de reserva de horário.';
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
        
        // Se o gateway Mercado Pago estiver ativo e configurado
        if (($empresa['metodo_pagamento_ativo'] ?? '') === 'mercadopago' && !empty($empresa['mp_access_token'])) {
            require_once __DIR__ . '/../includes/mercadopago.php';
            $stmtCli = $pdo->prepare("SELECT nome, email, cpf FROM usuarios WHERE id = :cid LIMIT 1");
            $stmtCli->execute([':cid' => $ag['cliente_id']]);
            $cliente = $stmtCli->fetch();
            
            $resMp = MercadoPagoService::criarPagamentoPix($ag, $cliente ?: ['nome' => 'Cliente', 'email' => 'cliente@gmail.com', 'cpf' => ''], $empresa);
            if ($resMp['success'] && !empty($resMp['pix_copia_cola'])) {
                $pixCopiaCola = $resMp['pix_copia_cola'];
                $pixTxId = 'MP-' . $resMp['payment_id'];
                $up = $pdo->prepare("UPDATE agendamentos SET pix_copia_cola = :copia, pix_txid = :txid, metodo_pagamento = 'pix' WHERE id = :id");
                $up->execute([':copia' => $pixCopiaCola, ':txid' => $pixTxId, ':id' => $ag['id']]);
                $ag['pix_copia_cola'] = $pixCopiaCola;
            }
        }

        // Fallback: PIX Fixo BACEN BRCode Padrão
        if (empty($pixCopiaCola)) {
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
