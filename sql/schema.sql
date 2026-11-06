-- ========================================================
-- SISTEMA CORTE NA NAVALHA - BANCO DE DADOS OFICIAL
-- ========================================================

CREATE DATABASE IF NOT EXISTS `corte_na_navalha` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `corte_na_navalha`;

-- 1. TABELA DE CONFIGURAÇÕES DA EMPRESA (DONO)
CREATE TABLE IF NOT EXISTS `configuracao_empresa` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome_barbearia` VARCHAR(150) NOT NULL DEFAULT 'Corte na Navalha Barbearia',
    `slogan` VARCHAR(255) DEFAULT 'Tradição, estilo e precisão em cada detalhe',
    `cnpj` VARCHAR(20) DEFAULT '12.345.678/0001-90',
    `telefone` VARCHAR(20) DEFAULT '(11) 98765-4321',
    `email` VARCHAR(100) DEFAULT 'contato@cortenanavalha.com.br',
    `endereco` VARCHAR(255) DEFAULT 'Rua dos Barbeiros, 777 - Centro, São Paulo - SP',
    `horario_abertura` TIME DEFAULT '08:00:00',
    `horario_fechamento` TIME DEFAULT '20:00:00',
    `chave_pix_principal` VARCHAR(100) DEFAULT 'pix@cortenanavalha.com.br',
    `tipo_chave_pix` VARCHAR(30) DEFAULT 'E-mail',
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE USUÁRIOS (CLIENTE, BARBEIRO, RECEPCAO, DONO)
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
    `ativo` TINYINT(1) DEFAULT 1,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE SERVIÇOS E PROCEDIMENTOS
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

-- 4. TABELA DE AGENDAMENTOS
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
    `recebido_por_id` INT DEFAULT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_agendamento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agendamento_barbeiro` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_agendamento_recepcao` FOREIGN KEY (`recebido_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE ITENS DO AGENDAMENTO (PERMITE ESCOLHER MAIS DE UM SERVIÇO, EX: CORTE + BARBA)
CREATE TABLE IF NOT EXISTS `agendamento_itens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agendamento_id` INT NOT NULL,
    `servico_id` INT NOT NULL,
    `preco_momento` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_item_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_item_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA DE FEEDBACK E AVALIAÇÃO DOS CLIENTES
CREATE TABLE IF NOT EXISTS `avaliacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agendamento_id` INT NOT NULL UNIQUE,
    `cliente_id` INT NOT NULL,
    `barbeiro_id` INT NOT NULL,
    `estrelas` TINYINT NOT NULL CHECK (`estrelas` BETWEEN 1 AND 5),
    `comentario` TEXT,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_avaliacao_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_avaliacao_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_avaliacao_barbeiro` FOREIGN KEY (`barbeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA DE PAGAMENTOS/REPASSES AOS FUNCIONÁRIOS (DONO -> FUNCIONÁRIO)
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

-- ========================================================
-- DADOS INICIAIS DE EXEMPLO (SEMENTE)
-- ========================================================

-- Inserir Configuração da Barbearia
INSERT INTO `configuracao_empresa` (`id`, `nome_barbearia`, `slogan`, `cnpj`, `telefone`, `email`, `endereco`, `chave_pix_principal`)
VALUES (1, 'Corte na Navalha Barbearia', 'Tradição clássica, navalha afiada e corte moderno', '32.198.472/0001-55', '(11) 98765-4321', 'contato@cortenanavalha.com.br', 'Av. Paulista, 1500 - Bela Vista, São Paulo - SP', 'pix@cortenanavalha.com.br')
ON DUPLICATE KEY UPDATE `nome_barbearia` = VALUES(`nome_barbearia`);

-- Senha padrão para todos os testes: 123456
-- Gerado via password_hash('123456', PASSWORD_DEFAULT)
-- $2y$10$wE99wJkL8xU/kGgJt6XWteF.94jJqjL992q7090b8oR4Qf7e0iVce

-- Inserir Usuários dos 4 Perfis
INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `telefone`, `senha`, `perfil`, `especialidade`, `chave_pix`, `tipo_chave_pix`, `comissao_percentual`) VALUES
(1, 'Carlos Oliveira (Dono)', 'dono@cortenanavalha.com.br', '111.222.333-44', '(11) 99999-0001', '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y', 'dono', 'Mestre Barbeiro e Gestor', 'dono@cortenanavalha.com.br', 'E-mail', 100.00),
(2, 'Lucas Navalha (Barbeiro)', 'lucas@cortenanavalha.com.br', '222.333.444-55', '(11) 99999-0002', '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y', 'barbeiro', 'Degradê, Navalhado e Barboterapia', 'lucas.pix@cortenanavalha.com.br', 'E-mail', 50.00),
(3, 'Marcos Fade (Barbeiro)', 'marcos@cortenanavalha.com.br', '333.444.555-66', '(11) 99999-0003', '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y', 'barbeiro', 'Cortes Clássicos, Pompadour e Freestyle', '11999990003', 'Telefone', 50.00),
(4, 'Mariana Silva (Recepção)', 'recepcao@cortenanavalha.com.br', '444.555.666-77', '(11) 99999-0004', '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y', 'recepcao', 'Atendimento e Gestão de Agendamentos', 'mariana.recep@gmail.com', 'E-mail', 0.00),
(5, 'João Santos (Cliente)', 'joao.cliente@gmail.com', '555.666.777-88', '(11) 98888-1234', '$2y$10$q.E3n6G.Nss4wcbuB4CeWOVFmC8PgVkpWDqnZZzcnxg6z3kpx/I8y', 'cliente', NULL, NULL, NULL, 0.00)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `senha` = VALUES(`senha`);

-- Inserir Tabela de Serviços e Procedimentos
INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco`, `tempo_minutos`, `icone`, `categoria`, `destaque`) VALUES
(1, 'Corte Tradicional na Tesoura & Navalha', 'Corte com acabamento refinado na navalha e finalização com pomada modeladora.', 50.00, 40, 'bi-scissors', 'cabelo', 1),
(2, 'Degradê / Fade Americano', 'Degradê milimétrico (Low, Mid ou High Fade) finalizado com navalhete e pós-barba.', 55.00, 45, 'bi-lightning-charge', 'cabelo', 1),
(3, 'Barboterapia Completa com Toalha Quente', 'Alinhamento com navalha afiada, hidratação com óleos essenciais e toalha vaporizada relaxante.', 45.00, 35, 'bi-person-badge', 'barba', 1),
(4, 'Combo Premium: Corte + Barba', 'O tratamento completo dos cavalheiros: corte de cabelo estilizado e barboterapia completa.', 85.00, 70, 'bi-star-fill', 'combo', 1),
(5, 'Design de Sobrancelha na Navalha', 'Alinhamento preciso e simétrico da sobrancelha masculina utilizando lâmina navalhete descartável.', 20.00, 15, 'bi-eye', 'especial', 0),
(6, 'Camuflagem de Fios Brancos & Pigmentação', 'Pigmentação natural para barba ou cabelo, realçando o contorno e rejuvenescendo a aparência.', 40.00, 30, 'bi-palette', 'especial', 0),
(7, 'Lavagem Especial com Massagem Capilar', 'Shampoo refrescante de mentol com massagem revigorante no couro cabeludo.', 25.00, 20, 'bi-droplet', 'especial', 0)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Inserir Agendamentos Iniciais para Demonstração Realista
INSERT INTO `agendamentos` (`id`, `codigo_reserva`, `cliente_id`, `barbeiro_id`, `data_agendamento`, `horario`, `valor_total`, `metodo_pagamento`, `status_pagamento`, `status_atendimento`, `pix_copia_cola`, `pix_pago_em`, `observacoes`) VALUES
(1, 'CN-20261016-001', 5, 2, CURDATE(), '09:00:00', 85.00, 'pix', 'pago', 'concluido', '00020126580014br.gov.bcb.pix0136pix@cortenanavalha.com.br520400005303986540585.005802BR5925Corte na Navalha Barbearia6009SAO PAULO62070503***6304ABCD', NOW(), 'Cliente pediu capricho na toalha quente.'),
(2, 'CN-20261016-002', 5, 3, CURDATE(), '11:00:00', 55.00, 'recepcao', 'pendente', 'agendado', NULL, NULL, 'Pagamento será feito no balcão.')
ON DUPLICATE KEY UPDATE `codigo_reserva` = VALUES(`codigo_reserva`);

-- Inserir Itens dos Agendamentos
INSERT INTO `agendamento_itens` (`agendamento_id`, `servico_id`, `preco_momento`) VALUES
(1, 4, 85.00),
(2, 2, 55.00)
ON DUPLICATE KEY UPDATE `preco_momento` = VALUES(`preco_momento`);

-- Inserir Avaliação Inicial
INSERT INTO `avaliacoes` (`agendamento_id`, `cliente_id`, `barbeiro_id`, `estrelas`, `comentario`) VALUES
(1, 5, 2, 5, 'Atendimento impecável! O Lucas manda muito bem no degradê e a toalha quente é outro nível.')
ON DUPLICATE KEY UPDATE `estrelas` = VALUES(`estrelas`);
