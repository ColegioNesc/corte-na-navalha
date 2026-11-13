-- ========================================================
-- ATUALIZAÇÃO DO BANCO DE DADOS - REQUISITOS MODS.MD
-- ========================================================

USE `corte_na_navalha`;

-- 1. Novas colunas em configuracao_empresa
ALTER TABLE `configuracao_empresa`
    ADD COLUMN IF NOT EXISTS `cor_tema` VARCHAR(30) DEFAULT 'crimson',
    ADD COLUMN IF NOT EXISTS `tipo_catalogo` ENUM('global', 'individual') DEFAULT 'global',
    ADD COLUMN IF NOT EXISTS `metodo_pagamento_ativo` ENUM('pix_fixo', 'mercadopago') DEFAULT 'pix_fixo',
    ADD COLUMN IF NOT EXISTS `mp_public_key` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `mp_access_token` TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `mp_webhook_secret` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `mp_sandbox` TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `comissao_automatica` TINYINT(1) DEFAULT 0;

-- 2. Novas colunas em usuarios
ALTER TABLE `usuarios`
    ADD COLUMN IF NOT EXISTS `permite_catalogo_proprio` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `permissoes` TEXT DEFAULT NULL;

-- 2B. Novas colunas em agendamentos e agendamento_itens (garantia de compatibilidade em bases antigas)
ALTER TABLE `agendamentos`
    ADD COLUMN IF NOT EXISTS `origem` ENUM('web', 'balcao') DEFAULT 'web',
    ADD COLUMN IF NOT EXISTS `cliente_balcao` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `cliente_nome_avulso` VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `taxa_cancelamento` DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `valor_reembolsado` DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `taxa_isenta` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `cancelado_em` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `motivo_cancelamento` TEXT NULL;

ALTER TABLE `agendamento_itens`
    MODIFY COLUMN `servico_id` INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nome_servico` VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `barbeiro_servico_id` INT DEFAULT NULL;

-- 3. Tabela de serviços individuais do barbeiro
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

-- 4. Tabela de transações financeiras e comprovantes
CREATE TABLE IF NOT EXISTS `transacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_transacao` VARCHAR(50) NOT NULL UNIQUE,
    `agendamento_id` INT DEFAULT NULL,
    `tipo` ENUM('recebimento', 'comissao', 'estorno') NOT NULL DEFAULT 'recebimento',
    `metodo` ENUM('dinheiro', 'pix_fixo', 'mercadopago_pix', 'mercadopago_cartao', 'outro') NOT NULL DEFAULT 'dinheiro',
    `valor_total` DECIMAL(10,2) NOT NULL,
    `valor_recebido` DECIMAL(10,2) DEFAULT NULL,
    `troco` DECIMAL(10,2) DEFAULT 0.00,
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

-- 5. Tabela de configuração de E-mail (SMTP/POP/Gmail)
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

-- Registro padrão de e-mail se não existir
INSERT IGNORE INTO `configuracao_email` (`id`, `driver`, `host`, `porta`, `seguranca`, `usuario`, `senha`, `remetente_nome`, `remetente_email`, `ativo`)
VALUES (1, 'gmail', 'smtp.gmail.com', 587, 'tls', '', '', 'Corte na Navalha Barbearia', 'contato@cortenanavalha.com.br', 0);

-- 6. Tabela de templates personalizáveis de E-mail
CREATE TABLE IF NOT EXISTS `templates_email` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `titulo` VARCHAR(100) NOT NULL,
    `assunto` VARCHAR(200) NOT NULL,
    `corpo_html` LONGTEXT NOT NULL,
    `variaveis_disponiveis` VARCHAR(255) DEFAULT '',
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates Iniciais
INSERT INTO `templates_email` (`slug`, `titulo`, `assunto`, `corpo_html`, `variaveis_disponiveis`) VALUES
('comprovante_pagamento', 'Comprovante de Pagamento', 'Comprovante de Pagamento - {NOME_BARBEARIA} (#{CODIGO_RESERVA})',
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;">
    <h2 style="color: #dc2626; text-align: center; margin-top: 0;">{NOME_BARBEARIA}</h2>
    <p style="text-align: center; color: #94a3b8;">{SLOGAN}</p>
    <hr style="border-color: #334155;">
    <h3>Olá, {CLIENTE_NOME}!</h3>
    <p>Seu pagamento foi confirmado com sucesso. Abaixo estão os detalhes da sua transação:</p>
    <div style="background: #181920; padding: 15px; border-radius: 6px; margin: 20px 0;">
        <p><strong>Código da Reserva:</strong> {CODIGO_RESERVA}</p>
        <p><strong>Procedimento(s):</strong> {PROCEDIMENTOS}</p>
        <p><strong>Barbeiro:</strong> {BARBEIRO_NOME}</p>
        <p><strong>Data & Horário:</strong> {DATA} às {HORARIO}</p>
        <p><strong>Valor Total:</strong> <span style="color: #22c55e; font-size: 1.1em; font-weight: bold;">R$ {VALOR_TOTAL}</span></p>
        <p><strong>Forma de Pagamento:</strong> {METODO_PAGAMENTO}</p>
    </div>
    <p style="color: #94a3b8; font-size: 0.9em; text-align: center;">Obrigado pela preferência! Aguardamos você para manter seu estilo sempre afiado na navalha.</p>
</div>',
'{CLIENTE_NOME}, {NOME_BARBEARIA}, {SLOGAN}, {CODIGO_RESERVA}, {PROCEDIMENTOS}, {BARBEIRO_NOME}, {DATA}, {HORARIO}, {VALOR_TOTAL}, {METODO_PAGAMENTO}'),

('agendamento_confirmado', 'Confirmação de Agendamento', 'Agendamento Confirmado - {NOME_BARBEARIA}',
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;">
    <h2 style="color: #dc2626; text-align: center; margin-top: 0;">{NOME_BARBEARIA}</h2>
    <p style="text-align: center; color: #94a3b8;">{SLOGAN}</p>
    <hr style="border-color: #334155;">
    <h3>Olá, {CLIENTE_NOME}!</h3>
    <p>Seu horário está confirmado na nossa agenda!</p>
    <div style="background: #181920; padding: 15px; border-radius: 6px; margin: 20px 0;">
        <p><strong>Código da Reserva:</strong> {CODIGO_RESERVA}</p>
        <p><strong>Barbeiro:</strong> {BARBEIRO_NOME}</p>
        <p><strong>Procedimento:</strong> {PROCEDIMENTOS}</p>
        <p><strong>Data:</strong> {DATA}</p>
        <p><strong>Horário:</strong> {HORARIO}</p>
        <p><strong>Endereço:</strong> {ENDERECO_BARBEARIA}</p>
    </div>
    <p style="color: #94a3b8; font-size: 0.9em; text-align: center;">Caso precise remarcar, entre em contato pelo telefone {TELEFONE_BARBEARIA}.</p>
</div>',
'{CLIENTE_NOME}, {NOME_BARBEARIA}, {SLOGAN}, {CODIGO_RESERVA}, {PROCEDIMENTOS}, {BARBEIRO_NOME}, {DATA}, {HORARIO}, {ENDERECO_BARBEARIA}, {TELEFONE_BARBEARIA}'),

('redefinicao_senha', 'Redefinição de Senha', 'Recuperação de Senha - {NOME_BARBEARIA}',
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;">
    <h2 style="color: #dc2626; text-align: center; margin-top: 0;">{NOME_BARBEARIA}</h2>
    <hr style="border-color: #334155;">
    <h3>Olá, {CLIENTE_NOME}!</h3>
    <p>Você solicitou a redefinição da sua senha de acesso ao sistema.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{LINK_RECUPERACAO}" style="background: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Redefinir Minha Senha</a>
    </div>
    <p style="color: #94a3b8; font-size: 0.85em;">Se você não solicitou este e-mail, por favor desconsidere esta mensagem com segurança.</p>
</div>',
'{CLIENTE_NOME}, {NOME_BARBEARIA}, {LINK_RECUPERACAO}')
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

