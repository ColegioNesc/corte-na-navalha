<?php
/**
 * Salvar Agendamento - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente', 'recepcao', 'dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}

$usuario = getUsuarioLogado();
$clienteId = ($usuario['perfil'] === 'cliente') ? $usuario['id'] : (int)($_POST['cliente_id'] ?? $usuario['id']);
$barbeiroId = (int)($_POST['barbeiro_id'] ?? 0);
$dataAgendamento = trim($_POST['data_agendamento'] ?? '');
$horario = trim($_POST['horario'] ?? '');
$metodoPagamento = trim($_POST['metodo_pagamento'] ?? 'pix');
$observacoes = trim($_POST['observacoes'] ?? '');
$servicosIds = $_POST['servicos'] ?? [];

// Normalizar método de pagamento
if (!in_array($metodoPagamento, ['pix', 'recepcao'])) {
    $metodoPagamento = 'pix';
}

if (empty($servicosIds) || !is_array($servicosIds)) {
    setMensagemToast('warning', 'Selecione um Procedimento', 'Você deve escolher ao menos um serviço para o agendamento.');
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}

if (empty($barbeiroId) || empty($dataAgendamento) || empty($horario)) {
    setMensagemToast('warning', 'Campos Incompletos', 'Informe o barbeiro, data e horário do atendimento.');
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}

// Validar se data não é no passado
if ($dataAgendamento < date('Y-m-d')) {
    setMensagemToast('warning', 'Data Inválida', 'A data do agendamento não pode ser anterior à data de hoje.');
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // 1. Calcular valor total e validar serviços selecionados
    $inQuery = implode(',', array_fill(0, count($servicosIds), '?'));
    $stmtSrv = $pdo->prepare("SELECT id, nome, preco FROM servicos WHERE id IN ($inQuery) AND ativo = 1");
    $stmtSrv->execute(array_values($servicosIds));
    $servicosValidos = $stmtSrv->fetchAll();

    if (empty($servicosValidos)) {
        throw new Exception('Nenhum serviço válido foi selecionado.');
    }

    $valorTotal = 0.0;
    foreach ($servicosValidos as $srv) {
        $valorTotal += (float)$srv['preco'];
    }

    // 2. Gerar código único de reserva
    $codigoReserva = 'CN-' . date('Ymd', strtotime($dataAgendamento)) . '-' . strtoupper(substr(uniqid(), -4));

    // 3. Gerar PIX Copia e Cola se for método PIX
    $pixCopiaCola = null;
    $pixTxId = null;
    if ($metodoPagamento === 'pix') {
        $empresa = getEmpresaConfig();
        $chavePix = $empresa['chave_pix_principal'] ?? 'pix@cortenanavalha.com.br';
        $pixTxId = 'CN' . time();
        $pixCopiaCola = gerarPixCopiaECola(
            $chavePix,
            $empresa['nome_barbearia'] ?? 'Corte na Navalha',
            'SAO PAULO',
            $valorTotal,
            $pixTxId
        );
    }

    // 4. Inserir Agendamento
    $sqlAgendamento = "
        INSERT INTO agendamentos (
            codigo_reserva, cliente_id, barbeiro_id, data_agendamento, horario,
            valor_total, metodo_pagamento, status_pagamento, status_atendimento,
            pix_copia_cola, pix_txid, observacoes
        ) VALUES (
            :codigo, :cliente_id, :barbeiro_id, :data_agendamento, :horario,
            :valor_total, :metodo_pagamento, 'pendente', 'agendado',
            :pix_copia_cola, :pix_txid, :observacoes
        )
    ";
    $stmtAgend = $pdo->prepare($sqlAgendamento);
    $stmtAgend->execute([
        ':codigo'           => $codigoReserva,
        ':cliente_id'       => $clienteId,
        ':barbeiro_id'      => $barbeiroId,
        ':data_agendamento' => $dataAgendamento,
        ':horario'          => $horario,
        ':valor_total'      => $valorTotal,
        ':metodo_pagamento' => $metodoPagamento,
        ':pix_copia_cola'   => $pixCopiaCola,
        ':pix_txid'         => $pixTxId,
        ':observacoes'      => $observacoes
    ]);

    $agendamentoId = $pdo->lastInsertId();

    // 5. Inserir Itens do Agendamento
    $sqlItem = "INSERT INTO agendamento_itens (agendamento_id, servico_id, preco_momento) VALUES (:agend_id, :serv_id, :preco)";
    $stmtItem = $pdo->prepare($sqlItem);
    foreach ($servicosValidos as $srv) {
        $stmtItem->execute([
            ':agend_id' => $agendamentoId,
            ':serv_id'  => $srv['id'],
            ':preco'    => $srv['preco']
        ]);
    }

    $pdo->commit();

    setMensagemToast('success', 'Agendamento Confirmado!', 'Seu horário foi reservado com sucesso. Veja os detalhes e o comprovante abaixo.');
    header('Location: ' . BASE_URL . '/cliente/comprovante.php?codigo=' . urlencode($codigoReserva));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setMensagemToast('danger', 'Erro no Agendamento', 'Não foi possível concluir o agendamento: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}
