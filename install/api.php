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
 * Traduz erros técnicos do PDO/MySQL (SQLSTATE) para mensagens claras e didáticas para os alunos
 */
function traduzirErroBancoAmigavel(Exception $e, string $host, string $dbname, string $user): string {
    $msg = $e->getMessage();

    // 1. Erro 1045: Usuário ou senha incorretos
    if (strpos($msg, '1045') !== false || stripos($msg, 'Access denied for user') !== false) {
        if (stripos($msg, 'to database') !== false) {
            return "O usuário '{$user}' não tem permissão para acessar ou criar o banco '{$dbname}'. No painel da sua hospedagem, verifique se você criou o banco de dados com esse nome exato (incluindo o prefixo do host) e vinculou o usuário a ele.";
        }
        return "Usuário ou Senha do MySQL incorretos para o usuário '{$user}'. Confira se digitou exatamente como cadastrado no painel da hospedagem (dica: no XAMPP local, o usuário é 'root' e a senha deve ficar vazia).";
    }

    // 2. Erro 1049 / 1044: Banco de dados não existe e sem permissão de criar
    if (strpos($msg, '1049') !== false || strpos($msg, '1044') !== false || stripos($msg, 'Unknown database') !== false) {
        return "O banco de dados '{$dbname}' não foi encontrado no servidor. Se estiver em uma hospedagem online (Hostinger, cPanel, InfinityFree), primeiro crie o banco lá no painel do host e copie o nome completo gerado (ex: u123456_{$dbname}).";
    }

    // 3. Endereço de host inválido / DNS não encontrado
    if (stripos($msg, 'getaddrinfo') !== false || stripos($msg, 'Name or service not known') !== false || stripos($msg, 'Unknown MySQL server host') !== false) {
        return "O endereço do servidor (Host: '{$host}') não foi encontrado. Verifique se digitou o endereço do Host MySQL corretamente sem espaços extras.";
    }

    // 4. Erro 2002: Servidor desligado, porta incorreta ou socket indisponível
    if (strpos($msg, '2002') !== false || stripos($msg, 'No such file or directory') !== false || stripos($msg, 'Connection refused') !== false || stripos($msg, 'recusou') !== false) {
        if (in_array(strtolower($host), ['localhost', '127.0.0.1'])) {
            return "Não foi possível conectar ao MySQL local. Se você está usando o XAMPP/WAMP, abra o painel de controle e clique em 'Start' no MySQL. Se você está em uma hospedagem online, troque 'localhost' pelo endereço do Host MySQL informado no painel da sua hospedagem (ou tente usar 127.0.0.1).";
        }
        return "O servidor '{$host}' recusou a conexão. Verifique se o endereço do Host e a Porta (padrão 3306) estão corretos no painel da sua hospedagem.";
    }

    // 5. Timeout
    if (stripos($msg, 'timed out') !== false || strpos($msg, '2006') !== false) {
        return "O servidor MySQL ('{$host}') demorou muito para responder. Confira se o Host e a Porta informados estão corretos e se o banco aceita conexões.";
    }

    return "Não foi possível completar a operação no banco de dados. Verifique os dados informados (Host, Nome do Banco, Usuário e Senha).";
}

/**
 * Conecta ao banco do host (com fallback inteligente de localhost -> 127.0.0.1
 * e criação automática da base caso o usuário tenha permissão)
 */
function conectarBancoInstalacao(string $host, string $port, string $dbname, string $user, string $pass): array {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::ATTR_TIMEOUT            => 8
    ];

    $dbnameLimpo = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbname);
    if (empty($dbnameLimpo)) {
        throw new Exception('O nome do banco de dados informado é inválido. Use apenas letras, números e underline (_).');
    }

    $bancoCriadoAgora = false;
    $hostEfetivo = $host;

    // Lista de hosts para tentar (se o aluno colocou 'localhost' e o socket Unix falhar com erro 2002, tenta via TCP '127.0.0.1' automaticamente)
    $hostsParaTentar = [$host];
    if (strtolower($host) === 'localhost') {
        $hostsParaTentar[] = '127.0.0.1';
    }

    $ultimoErro = null;
    $pdo = null;

    foreach ($hostsParaTentar as $hTentativa) {
        try {
            // 1. Tenta conectar diretamente ao banco informado
            $dsn = "mysql:host={$hTentativa};port={$port};dbname={$dbnameLimpo};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, $options);
            $hostEfetivo = $hTentativa;
            break;
        } catch (PDOException $e) {
            // 2. Se o erro for banco inexistente (1049), tenta conectar sem dbname e criar o banco
            if (strpos($e->getMessage(), '1049') !== false || stripos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $dsnSemBanco = "mysql:host={$hTentativa};port={$port};charset=utf8mb4";
                    $pdo = new PDO($dsnSemBanco, $user, $pass, $options);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbnameLimpo}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbnameLimpo}`");
                    $bancoCriadoAgora = true;
                    $hostEfetivo = $hTentativa;
                    break;
                } catch (PDOException $eCreate) {
                    $ultimoErro = $eCreate;
                }
            } else {
                $ultimoErro = $e;
            }
        }
    }

    if (!$pdo) {
        throw new Exception(traduzirErroBancoAmigavel($ultimoErro ?? new Exception('Erro desconhecido'), $host, $dbnameLimpo, $user));
    }

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $versao = $pdo->query("SELECT VERSION()")->fetchColumn();
    $tabelasExistentes = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    return [
        'pdo'                => $pdo,
        'host_efetivo'       => $hostEfetivo,
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
            'total_tabelas' => $info['total_tabelas'],
            'host_efetivo'  => $info['host_efetivo']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
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
    $corTema       = trim($data['cor_tema'] ?? 'crimson');
    if (!in_array($corTema, ['crimson', 'gold', 'emerald', 'royal', 'copper', 'purple'], true)) {
        $corTema = 'crimson';
    }

    // Dados obrigatórios do Proprietário (Dono) caso Modo Produção esteja ativo
    $donoNome     = trim($data['dono_nome'] ?? '');
    $donoEmail    = trim($data['dono_email'] ?? '');
    $donoCpf      = trim($data['dono_cpf'] ?? '');
    $donoTelefone = trim($data['dono_telefone'] ?? '');
    $donoSenha    = (string)($data['dono_senha'] ?? '');

    if ($modoProducao) {
        if ($donoNome === '' || $donoEmail === '' || $donoCpf === '' || $donoTelefone === '' || $donoSenha === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Como o Modo Produção está ativo, é obrigatório preencher todos os dados de cadastro do Proprietário (Nome, E-mail, CPF, Telefone e Senha).'
            ]);
            exit;
        }
        if (strlen($donoSenha) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'A senha do Proprietário deve possuir no mínimo 6 caracteres.'
            ]);
            exit;
        }
    }

    $logs = [];

    try {
        // 1. Conectar ao banco informado pelo aluno
        $info = conectarBancoInstalacao($host, $port, $dbname, $user, $pass);
        /** @var PDO $pdo */
        $pdo = $info['pdo'];
        $dbnameLimpo = $info['dbname'];
        $hostParaSalvar = $info['host_efetivo'] ?? $host;
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

        // 4. Garantir coluna cor_tema e atualizar Nome da Barbearia, Chave PIX e Paleta de Cores
        $checkCor = $pdo->query("SHOW COLUMNS FROM `configuracao_empresa` LIKE 'cor_tema'");
        if ($checkCor->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `configuracao_empresa` ADD COLUMN `cor_tema` VARCHAR(30) DEFAULT 'crimson'");
        }

        $stmtUp = $pdo->prepare("
            UPDATE `configuracao_empresa`
            SET `nome_barbearia` = COALESCE(NULLIF(:nome, ''), `nome_barbearia`),
                `chave_pix_principal` = COALESCE(NULLIF(:pix, ''), `chave_pix_principal`),
                `cor_tema` = :cor
            WHERE `id` = 1
        ");
        $stmtUp->execute([
            ':nome' => $nomeBarbearia,
            ':pix'  => $chavePix,
            ':cor'  => $corTema
        ]);
        if ($nomeBarbearia !== '') {
            $stmtEmail = $pdo->prepare("UPDATE `configuracao_email` SET `remetente_nome` = :nome WHERE `id` = 1");
            @$stmtEmail->execute([':nome' => $nomeBarbearia]);
        }
        $logs[] = "Identificação da barbearia, chave PIX e paleta de cores ('{$corTema}') configuradas.";

        // 5. Se Modo Produção estiver ativo:
        //    Remove todos os funcionários/clientes/agendamentos fictícios de demonstração
        //    e mantém na base APENAS o usuário Proprietário (Dono) cadastrado pelo aluno.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];

        if ($modoProducao) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE `avaliacoes`");
            $pdo->exec("TRUNCATE TABLE `agendamento_itens`");
            $pdo->exec("TRUNCATE TABLE `transacoes`");
            $pdo->exec("TRUNCATE TABLE `pagamentos_funcionarios`");
            $pdo->exec("TRUNCATE TABLE `agendamentos`");
            $pdo->exec("TRUNCATE TABLE `barbeiro_servicos`");
            $pdo->exec("TRUNCATE TABLE `usuarios`");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $senhaHashDono = password_hash($donoSenha, PASSWORD_DEFAULT);
            $pixDono = $chavePix !== '' ? $chavePix : $donoEmail;

            $stmtDono = $pdo->prepare("
                INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `telefone`, `senha`, `perfil`, `especialidade`, `chave_pix`, `tipo_chave_pix`, `comissao_percentual`, `permite_catalogo_proprio`, `ativo`)
                VALUES (1, :nome, :email, :cpf, :telefone, :senha, 'dono', 'Proprietário & Gestor', :pix, 'E-mail', 100.00, 1, 1)
            ");
            $stmtDono->execute([
                ':nome'     => $donoNome,
                ':email'    => $donoEmail,
                ':cpf'      => $donoCpf,
                ':telefone' => $donoTelefone,
                ':senha'    => $senhaHashDono,
                ':pix'      => $pixDono
            ]);

            $logs[] = "Usuários fictícios removidos. Base iniciada exclusivamente com o Proprietário ({$donoEmail}).";
        }

        // 5. Gravar arquivo config/db_credentials.php
        $configFile = __DIR__ . '/../config/db_credentials.php';
        $conteudoConfig = "<?php\n"
            . "/**\n"
            . " * Credenciais de Banco de Dados geradas pelo Assistente de Instalação (/install)\n"
            . " * Data da instalação: " . date('d/m/Y H:i:s') . "\n"
            . " */\n\n"
            . "define('DB_HOST', " . var_export($hostParaSalvar, true) . ");\n"
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
