-- Schema para a aplicação Alarme a Distância
-- Banco de Dados MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS `alarme_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `alarme_db`;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `raio_padrao` INT DEFAULT 300,
    `tipo_alarme_padrao` ENUM('som', 'vibracao', 'ambos') DEFAULT 'ambos',
    `som_alarme` VARCHAR(50) DEFAULT 'sirene',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Viagens / Monitoramentos
CREATE TABLE IF NOT EXISTS `viagens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NULL,
    `destino_nome` VARCHAR(255) NOT NULL,
    `destino_lat` DECIMAL(10, 8) NOT NULL,
    `destino_lng` DECIMAL(11, 8) NOT NULL,
    `raio_metros` INT NOT NULL DEFAULT 300,
    `tipo_alarme` ENUM('som', 'vibracao', 'ambos') DEFAULT 'ambos',
    `som_alarme` VARCHAR(50) DEFAULT 'sirene',
    `status` ENUM('ativo', 'pausado', 'concluido', 'cancelado') DEFAULT 'ativo',
    `acionado` TINYINT(1) DEFAULT 0,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `concluido_em` TIMESTAMP NULL,
    INDEX `idx_usuario` (`usuario_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_viagens_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Destinos Favoritos
CREATE TABLE IF NOT EXISTS `destinos_favoritos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `titulo` VARCHAR(100) NOT NULL,
    `endereco` VARCHAR(255) NOT NULL,
    `lat` DECIMAL(10, 8) NOT NULL,
    `lng` DECIMAL(11, 8) NOT NULL,
    `raio_preferido` INT DEFAULT 300,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usuario_fav` (`usuario_id`),
    CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
