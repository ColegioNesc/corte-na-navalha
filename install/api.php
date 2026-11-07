<?php
/**
 * API do Assistente de Instalação e Migração (/install)
 * Barbearia Corte na Navalha
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
 * Verifica se uma coluna existe em uma tabela e a adiciona caso não exista
 * (Compatível com qualquer versão do MySQL 5.7+, 8.0+ e MariaDB)
 */
function garantirColuna(PDO $pdo, string $tabela, string $coluna, string $definicao): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tabela}` LIKE :col");
    $stmt->execute([':col' => $coluna]);
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `{$tabela}` ADD COLUMN `{$coluna}` {$definicao}");
        return true;
    }
    return false;
}

/**
 * Conecta ao banco do host (ou cria a base caso o usuário tenha permissão)
 */
function conectarBancoInstalacao(string $host, string $port, string $dbname, string $user, string $pass): array {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 8
    ];

    $dbnameLimpo = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbname);
    if (empty($dbnameLimpo)) {
        throw new Exception('O nome do banco de dados informado é inválido.');
    }

    $bancoCriadoAgora = false;

    try {
        // 1. Tenta conectar diretamente ao banco informado (padrão de hospedagens compartilhadas como Hostinger/cPanel)
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
            'success'      => true,
            'message'      => "Conexão estabelecida com sucesso! {$msgExtra}",
            'mysql_version'=> $info['versao_mysql'],
            'total_tabelas'=> $info['total_tabelas']
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
// AÇÃO 2: SALVAR CONFIGURAÇÃO E MIGRAR TODA A BASE DE DADOS
// =========================================================================
if ($acao === 'executar_instalacao') {
    $host          = trim($data['db_host'] ?? 'localhost');
    $port          = trim($data['db_port'] ?? '3306');
    $dbname        = trim($data['db_name'] ?? 'corte_na_navalha');
    $user          = trim($data['db_user'] ?? 'root');
    $pass          = (string)($data['db_pass'] ?? '');
    $modoProducao  = !empty($data['modo_producao']);
    $nomeBarbearia = trim($data['nome_barbearia'] ?? 'Corte na Navalha Barbearia');
    $chavePix      = trim($data['chave_pix'] ?? 'pix@cortenanavalha.com.br');

    $logs = [];

    try {
        // 1. Conectar ao banco
        $info = conectarBancoInstalacao($host, $port, $dbname, $user, $pass);
        /** @var PDO $pdo */
        $pdo = $info['pdo'];
        $dbnameLimpo = $info['dbname'];
        $logs[] = "Conectado ao servidor MySQL ({$info['versao_mysql']}) na base '{$dbnameLimpo}'.";

        // 2. Criar/Atualizar tabelas principais e auxiliares
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `configuracao_empresa` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nome_barbearia` VARCHAR(150) NOT NULL DEFAULT 'Corte na Navalha Barbearia',
                `slogan` VARCHAR(255) DEFAULT 'Tradição clássica, navalha afiada e corte moderno',
                `cnpj` VARCHAR(20) DEFAULT '32.198.472/0001-55',
                `telefone` VARCHAR(20) DEFAULT '(11) 98765-4321',
                `email` VARCHAR(100) DEFAULT 'contato@cortenanavalha.com.br',
                `endereco` VARCHAR(255) DEFAULT 'Av. Paulista, 1500 - Bela Vista, São Paulo - SP',
                `horario_abertura` TIME DEFAULT '08:00:00',
                `horario_fechamento` TIME DEFAULT '20:00:00',
                `chave_pix_principal` VARCHAR(100) DEFAULT 'pix@cortenanavalha.com.br',
                `tipo_chave_pix` VARCHAR(30) DEFAULT 'E-mail',
                `tipo_catalogo` ENUM('global', 'individual') DEFAULT 'global',
                `metodo_pagamento_ativo` ENUM('pix_fixo', 'mercadopago') DEFAULT 'pix_fixo',
                `mp_public_key` VARCHAR(255) DEFAULT NULL,
                `mp_access_token` TEXT DEFAULT NULL,
                `mp_webhook_secret` VARCHAR(255) DEFAULT NULL,
                `mp_sandbox` TINYINT(1) DEFAULT 1,
                `comissao_automatica` TINYINT(1) DEFAULT 0,
                `taxa_cancelamento_percentual` DECIMAL(5,2) DEFAULT 20.00,
                `horas_minimas_cancelamento` INT DEFAULT 2,
                `politica_cancelamento_texto` TEXT DEFAULT NULL,
                `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `usuarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nome` VARCHAR(150) NOT NULL,
                `email` VARCHAR(120) NOT NULL UNIQUE,
                `cpf` VARCHAR(20) NOT NULL UNIQUE,
                `telefone` VARCHAR(20) NOT NULL,
                `senha` VARCHAR(255) NOT NULL,
                `perfil` ENUM('cliente', 'barbeiro', 'recepcao', 'dono') NOT NULL DEFAULT 'cliente',
                `foto` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
                `especialidade` VARCHAR(150) DEFAULT NULL,
                `chave_pix` VARCHAR(100) DEFAULT NULL,
                `tipo_chave_pix` VARCHAR(30) DEFAULT NULL,
                `comissao_percentual` DECIMAL(5,2) DEFAULT 50.00,
                `permite_catalogo_proprio` TINYINT(1) DEFAULT 0,
                `permissoes` TEXT DEFAULT NULL,
                `ativo` TINYINT(1) DEFAULT 1,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `servicos` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nome` VARCHAR(100) NOT NULL,
                `descricao` TEXT,
                `preco` DECIMAL(10,2) NOT NULL,
                `tempo_minutos` INT NOT NULL DEFAULT 30,
                `icone` VARCHAR(50) DEFAULT 'bi-scissors',
                `imagem` VARCHAR(255) DEFAULT 'assets/img/servico-corte.jpg',
                `categoria` ENUM('cabelo', 'barba', 'combo', 'especial') NOT NULL DEFAULT 'cabelo',
                `destaque` TINYINT(1) DEFAULT 0,
                `ativo` TINYINT(1) DEFAULT 1,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `agendamentos` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo_reserva` VARCHAR(20) NOT NULL UNIQUE,
                `cliente_id` INT NOT NULL,
                `barbeiro_id` INT NOT NULL,
                `data_agendamento` DATE NOT NULL,
                `horario` TIME NOT NULL,
                `valor_total` DECIMAL(10,2) NOT NULL,
                `metodo_pagamento` ENUM('pix', 'recepcao') NOT NULL DEFAULT 'pix',
                `status_pagamento` ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente',
                `status_atendimento` ENUM('agendado', 'em_atendimento', 'concluido', 'cancelado') NOT NULL DEFAULT 'agendado',
                `pix_copia_cola` TEXT DEFAULT NULL,
                `pix_txid` VARCHAR(100) DEFAULT NULL,
                `pix_pago_em` DATETIME DEFAULT NULL,
                `observacoes` TEXT DEFAULT NULL,
                `origem` ENUM('web', 'balcao') DEFAULT 'web',
                `cliente_balcao` TINYINT(1) DEFAULT 0,
                `cliente_nome_avulso` VARCHAR(100) DEFAULT NULL,
                `taxa_cancelamento` DECIMAL(10,2) DEFAULT 0.00,
                `valor_reembolsado` DECIMAL(10,2) DEFAULT 0.00,
                `taxa_isenta` TINYINT(1) DEFAULT 0,
                `cancelado_em` DATETIME DEFAULT NULL,
                `motivo_cancelamento` TEXT DEFAULT NULL,
                `recebido_por_id` INT DEFAULT NULL,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_agendamento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_agendamento_barbeiro` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `fk_agendamento_recepcao` FOREIGN KEY (`recebido_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `agendamento_itens` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `agendamento_id` INT NOT NULL,
                `servico_id` INT NOT NULL,
                `preco_momento` DECIMAL(10,2) NOT NULL,
                CONSTRAINT `fk_item_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_item_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `avaliacoes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `agendamento_id` INT NOT NULL UNIQUE,
                `cliente_id` INT NOT NULL,
                `barbeiro_id` INT NOT NULL,
                `estrelas` TINYINT NOT NULL,
                `comentario` TEXT,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_avaliacao_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_avaliacao_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_avaliacao_barbeiro` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `pagamentos_funcionarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `funcionario_id` INT NOT NULL,
                `dono_id` INT NOT NULL,
                `valor` DECIMAL(10,2) NOT NULL,
                `chave_pix_utilizada` VARCHAR(100) NOT NULL,
                `tipo_chave` VARCHAR(30) NOT NULL,
                `comprovante_transacao` VARCHAR(100) NOT NULL,
                `referencia_periodo` VARCHAR(50) DEFAULT 'Comissão de Atendimentos',
                `data_pagamento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `observacoes` TEXT,
                CONSTRAINT `fk_pagto_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `fk_pagto_dono` FOREIGN KEY (`dono_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `barbeiro_servicos` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `barbeiro_id` INT NOT NULL,
                `nome` VARCHAR(100) NOT NULL,
                `descricao` TEXT DEFAULT NULL,
                `preco` DECIMAL(10,2) NOT NULL,
                `tempo_minutos` INT NOT NULL DEFAULT 30,
                `categoria` ENUM('cabelo', 'barba', 'combo', 'especial') DEFAULT 'cabelo',
                `ativo` TINYINT(1) DEFAULT 1,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_barbeiro_servico` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `transacoes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo_transacao` VARCHAR(50) NOT NULL UNIQUE,
                `agendamento_id` INT DEFAULT NULL,
                `tipo` ENUM('recebimento', 'comissao', 'estorno') NOT NULL DEFAULT 'recebimento',
                `metodo` ENUM('dinheiro', 'pix_fixo', 'mercadopago_pix', 'mercadopago_cartao', 'outro') NOT NULL DEFAULT 'dinheiro',
                `valor_total` DECIMAL(10,2) NOT NULL,
                `valor_recebido` DECIMAL(10,2) DEFAULT NULL,
                `troco` DECIMAL(10,2) DEFAULT 0.00,
                `taxa_retida` DECIMAL(10,2) DEFAULT 0.00,
                `valor_liquido` DECIMAL(10,2) DEFAULT NULL,
                `cliente_id` INT DEFAULT NULL,
                `funcionario_id` INT DEFAULT NULL,
                `operador_id` INT DEFAULT NULL,
                `status` ENUM('concluida', 'pendente', 'cancelada') DEFAULT 'concluida',
                `detalhes` TEXT DEFAULT NULL,
                `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_transacao_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_transacao_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_transacao_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_transacao_operador` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `configuracao_email` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `driver` ENUM('smtp', 'gmail', 'mail') DEFAULT 'gmail',
                `host` VARCHAR(150) DEFAULT 'smtp.gmail.com',
                `porta` INT DEFAULT 587,
                `seguranca` ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
                `usuario` VARCHAR(150) DEFAULT '',
                `senha` VARCHAR(255) DEFAULT '',
                `remetente_nome` VARCHAR(150) DEFAULT 'Corte na Navalha Barbearia',
                `remetente_email` VARCHAR(150) DEFAULT 'contato@cortenanavalha.com.br',
                `ativo` TINYINT(1) DEFAULT 0,
                `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `templates_email` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(50) NOT NULL UNIQUE,
                `titulo` VARCHAR(100) NOT NULL,
                `assunto` VARCHAR(200) NOT NULL,
                `corpo_html` LONGTEXT NOT NULL,
                `variaveis_disponiveis` VARCHAR(255) DEFAULT '',
                `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $logs[] = "Todas as 11 tabelas estruturais verificadas/criadas com sucesso.";

        // 3. Garantir colunas complementares caso a base já existisse em versão anterior
        garantirColuna($pdo, 'configuracao_empresa', 'tipo_catalogo', "ENUM('global', 'individual') DEFAULT 'global'");
        garantirColuna($pdo, 'configuracao_empresa', 'metodo_pagamento_ativo', "ENUM('pix_fixo', 'mercadopago') DEFAULT 'pix_fixo'");
        garantirColuna($pdo, 'configuracao_empresa', 'mp_public_key', "VARCHAR(255) DEFAULT NULL");
        garantirColuna($pdo, 'configuracao_empresa', 'mp_access_token', "TEXT DEFAULT NULL");
        garantirColuna($pdo, 'configuracao_empresa', 'mp_webhook_secret', "VARCHAR(255) DEFAULT NULL");
        garantirColuna($pdo, 'configuracao_empresa', 'mp_sandbox', "TINYINT(1) DEFAULT 1");
        garantirColuna($pdo, 'configuracao_empresa', 'comissao_automatica', "TINYINT(1) DEFAULT 0");
        garantirColuna($pdo, 'configuracao_empresa', 'taxa_cancelamento_percentual', "DECIMAL(5,2) DEFAULT 20.00");
        garantirColuna($pdo, 'configuracao_empresa', 'horas_minimas_cancelamento', "INT DEFAULT 2");
        garantirColuna($pdo, 'configuracao_empresa', 'politica_cancelamento_texto', "TEXT DEFAULT NULL");

        garantirColuna($pdo, 'usuarios', 'permite_catalogo_proprio', "TINYINT(1) DEFAULT 0");
        garantirColuna($pdo, 'usuarios', 'permissoes', "TEXT DEFAULT NULL");

        garantirColuna($pdo, 'agendamentos', 'origem', "ENUM('web', 'balcao') DEFAULT 'web'");
        garantirColuna($pdo, 'agendamentos', 'cliente_balcao', "TINYINT(1) DEFAULT 0");
        garantirColuna($pdo, 'agendamentos', 'cliente_nome_avulso', "VARCHAR(100) DEFAULT NULL");
        garantirColuna($pdo, 'agendamentos', 'taxa_cancelamento', "DECIMAL(10,2) DEFAULT 0.00");
        garantirColuna($pdo, 'agendamentos', 'valor_reembolsado', "DECIMAL(10,2) DEFAULT 0.00");
        garantirColuna($pdo, 'agendamentos', 'taxa_isenta', "TINYINT(1) DEFAULT 0");
        garantirColuna($pdo, 'agendamentos', 'cancelado_em', "DATETIME DEFAULT NULL");
        garantirColuna($pdo, 'agendamentos', 'motivo_cancelamento', "TEXT DEFAULT NULL");

        garantirColuna($pdo, 'transacoes', 'taxa_retida', "DECIMAL(10,2) DEFAULT 0.00");
        garantirColuna($pdo, 'transacoes', 'valor_liquido', "DECIMAL(10,2) DEFAULT NULL");

        $logs[] = "Migrações de colunas (Catálogo, Gateway, Balcão e Regras CDC) aplicadas.";

        // 4. Popular dados iniciais (Seed)
        $politicaPadrao = 'Cancelamentos com mais de 2 horas de antecedência contam com reembolso integral (100%). Cancelamentos realizados fora deste prazo estão sujeitos à taxa de retenção de até 20% conforme o Código de Defesa do Consumidor para cobrir custos operacionais de reserva.';

        $stmtEmp = $pdo->prepare("
            INSERT INTO `configuracao_empresa` (`id`, `nome_barbearia`, `slogan`, `cnpj`, `telefone`, `email`, `endereco`, `chave_pix_principal`, `taxa_cancelamento_percentual`, `horas_minimas_cancelamento`, `politica_cancelamento_texto`)
            VALUES (1, :nome, 'Tradição clássica, navalha afiada e corte moderno', '32.198.472/0001-55', '(11) 98765-4321', 'contato@cortenanavalha.com.br', 'Av. Paulista, 1500 - Bela Vista, São Paulo - SP', :pix, 20.00, 2, :politica)
            ON DUPLICATE KEY UPDATE
                `nome_barbearia` = VALUES(`nome_barbearia`),
                `chave_pix_principal` = VALUES(`chave_pix_principal`),
                `politica_cancelamento_texto` = COALESCE(`politica_cancelamento_texto`, VALUES(`politica_cancelamento_texto`))
        ");
        $stmtEmp->execute([
            ':nome'     => $nomeBarbearia ?: 'Corte na Navalha Barbearia',
            ':pix'      => $chavePix ?: 'pix@cortenanavalha.com.br',
            ':politica' => $politicaPadrao
        ]);

        // Usuários iniciais (Senha padrão: 123456)
        $senhaHash = '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y';
        $pdo->exec("
            INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `telefone`, `senha`, `perfil`, `especialidade`, `chave_pix`, `tipo_chave_pix`, `comissao_percentual`, `permite_catalogo_proprio`) VALUES
            (1, 'Carlos Oliveira (Dono)', 'dono@cortenanavalha.com.br', '111.222.333-44', '(11) 99999-0001', '{$senhaHash}', 'dono', 'Mestre Barbeiro e Gestor', 'dono@cortenanavalha.com.br', 'E-mail', 100.00, 1),
            (2, 'Lucas Navalha (Barbeiro)', 'lucas@cortenanavalha.com.br', '222.333.444-55', '(11) 99999-0002', '{$senhaHash}', 'barbeiro', 'Degradê, Navalhado e Barboterapia', 'lucas.pix@cortenanavalha.com.br', 'E-mail', 50.00, 1),
            (3, 'Marcos Fade (Barbeiro)', 'marcos@cortenanavalha.com.br', '333.444.555-66', '(11) 99999-0003', '{$senhaHash}', 'barbeiro', 'Cortes Clássicos, Pompadour e Freestyle', '11999990003', 'Telefone', 50.00, 0),
            (4, 'Mariana Silva (Recepção)', 'recepcao@cortenanavalha.com.br', '444.555.666-77', '(11) 99999-0004', '{$senhaHash}', 'recepcao', 'Atendimento e Gestão de Agendamentos', 'mariana.recep@gmail.com', 'E-mail', 0.00, 0),
            (5, 'João Santos (Cliente)', 'joao.cliente@gmail.com', '555.666.777-88', '(11) 98888-1234', '{$senhaHash}', 'cliente', NULL, NULL, NULL, 0.00, 0)
            ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);
        ");

        // Catálogo de Serviços
        $pdo->exec("
            INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco`, `tempo_minutos`, `icone`, `categoria`, `destaque`) VALUES
            (1, 'Corte Tradicional na Tesoura & Navalha', 'Corte com acabamento refinado na navalha e finalização com pomada modeladora.', 50.00, 40, 'bi-scissors', 'cabelo', 1),
            (2, 'Degradê / Fade Americano', 'Degradê milimétrico (Low, Mid ou High Fade) finalizado com navalhete e pós-barba.', 55.00, 45, 'bi-lightning-charge', 'cabelo', 1),
            (3, 'Barboterapia Completa com Toalha Quente', 'Alinhamento com navalha afiada, hidratação com óleos essenciais e toalha vaporizada relaxante.', 45.00, 35, 'bi-person-badge', 'barba', 1),
            (4, 'Combo Premium: Corte + Barba', 'O tratamento completo dos cavalheiros: corte de cabelo estilizado e barboterapia completa.', 85.00, 70, 'bi-star-fill', 'combo', 1),
            (5, 'Design de Sobrancelha na Navalha', 'Alinhamento preciso e simétrico da sobrancelha masculina utilizando lâmina navalhete descartável.', 20.00, 15, 'bi-eye', 'especial', 0),
            (6, 'Camuflagem de Fios Brancos & Pigmentação', 'Pigmentação natural para barba ou cabelo, realçando o contorno e rejuvenescendo a aparência.', 40.00, 30, 'bi-palette', 'especial', 0),
            (7, 'Lavagem Especial com Massagem Capilar', 'Shampoo refrescante de mentol com massagem revigorante no couro cabeludo.', 25.00, 20, 'bi-droplet', 'especial', 0)
            ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);
        ");

        // Configuração inicial de E-mail
        $pdo->exec("
            INSERT IGNORE INTO `configuracao_email` (`id`, `driver`, `host`, `porta`, `seguranca`, `usuario`, `senha`, `remetente_nome`, `remetente_email`, `ativo`)
            VALUES (1, 'gmail', 'smtp.gmail.com', 587, 'tls', '', '', 'Corte na Navalha Barbearia', 'contato@cortenanavalha.com.br', 0);
        ");

        // Templates de E-mail padrão
        $pdo->exec("
            INSERT IGNORE INTO `templates_email` (`slug`, `titulo`, `assunto`, `corpo_html`, `variaveis_disponiveis`) VALUES
            ('comprovante_pagamento', 'Comprovante de Pagamento', 'Comprovante de Pagamento - {NOME_BARBEARIA} (#{CODIGO_RESERVA})',
            '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;\"><h2 style=\"color: #dc2626; text-align: center; margin-top: 0;\">{NOME_BARBEARIA}</h2><hr style=\"border-color: #334155;\"><h3>Olá, {CLIENTE_NOME}!</h3><p>Seu pagamento foi confirmado com sucesso.</p><p><strong>Reserva:</strong> {CODIGO_RESERVA} | <strong>Valor:</strong> R$ {VALOR}</p></div>',
            '{CLIENTE_NOME}, {CODIGO_RESERVA}, {PROCEDIMENTOS}, {BARBEIRO_NOME}, {DATA_HORA}, {VALOR}, {METODO_PAGAMENTO}, {NOME_BARBEARIA}'),
            ('confirmacao_agendamento', 'Confirmação de Agendamento', 'Agendamento Confirmado - {NOME_BARBEARIA} (#{CODIGO_RESERVA})',
            '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;\"><h2 style=\"color: #dc2626; text-align: center; margin-top: 0;\">{NOME_BARBEARIA}</h2><hr style=\"border-color: #334155;\"><h3>Olá, {CLIENTE_NOME}!</h3><p>Seu horário está reservado para <strong>{DATA_HORA}</strong> com <strong>{BARBEIRO_NOME}</strong>.</p></div>',
            '{CLIENTE_NOME}, {CODIGO_RESERVA}, {PROCEDIMENTOS}, {BARBEIRO_NOME}, {DATA_HORA}, {VALOR}, {NOME_BARBEARIA}');
        ");

        $logs[] = "Dados iniciais (Barbearia, Equipe, Clientes e Catálogo de Serviços) importados.";

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

    // Muda o diretório de trabalho atual para a raiz antes de remover a pasta /install (evita bloqueio no Windows/Linux)
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
