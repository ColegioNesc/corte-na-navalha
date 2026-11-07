<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDBConnection();

try {
    $pdo->exec("
        ALTER TABLE configuracao_empresa 
        ADD COLUMN IF NOT EXISTS taxa_cancelamento_percentual DECIMAL(5,2) DEFAULT 20.00,
        ADD COLUMN IF NOT EXISTS horas_minimas_cancelamento INT DEFAULT 2,
        ADD COLUMN IF NOT EXISTS politica_cancelamento_texto TEXT NULL;
    ");
} catch (Exception $e) {}

try {
    $pdo->exec("
        ALTER TABLE agendamentos 
        ADD COLUMN IF NOT EXISTS taxa_cancelamento DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS valor_reembolsado DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS taxa_isenta TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS cancelado_em DATETIME NULL,
        ADD COLUMN IF NOT EXISTS motivo_cancelamento VARCHAR(255) NULL;
    ");
} catch (Exception $e) {}

echo "MIGRACAO_CANCELAMENTO_OK\n";

