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
$origem = trim($_POST['origem'] ?? 'web');
$clienteAnonimo = !empty($_POST['cliente_anonimo']) ? 1 : 0;
$clienteNomeAvulso = trim($_POST['cliente_nome_avulso'] ?? '');

if ($usuario['perfil'] === 'cliente') {
    $clienteId = $usuario['id'];
    $clienteBalcao = 0;
} else {
    // Agendamento realizado por Recepção ou Dono
    if ($clienteAnonimo || empty($_POST['cliente_id'])) {
        // Busca o usuário coringa de balcão
        $pdoTemp = getDBConnection();
        $stmtB = $pdoTemp->query("SELECT id FROM usuarios WHERE email = 'balcao@cortenanavalha.local' LIMIT 1");
        $uBalcao = $stmtB->fetch();
        $clienteId = $uBalcao ? (int)$uBalcao['id'] : $usuario['id'];
        $clienteBalcao = 1;
        $origem = 'balcao';
        if (empty($clienteNomeAvulso)) {
            $clienteNomeAvulso = 'Cliente Balcão (Não Identificado)';
        }
    } else {
        $clienteId = (int)$_POST['cliente_id'];
        $clienteBalcao = ($origem === 'balcao') ? 1 : 0;
    }
}

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

    // 1. Processar e validar serviços selecionados (tabela geral ou catálogo individual do barbeiro)
    $servicosValidos = [];
    $valorTotal = 0.0;

    foreach ($servicosIds as $sRaw) {
        $sRaw = trim((string)$sRaw);
        if (str_starts_with($sRaw, 'custom_')) {
            $cId = (int)str_replace('custom_', '', $sRaw);
            $stmtC = $pdo->prepare("SELECT id, nome, preco FROM barbeiro_servicos WHERE id = :id AND barbeiro_id = :b_id AND ativo = 1 LIMIT 1");
            $stmtC->execute([':id' => $cId, ':b_id' => $barbeiroId]);
            $item = $stmtC->fetch();
            if ($item) {
                $servicosValidos[] = [
                    'servico_id'          => null,
                    'barbeiro_servico_id' => $item['id'],
                    'nome'                => $item['nome'],
                    'preco'               => (float)$item['preco']
                ];
                $valorTotal += (float)$item['preco'];
            }
        } else {
            $gId = (int)str_replace('global_', '', $sRaw);
            $stmtG = $pdo->prepare("SELECT id, nome, preco FROM servicos WHERE id = :id AND ativo = 1 LIMIT 1");
            $stmtG->execute([':id' => $gId]);
            $item = $stmtG->fetch();
            if ($item) {
                $servicosValidos[] = [
                    'servico_id'          => $item['id'],
                    'barbeiro_servico_id' => null,
                    'nome'                => $item['nome'],
                    'preco'               => (float)$item['preco']
                ];
                $valorTotal += (float)$item['preco'];
            }
        }
    }

    if (empty($servicosValidos)) {
        throw new Exception('Nenhum serviço válido foi selecionado.');
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
    $taxaIsentaInicial = ($origem === 'balcao' || $clienteBalcao) ? 1 : 0;
    $sqlAgendamento = "
        INSERT INTO agendamentos (
            codigo_reserva, cliente_id, barbeiro_id, data_agendamento, horario,
            valor_total, metodo_pagamento, status_pagamento, status_atendimento,
            pix_copia_cola, pix_txid, observacoes, origem, cliente_balcao, cliente_nome_avulso, taxa_isenta
        ) VALUES (
            :codigo, :cliente_id, :barbeiro_id, :data_agendamento, :horario,
            :valor_total, :metodo_pagamento, 'pendente', 'agendado',
            :pix_copia_cola, :pix_txid, :observacoes, :origem, :cliente_balcao, :cliente_nome_avulso, :taxa_isenta
        )
    ";
    $stmtAgend = $pdo->prepare($sqlAgendamento);
    $stmtAgend->execute([
        ':codigo'              => $codigoReserva,
        ':cliente_id'          => $clienteId,
        ':barbeiro_id'         => $barbeiroId,
        ':data_agendamento'    => $dataAgendamento,
        ':horario'             => $horario,
        ':valor_total'         => $valorTotal,
        ':metodo_pagamento'    => $metodoPagamento,
        ':pix_copia_cola'      => $pixCopiaCola,
        ':pix_txid'            => $pixTxId,
        ':observacoes'         => $observacoes,
        ':origem'              => $origem,
        ':cliente_balcao'      => $clienteBalcao,
        ':cliente_nome_avulso' => !empty($clienteNomeAvulso) ? $clienteNomeAvulso : null,
        ':taxa_isenta'         => $taxaIsentaInicial
    ]);

    $agendamentoId = $pdo->lastInsertId();

    // 5. Inserir Itens do Agendamento
    $sqlItem = "INSERT INTO agendamento_itens (agendamento_id, servico_id, nome_servico, barbeiro_servico_id, preco_momento) VALUES (:agend_id, :serv_id, :nome_srv, :barb_srv_id, :preco)";
    $stmtItem = $pdo->prepare($sqlItem);
    foreach ($servicosValidos as $srv) {
        $stmtItem->execute([
            ':agend_id'     => $agendamentoId,
            ':serv_id'      => $srv['servico_id'],
            ':nome_srv'     => $srv['nome'],
            ':barb_srv_id'  => $srv['barbeiro_servico_id'],
            ':preco'        => $srv['preco']
        ]);
    }

    $pdo->commit();

    setMensagemToast('success', 'Agendamento Confirmado!', 'Horário reservado com sucesso no sistema (Reserva: ' . $codigoReserva . ').');

    if (!empty($_POST['redirect'])) {
        header('Location: ' . $_POST['redirect']);
    } elseif ($usuario['perfil'] === 'recepcao') {
        header('Location: ' . BASE_URL . '/recepcao/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/cliente/comprovante.php?codigo=' . urlencode($codigoReserva));
    }
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setMensagemToast('danger', 'Erro no Agendamento', 'Não foi possível concluir o agendamento: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/cliente/agendar.php');
    exit;
}
