<?php
/**
 * Painel da Recepção - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['recepcao', 'dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();

$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$barbeiroFiltro = (int)($_GET['barbeiro_id'] ?? 0);

// Buscar todos os barbeiros para filtro
$stmtBarbeiros = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'barbeiro' AND ativo = 1 ORDER BY nome ASC");
$barbeiros = $stmtBarbeiros->fetchAll();

// Buscar agendamentos conforme filtros
$params = [':data' => $dataFiltro];
$whereBarbeiro = "";
if ($barbeiroFiltro > 0) {
    $whereBarbeiro = " AND a.barbeiro_id = :barbeiro_id ";
    $params[':barbeiro_id'] = $barbeiroFiltro;
}

$sql = "
    SELECT a.*, 
           c.nome as cliente_nome, c.telefone as cliente_telefone, c.cpf as cliente_cpf,
           b.nome as barbeiro_nome,
           GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN usuarios b ON a.barbeiro_id = b.id
    JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    WHERE a.data_agendamento = :data $whereBarbeiro
    GROUP BY a.id
    ORDER BY a.horario ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll();

// Métricas do dia da recepção
$totalClientes = count($agendamentos);
$totalReceberNaHora = 0;
$totalQuitados = 0;
$faturamentoDia = 0.0;

foreach ($agendamentos as $item) {
    if ($item['status_pagamento'] === 'pago') {
        $totalQuitados++;
        $faturamentoDia += (float)$item['valor_total'];
    } elseif ($item['metodo_pagamento'] === 'recepcao') {
        $totalReceberNaHora++;
    }
}

$paginaTitulo = 'Painel da Recepção | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho da Recepção -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Painel de Atendimento da Recepção</h4>
                        <p class="text-secondary small mb-0">Controle de fluxo de balcão, check-ins e pagamentos presenciais</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold py-2 px-3">
                    <i class="bi bi-plus-circle me-1"></i> Agendar Cliente no Balcão
                </a>
            </div>
        </div>
    </div>

    <!-- Indicadores do Dia -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Agendamentos no Dia</small>
                <h3 class="text-white fw-bold mb-0"><?= $totalClientes ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">A Pagar na Recepção</small>
                <h3 class="text-warning fw-bold mb-0"><?= $totalReceberNaHora ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Pagamentos Confirmados</small>
                <h3 class="text-success fw-bold mb-0"><?= $totalQuitados ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Faturamento Quitado</small>
                <h3 class="text-gold fw-bold mb-0">R$ <?= number_format($faturamentoDia, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="barber-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="data" class="form-label">Data de Consulta</label>
                <input type="date" class="form-control" id="data" name="data" value="<?= htmlspecialchars($dataFiltro) ?>">
            </div>
            <div class="col-md-5">
                <label for="barbeiro_id" class="form-label">Filtrar por Barbeiro</label>
                <select class="form-select" id="barbeiro_id" name="barbeiro_id">
                    <option value="0">Todos os Barbeiros</option>
                    <?php foreach ($barbeiros as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($b['id'] == $barbeiroFiltro) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-gold w-100 py-2">
                    <i class="bi bi-funnel me-1"></i> Filtrar Agenda
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Agendamentos do Dia -->
    <div class="barber-card p-0">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold">
                <i class="bi bi-list-check text-gold me-2"></i>Atendimentos de <?= date('d/m/Y', strtotime($dataFiltro)) ?>
            </h5>
            <span class="text-secondary small">Total: <?= count($agendamentos) ?></span>
        </div>

        <?php if (empty($agendamentos)): ?>
            <div class="p-5 text-center">
                <i class="bi bi-calendar-x text-muted fs-1 mb-2 d-block"></i>
                <h6 class="text-white">Nenhum atendimento encontrado para os filtros selecionados.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead style="background: #111319;">
                        <tr>
                            <th class="ps-3">Horário</th>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Barbeiro</th>
                            <th>Procedimento(s)</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th>Status Pagto</th>
                            <th>Status Atendimento</th>
                            <th class="text-end pe-3">Ações de Balcão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $ag): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-gold fs-6"><?= substr($ag['horario'], 0, 5) ?></td>
                                <td class="font-monospace small"><?= htmlspecialchars($ag['codigo_reserva']) ?></td>
                                <td>
                                    <strong class="text-white d-block"><?= htmlspecialchars($ag['cliente_nome']) ?></strong>
                                    <small class="text-secondary"><?= htmlspecialchars($ag['cliente_telefone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($ag['barbeiro_nome']) ?></td>
                                <td class="small"><?= htmlspecialchars($ag['procedimentos']) ?></td>
                                <td class="fw-bold">R$ <?= number_format($ag['valor_total'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-capitalize">
                                        <?= ($ag['metodo_pagamento'] === 'pix') ? 'PIX' : 'Recepção' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $ag['status_pagamento'] ?>">
                                        <?= strtoupper($ag['status_pagamento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $ag['status_atendimento'] ?>">
                                        <?= str_replace('_', ' ', $ag['status_atendimento']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if ($ag['status_pagamento'] === 'pendente'): ?>
                                            <!-- Botão de Baixa / Receber no Balcão -->
                                            <form action="<?= BASE_URL ?>/actions/agendamento_pagar.php" method="POST" class="d-inline">
                                                <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                                <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Confirmar Recebimento do Pagamento">
                                                    <i class="bi bi-cash-coin me-1"></i> Receber
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($ag['status_atendimento'] === 'agendado'): ?>
                                            <!-- Check-in do Cliente -->
                                            <form action="<?= BASE_URL ?>/actions/agendamento_status.php" method="POST" class="d-inline">
                                                <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                                <input type="hidden" name="status" value="em_atendimento">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                                <button type="submit" class="btn btn-sm btn-warning py-1 px-2" title="Check-in: Cliente chegou e sentou na cadeira">
                                                    <i class="bi bi-box-arrow-in-right"></i> Check-in
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Ver Comprovante">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
