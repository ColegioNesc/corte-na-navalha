<?php
/**
 * API - Obtenção de QR Code PIX e Verificação de Status do Pagamento
 * Permite escutar a liquidação em tempo real
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$agendamentoId = (int)($_GET['id'] ?? 0);
$codigoReserva = trim($_GET['codigo'] ?? '');

if ($agendamentoId <= 0 && empty($codigoReserva)) {
    echo json_encode(['success' => false, 'message' => 'Identificador de agendamento não informado.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    if ($agendamentoId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM agendamentos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $agendamentoId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM agendamentos WHERE codigo_reserva = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigoReserva]);
    }
    
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado.']);
        exit;
    }
    
    $pixData = obterOuGerarPixAgendamento($row['id'], $pdo);
    if (!$pixData) {
        echo json_encode(['success' => false, 'message' => 'Não foi possível gerar dados de pagamento PIX.']);
        exit;
    }
    
    // Obter dados complementares
    $stmtDetalhes = $pdo->prepare("
        SELECT a.cliente_id, a.barbeiro_id, a.data_agendamento, a.horario,
               c.nome as cliente_nome, b.nome as barbeiro_nome,
               GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
        FROM agendamentos a
        JOIN usuarios c ON a.cliente_id = c.id
        JOIN usuarios b ON a.barbeiro_id = b.id
        JOIN agendamento_itens ai ON a.id = ai.agendamento_id
        JOIN servicos s ON ai.servico_id = s.id
        WHERE a.id = :id
        GROUP BY a.id
    ");
    $stmtDetalhes->execute([':id' => $row['id']]);
    $detalhes = $stmtDetalhes->fetch();
    
    echo json_encode([
        'success'            => true,
        'agendamento_id'     => $pixData['id'],
        'codigo_reserva'     => $pixData['codigo_reserva'],
        'valor_total'        => $pixData['valor_total'],
        'valor_formatado'    => 'R$ ' . number_format($pixData['valor_total'], 2, ',', '.'),
        'status_pagamento'   => $pixData['status_pagamento'],
        'status_atendimento' => $pixData['status_atendimento'],
        'pix_copia_cola'     => $pixData['pix_copia_cola'],
        'qr_code_url'        => $pixData['qr_code_url'],
        'cliente_nome'       => $detalhes['cliente_nome'] ?? 'Cliente',
        'barbeiro_nome'      => $detalhes['barbeiro_nome'] ?? 'Barbeiro',
        'procedimentos'      => $detalhes['procedimentos'] ?? 'Procedimentos da Barbearia'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()]);
    exit;
}
