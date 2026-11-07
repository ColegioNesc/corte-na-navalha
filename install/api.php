<?php
/**
 * API do Assistente de Instalação e Migração (/install)
 * Barbearia Corte na Navalha
 *
 * Lê e executa diretamente os arquivos SQL oficiais do projeto:
 * - sql/schema.sql
 * - database/migracao_mods.sql
 */

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Sao_Paulo');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$acao = trim($data['acao'] ?? $_GET['acao'] ?? '');

/**
 * Conecta ao banco do host (ou cria a base caso o usuário tenha permissão)
 */
function conectarBancoInstalacao(string $host, string $port, string $dbname, string $user, string $pass): array {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true, // Permite múltiplos statements e flexibilidade de migração
        PDO::ATTR_TIMEOUT            => 8
    ];

    $dbnameLimpo = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbname);
    if (empty($dbnameLimpo)) {
        throw new Exception('O nome do banco de dados informado é inválido.');
    }

    $bancoCriadoAgora = false;

    try {
        // 1. Tenta conectar diretamente ao banco informado (padrão de hospedagens como Hostinger/cPanel)
        $dsn = "mysql:host={$host};port={$port};dbname={$dbnameLimpo};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // 2. Se o erro for banco inexistente (1049), tenta conectar sem dbname e criar o banco
        if (strpos($e->getMessage(), '1049') !== false || stripos($e->getMessage(), 'Unknown database') !== false) {
            $dsnSemBanco = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsnSemBanco, $user, $pass, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbnameLimpo}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbnameLimpo}`");
            $bancoCriadoAgora = true;
        } else {
            throw $e;
        }
    }

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $versao = $pdo->query("SELECT VERSION()")->fetchColumn();
    $tabelasExistentes = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    return [
        'pdo'                => $pdo,
        'dbname'             => $dbnameLimpo,
        'versao_mysql'       => $versao,
        'banco_criado_agora' => $bancoCriadoAgora,
        'total_tabelas'      => count($tabelasExistentes)
    ];
}

/**
 * Divide um script SQL em instruções individuais respeitando strings entre aspas
 */
function dividirInstrucoesSql(string $sql): array {
    $instrucoes = [];
    $atual = '';
    $emAspasSimples = false;
    $emAspasDuplas = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $anterior = $i > 0 ? $sql[$i - 1] : '';

        if ($ch === "'" && !$emAspasDuplas && $anterior !== '\\') {
            $emAspasSimples = !$emAspasSimples;
        } elseif ($ch === '"' && !$emAspasSimples && $anterior !== '\\') {
            $emAspasDuplas = !$emAspasDuplas;
        }

        if ($ch === ';' && !$emAspasSimples && !$emAspasDuplas) {
            $cmd = trim($atual);
            if ($cmd !== '') {
                $instrucoes[] = $cmd;
            }
            $atual = '';
        } else {
            $atual .= $ch;
        }
    }

    if (trim($atual) !== '') {
        $instrucoes[] = trim($atual);
    }

    return $instrucoes;
}

/**
 * Lê um arquivo .sql do projeto, remove comandos CREATE DATABASE / USE fixos
 * (para respeitar o nome do banco do host do aluno) e executa as instruções SQL.
 */
function executarArquivoSql(PDO $pdo, string $caminhoArquivo): int {
    if (!file_exists($caminhoArquivo)) {
        throw new Exception("Arquivo SQL não encontrado: " . basename($caminhoArquivo));
    }

    $sql = file_get_contents($caminhoArquivo);

    // Remove comentários de linha (-- ...)
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    // Remove CREATE DATABASE e USE fixos (`corte_na_navalha`) para usar o banco conectado pelo aluno
    $sql = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?corte_na_navalha`?[^;]*;/i', '', $sql);
    $sql = preg_replace('/USE\s+`?corte_na_navalha`?\s*;/i', '', $sql);

    $comandos = dividirInstrucoesSql($sql);
    $executados = 0;

    foreach ($comandos as $cmd) {
        $cmd = trim($cmd);
        if ($cmd === '') continue;

        try {
            $pdo->exec($cmd);
            $executados++;
        } catch (PDOException $e) {
            // Caso o servidor seja MySQL 8 puro (que não aceita "ADD COLUMN IF NOT EXISTS" do MariaDB),
            // converte automaticamente cada ADD COLUMN IF NOT EXISTS em verificação individual
            if (stripos($cmd, 'ADD COLUMN IF NOT EXISTS') !== false) {
                executarAlterTableCompativel($pdo, $cmd);
                $executados++;
            } elseif (
                strpos($e->getMessage(), '1060') !== false || // Duplicate column name
                strpos($e->getMessage(), '1061') !== false || // Duplicate key name
                strpos($e->getMessage(), '1050') !== false    // Table already exists
            ) {
                // Ignora caso a coluna/tabela já exista
                continue;
            } else {
                throw $e;
            }
        }
    }

    return $executados;
}

/**
 * Converte um comando ALTER TABLE ... ADD COLUMN IF NOT EXISTS (sintaxe MariaDB)
 * para comandos individuais compatíveis com qualquer MySQL 5.7 / 8.0+
 */
function executarAlterTableCompativel(PDO $pdo, string $cmd): void {
    if (!preg_match('/ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $cmd, $mTabela)) {
        return;
    }
    $tabela = $mTabela[1];

    if (preg_match_all('/ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?\s+([^,;]+)/i', $cmd, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $coluna = $match[1];
            $definicao = trim($match[2]);
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tabela}` LIKE :col");
            $stmt->execute([':col' => $coluna]);
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `{$tabela}` ADD COLUMN `{$coluna}` {$definicao}");
            }
        }
    }
}

// =========================================================================
// AÇÃO 1: TESTAR CONEXÃO COM O BANCO DE DADOS
// =========================================================================
if ($acao === 'testar_conexao') {
    $host   = trim($data['db_host'] ?? 'localhost');
    $port   = trim($data['db_port'] ?? '3306');
    $dbname = trim($data['db_name'] ?? '');
    $user   = trim($data['db_user'] ?? '');
    $pass   = (string)($data['db_pass'] ?? '');

    if (empty($host) || empty($dbname) || empty($user)) {
        echo json_encode([
            'success' => false,
            'message' => 'Preencha o Host, Nome do Banco de Dados e Usuário para testar a conexão.'
        ]);
        exit;
    }

    try {
        $info = conectarBancoInstalacao($host, $port, $dbname, $user, $pass);
        $msgExtra = $info['banco_criado_agora']
            ? "O banco '{$info['dbname']}' não existia e foi criado automaticamente."
            : "Banco '{$info['dbname']}' localizado ({$info['total_tabelas']} tabela(s) existente(s)).";

        echo json_encode([
            'success'       => true,
            'message'       => "Conexão estabelecida com sucesso! {$msgExtra}",
            'mysql_version' => $info['versao_mysql'],
            'total_tabelas' => $info['total_tabelas']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao conectar no MySQL: ' . $e->getMessage()
        ]);
    }
    exit;
}

// =========================================================================
// AÇÃO 2: SALVAR CONFIGURAÇÃO E MIGRAR A PARTIR DE sql/schema.sql
// =========================================================================
if ($acao === 'executar_instalacao') {
    $host          = trim($data['db_host'] ?? 'localhost');
    $port          = trim($data['db_port'] ?? '3306');
    $dbname        = trim($data['db_name'] ?? 'corte_na_navalha');
    $user          = trim($data['db_user'] ?? 'root');
    $pass          = (string)($data['db_pass'] ?? '');
    $modoProducao  = !empty($data['modo_producao']);
    $nomeBarbearia = trim($data['nome_barbearia'] ?? '');
    $chavePix      = trim($data['chave_pix'] ?? '');

    $logs = [];

    try {
        // 1. Conectar ao banco informado pelo aluno
        $info = conectarBancoInstalacao($host, $port, $dbname, $user, $pass);
        /** @var PDO $pdo */
        $pdo = $info['pdo'];
        $dbnameLimpo = $info['dbname'];
        $logs[] = "Conectado ao servidor MySQL ({$info['versao_mysql']}) na base '{$dbnameLimpo}'.";

        // 2. Executar o arquivo principal sql/schema.sql
        $caminhoSchema = __DIR__ . '/../sql/schema.sql';
        $qtdSchema = executarArquivoSql($pdo, $caminhoSchema);
        $logs[] = "Arquivo sql/schema.sql importado com sucesso ({$qtdSchema} instruções executadas).";

        // 3. Executar complementarmente database/migracao_mods.sql (caso exista)
        $caminhoMods = __DIR__ . '/../database/migracao_mods.sql';
        if (file_exists($caminhoMods)) {
            $qtdMods = executarArquivoSql($pdo, $caminhoMods);
            $logs[] = "Arquivo database/migracao_mods.sql aplicado ({$qtdMods} instruções executadas).";
        }

        // 4. Atualizar Nome da Barbearia e Chave PIX caso o aluno tenha personalizado na tela
        if ($nomeBarbearia !== '' || $chavePix !== '') {
            $stmtUp = $pdo->prepare("
                UPDATE `configuracao_empresa`
                SET `nome_barbearia` = COALESCE(NULLIF(:nome, ''), `nome_barbearia`),
                    `chave_pix_principal` = COALESCE(NULLIF(:pix, ''), `chave_pix_principal`)
                WHERE `id` = 1
            ");
            $stmtUp->execute([
                ':nome' => $nomeBarbearia,
                ':pix'  => $chavePix
            ]);
            $logs[] = "Identificação inicial da barbearia e chave PIX configuradas.";
        }

        // 5. Gravar arquivo config/db_credentials.php
        $configFile = __DIR__ . '/../config/db_credentials.php';
        $conteudoConfig = "<?php\n"
            . "/**\n"
            . " * Credenciais de Banco de Dados geradas pelo Assistente de Instalação (/install)\n"
            . " * Data da instalação: " . date('d/m/Y H:i:s') . "\n"
            . " */\n\n"
            . "define('DB_HOST', " . var_export($host, true) . ");\n"
            . "define('DB_PORT', " . var_export($port, true) . ");\n"
            . "define('DB_NAME', " . var_export($dbnameLimpo, true) . ");\n"
            . "define('DB_USER', " . var_export($user, true) . ");\n"
            . "define('DB_PASS', " . var_export($pass, true) . ");\n"
            . "define('DB_CHARSET', 'utf8mb4');\n"
            . "define('ACABOUOTESTE', " . ($modoProducao ? 'true' : 'false') . ");\n";

        if (@file_put_contents($configFile, $conteudoConfig) === false) {
            throw new Exception('Não foi possível gravar o arquivo config/db_credentials.php. Verifique as permissões de escrita da pasta config/.');
        }

        $logs[] = "Arquivo config/db_credentials.php gravado com sucesso.";

        echo json_encode([
            'success' => true,
            'message' => 'Instalação e migração do banco de dados concluídas com sucesso!',
            'logs'    => $logs
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro durante a migração: ' . $e->getMessage(),
            'logs'    => $logs
        ]);
    }
    exit;
}

// =========================================================================
// AÇÃO 3: REMOVER PASTA /install AO FINALIZAR
// =========================================================================
if ($acao === 'remover_install') {
    $dirInstall = __DIR__;
    $erros = [];

    $arquivos = scandir($dirInstall);
    if (is_array($arquivos)) {
        foreach ($arquivos as $arq) {
            if ($arq === '.' || $arq === '..') continue;
            $caminho = $dirInstall . DIRECTORY_SEPARATOR . $arq;
            if (is_file($caminho)) {
                if (!@unlink($caminho)) {
                    $erros[] = $arq;
                }
            }
        }
    }

    @chdir(dirname($dirInstall));
    $pastaRemovida = @rmdir($dirInstall);

    echo json_encode([
        'success'        => true,
        'pasta_removida' => $pastaRemovida,
        'erros'          => $erros,
        'message'        => $pastaRemovida
            ? 'Pasta /install removida com segurança!'
            : 'Arquivos de instalação limpos com sucesso!'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida informada para a API de instalação.'
]);
