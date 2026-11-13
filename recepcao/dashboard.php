<?php
/**
 * Painel da Recepção - Barbearia Corte na Navalha
 * Fluxo de Balcão, QR Code de Cadastro Rápido, Agendamento Não Identificado, Caixa e Isenção de Cancelamento
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['recepcao', 'dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$taxaPadraoCDC = (float)($empresa['taxa_cancelamento_percentual'] ?? 20.0);
$horasMinimasCDC = (float)($empresa['horas_minimas_cancelamento'] ?? 2.0);

$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$barbeiroFiltro = (int)($_GET['barbeiro_id'] ?? 0);

// Buscar todos os barbeiros ativos
$stmtBarbeiros = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'barbeiro' AND ativo = 1 ORDER BY nome ASC");
$barbeiros = $stmtBarbeiros->fetchAll();

// Buscar clientes cadastrados para o balcão
$stmtClientes = $pdo->query("SELECT id, nome, email, cpf, telefone FROM usuarios WHERE perfil = 'cliente' AND email != 'balcao@cortenanavalha.local' ORDER BY nome ASC");
$clientesCadastrados = $stmtClientes->fetchAll();

// Buscar procedimentos gerais
$stmtServicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC");
$servicosGerais = $stmtServicos->fetchAll();

// Buscar agendamentos conforme filtros
$params = [':data' => $dataFiltro];
$whereBarbeiro = "";
if ($barbeiroFiltro > 0) {
    $whereBarbeiro = " AND a.barbeiro_id = :barbeiro_id ";
    $params[':barbeiro_id'] = $barbeiroFiltro;
}

$sql = "
    SELECT a.*, 
           c.nome as cliente_nome_cadastrado, c.telefone as cliente_telefone, c.cpf as cliente_cpf,
           b.nome as barbeiro_nome,
           GROUP_CONCAT(DISTINCT COALESCE(ai.nome_servico, s.nome) SEPARATOR ' + ') as procedimentos
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN usuarios b ON a.barbeiro_id = b.id
    LEFT JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    LEFT JOIN servicos s ON ai.servico_id = s.id
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

$urlCadastroRapido = BASE_URL . '/cadastro_rapido.php';
$qrCodeCadastroUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($urlCadastroRapido);

$paginaTitulo = 'Painel da Recepção | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho da Recepção com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar para A Barbearia">
                        <i class="bi bi-arrow-left"></i> A Barbearia
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Painel de Atendimento da Recepção</h4>
                        <p class="text-secondary small mb-0">Controle de fluxo de balcão, check-ins, caixa e cancelamentos com isenção</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="abrirModalQrBalcao()">
                    <i class="bi bi-qr-code-scan me-1"></i> QR Code Cadastro
                </button>
                <button type="button" class="btn btn-gold btn-sm py-2 px-3" onclick="ativarAbaBalcao()">
                    <i class="bi bi-plus-circle me-1"></i> Agendamento Balcão
                </button>
            </div>
        </div>
    </div>

    <!-- Indicadores Rápidos -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Agendamentos no Dia</small>
                <h3 class="text-white fw-bold mb-0"><?= $totalClientes ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">A Pagar no Balcão</small>
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

    <!-- Abas de Navegação (UX Sem Poluição) -->
    <ul class="nav nav-pills mb-4 nav-fill p-2 rounded-3 bg-dark border border-secondary border-opacity-25" id="recepcaoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active py-2 text-uppercase fw-semibold small" id="tab-agenda-btn" data-bs-toggle="pill" data-bs-target="#tab-agenda" type="button" role="tab" aria-selected="true">
                <i class="bi bi-list-check me-1 text-gold"></i> Atendimentos do Dia (<?= date('d/m', strtotime($dataFiltro)) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-caixa-btn" data-bs-toggle="pill" data-bs-target="#tab-caixa" type="button" role="tab" aria-selected="false">
                <i class="bi bi-cash-coin me-1 text-success"></i> Caixa & Recebimentos Presenciais
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-balcao-btn" data-bs-toggle="pill" data-bs-target="#tab-balcao" type="button" role="tab" aria-selected="false">
                <i class="bi bi-person-plus me-1 text-danger"></i> Agendamento Rápido no Balcão
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Abas -->
    <div class="tab-content" id="recepcaoTabsContent">
        
        <!-- ABA 1: Atendimentos do Dia -->
        <div class="tab-pane fade show active" id="tab-agenda" role="tabpanel" aria-labelledby="tab-agenda-btn">
            <!-- Filtros de Busca e Pesquisa em Tempo Real -->
            <div class="barber-card p-3 mb-4">
                <form method="GET" class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label for="data" class="form-label small text-secondary">Data de Consulta</label>
                        <input type="date" class="form-control form-control-sm" id="data" name="data" value="<?= htmlspecialchars($dataFiltro) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="barbeiro_id" class="form-label small text-secondary">Filtrar por Barbeiro</label>
                        <select class="form-select form-select-sm" id="barbeiro_id" name="barbeiro_id">
                            <option value="0">Todos os Barbeiros</option>
                            <?php foreach ($barbeiros as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($b['id'] == $barbeiroFiltro) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-red btn-sm w-100 py-1 fw-bold">
                            <i class="bi bi-funnel me-1"></i> Filtrar Agenda
                        </button>
                    </div>
                </form>

                <div class="pt-2 border-top border-secondary border-opacity-25">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-danger"><i class="bi bi-search"></i></span>
                        <input type="text" id="buscaRecepcao" class="form-control form-control-sm" placeholder="Buscar por cliente, CPF, telefone ou código..." oninput="filtrarTabelaRecepcao()">
                    </div>
                </div>
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
                                <?php foreach ($agendamentos as $ag): 
                                    $ehBalcao = ($ag['origem'] === 'balcao' || !empty($ag['cliente_balcao']));
                                    $nomeExibicao = !empty($ag['cliente_nome_avulso']) 
                                        ? $ag['cliente_nome_avulso'] 
                                        : ($ehBalcao ? 'Cliente Balcão (Não Identificado)' : $ag['cliente_nome_cadastrado']);
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-gold fs-6"><?= substr($ag['horario'], 0, 5) ?></td>
                                        <td class="font-monospace small">
                                            <?= htmlspecialchars($ag['codigo_reserva']) ?>
                                            <?php if ($ehBalcao): ?>
                                                <span class="badge bg-secondary text-white d-block mt-1" style="font-size: 0.65rem;">Balcão</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-white d-block"><?= htmlspecialchars($nomeExibicao) ?></strong>
                                            <?php if (!$ehBalcao && !empty($ag['cliente_telefone'])): ?>
                                                <small class="text-secondary"><?= htmlspecialchars($ag['cliente_telefone']) ?></small>
                                            <?php elseif ($ehBalcao): ?>
                                                <small class="text-muted" style="font-size: 0.72rem;">Atendimento no Balcão</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($ag['barbeiro_nome']) ?></td>
                                        <td class="small"><?= htmlspecialchars($ag['procedimentos'] ?? 'Procedimento padrão') ?></td>
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
                                                    <!-- Cobrança com QR Code PIX -->
                                                    <button type="button" class="btn btn-sm btn-danger py-1 px-2 fw-bold" onclick="abrirModalQrCode(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($nomeExibicao)) ?>', '<?= number_format($ag['valor_total'], 2, ',', '.') ?>', '<?= htmlspecialchars(addslashes($ag['procedimentos'] ?? 'Procedimento')) ?>', '<?= $ag['codigo_reserva'] ?>')" title="Apresentar QR Code para Pagamento">
                                                        <i class="bi bi-qr-code me-1"></i> PIX
                                                    </button>

                                                    <!-- Caixa com Troco -->
                                                    <button type="button" class="btn btn-sm btn-success py-1 px-2 fw-semibold" onclick="abrirModalCaixa(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($nomeExibicao)) ?>', <?= (float)$ag['valor_total'] ?>, '<?= htmlspecialchars(addslashes($ag['procedimentos'] ?? 'Procedimento')) ?>', '<?= $ag['codigo_reserva'] ?>')" title="Receber no Caixa com Cálculo de Troco">
                                                        <i class="bi bi-cash-coin me-1"></i> Caixa
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($ag['status_atendimento'] === 'agendado'): ?>
                                                    <!-- Check-in do Cliente -->
                                                    <form action="<?= BASE_URL ?>/actions/agendamento_status.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                                        <input type="hidden" name="status" value="em_atendimento">
                                                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning py-1 px-2" title="Check-in: Cliente sentou na cadeira">
                                                            <i class="bi bi-box-arrow-in-right"></i> Check-in
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($ag['status_atendimento'] !== 'cancelado'): ?>
                                                    <!-- Botão Cancelar com Destaque -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 fw-semibold" title="Cancelar Agendamento e Definir Regra de Taxa CDC" onclick='abrirModalCancelarRecepcao(<?= json_encode($ag) ?>, "<?= htmlspecialchars(addslashes($nomeExibicao)) ?>")'>
                                                        <i class="bi bi-x-circle me-1"></i> Cancelar
                                                    </button>
                                                <?php endif; ?>

                                                <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Ver Comprovante Oficial">
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

        <!-- ABA 2: Caixa & Recebimentos Presenciais -->
        <div class="tab-pane fade" id="tab-caixa" role="tabpanel" aria-labelledby="tab-caixa-btn">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="barber-card p-4">
                        <h5 class="text-white fw-bold mb-3"><i class="bi bi-cash-coin text-gold me-2"></i>Instruções do Caixa com Troco Rápido</h5>
                        <p class="text-secondary small">
                            Quando o cliente pagar em dinheiro no balcão da recepção, utilize o botão <strong>Caixa</strong> na linha do agendamento.
                        </p>
                        <ul class="text-secondary small">
                            <li>O sistema calcula o troco em tempo real conforme as cédulas digitadas ou botões de atalho (R$ 20, R$ 50, R$ 100, R$ 200).</li>
                            <li>Após confirmar o recebimento, o status é alterado imediatamente para <strong>PAGO</strong>.</li>
                            <li>É gerado o comprovante oficial com código fiscal de transação e nome do operador logado.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="barber-card p-4">
                        <h5 class="text-white fw-bold mb-3"><i class="bi bi-receipt text-gold me-2"></i>Acesso ao Livro Caixa</h5>
                        <p class="text-secondary small">
                            Consulte todas as movimentações contábeis, total em dinheiro em caixa, repasses e estornos.
                        </p>
                        <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-gold btn-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i> Abrir Relatório do Livro Caixa
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 3: Agendamento Rápido no Balcão -->
        <div class="tab-pane fade" id="tab-balcao" role="tabpanel" aria-labelledby="tab-balcao-btn">
            <?php require __DIR__ . '/../includes/componente_agendamento_balcao.php'; ?>
        </div>

    </div>
</div>

<!-- Modal de Cancelamento da Recepção com Isenção/Redução CDC -->
<div class="modal fade" id="modalCancelarRecepcao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-danger"><i class="bi bi-x-circle me-2"></i>Cancelar Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/agendamento_cancelar.php" method="POST">
                <input type="hidden" name="agendamento_id" id="recCancId" value="">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/recepcao/dashboard.php">
                <input type="hidden" name="isentar_taxa" id="recHiddenIsentar" value="0">
                
                <div class="modal-body py-3">
                    <div id="recCancEtapa1">
                        <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
                            <span class="text-secondary small d-block">Cliente: <strong id="recCancCliente" class="text-white"></strong></span>
                            <span class="text-secondary small d-block">Reserva: <strong id="recCancCodigo" class="text-gold font-monospace"></strong></span>
                            <span class="text-secondary small d-block">Valor Total: <strong id="recCancValor" class="text-gold"></strong></span>
                        </div>

                        <!-- Alerta de Isenção para Agendamentos de Balcão -->
                        <div id="alertaBalcaoIsento" class="alert alert-success py-2 small mb-3" style="display: none;">
                            <i class="bi bi-shield-check me-1"></i> <strong>Atendimento de Balcão:</strong> Isento de taxa de cancelamento.
                        </div>

                        <!-- Opções de Cobrança / Isenção -->
                        <div id="cardControleTaxa" class="card p-3 mb-3 border border-secondary border-opacity-50 bg-dark">
                            <label class="form-label text-gold fw-bold small text-uppercase mb-2">
                                <i class="bi bi-percent me-1"></i> Taxa de Cancelamento
                            </label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="opcao_taxa_recepcao" id="recOptIsentar" value="isentar" onchange="selecionarOpcaoTaxaRecepcao(this.value)">
                                <label class="form-check-label text-white fw-bold small" for="recOptIsentar">
                                    Isentar Taxa (Reembolso de 100%)
                                </label>
                                <small class="text-secondary d-block ps-4">Utilize para cortesias ou reagendamentos combinados.</small>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="opcao_taxa_recepcao" id="recOptCobrar" value="cobrar" checked onchange="selecionarOpcaoTaxaRecepcao(this.value)">
                                <label class="form-check-label text-white fw-bold small" for="recOptCobrar">
                                    Aplicar Taxa Padrão
                                </label>
                                <small class="text-secondary d-block ps-4">Reter taxa operacional pela reserva do horário.</small>
                            </div>

                            <div id="blocoCalculoTaxaRecepcao" class="mt-2 p-3 rounded bg-black border border-secondary border-opacity-25">
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-7">
                                        <label class="form-label small text-secondary mb-0">Taxa da Barbearia:</label>
                                    </div>
                                    <div class="col-5">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control text-secondary" id="recInputTaxaPerc" value="<?= $taxaPadraoCDC ?>%" readonly disabled style="background-color: #212529 !important; color: #adb5bd !important; cursor: not-allowed; border-color: #495057; font-weight: bold;" title="Definida pela administração">
                                            <span class="input-group-text border-secondary" style="background-color: #1a1d20 !important;"><i class="bi bi-lock-fill text-muted"></i></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between small text-secondary border-top border-secondary border-opacity-25 pt-2">
                                    <span>Retenção do Salão:</span>
                                    <strong class="text-danger" id="recValorRetencaoPreview">R$ 0,00</strong>
                                </div>
                                <div class="d-flex justify-content-between small text-secondary pt-1">
                                    <span>Saldo a Reembolsar ao Cliente:</span>
                                    <strong class="text-success" id="recValorReembolsoPreview">R$ 0,00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="recMotivo" class="form-label small text-secondary">Motivo do Cancelamento</label>
                            <textarea class="form-control form-control-sm" name="motivo_cancelamento" id="recMotivo" rows="2" placeholder="Ex: Cliente solicitou cancelamento, reagendamento..." required></textarea>
                        </div>
                    </div>

                    <div id="recCancEtapa2" style="display: none;">
                        <div class="alert alert-danger border-danger bg-danger bg-opacity-10 text-white mb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold text-danger mb-2">
                                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                <span>Confirmação de Cancelamento (Etapa 2 de 2)</span>
                            </div>
                            <p class="small mb-2">
                                Tem certeza que deseja cancelar o agendamento de <strong id="recCancClienteConfirma" class="text-white"></strong> (<strong id="recCancCodigoConfirma" class="text-gold font-monospace"></strong>)?
                            </p>
                            <p class="small text-secondary mb-0">
                                Após confirmado, apenas o proprietário (Dono) poderá reverter este cancelamento.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="recCancFooter1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="avancarEtapaCancelarRecepcao(true)">
                        <i class="bi bi-arrow-right-circle me-1"></i> Prosseguir para Confirmação
                    </button>
                </div>
                <div class="modal-footer" id="recCancFooter2" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="avancarEtapaCancelarRecepcao(false)">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                        <i class="bi bi-x-circle me-1"></i> Sim, Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let valorTotalCancelamentoRecepcao = 0;

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const cobrarId = params.get('cobrar_id') || params.get('concluido_id');
    if (cobrarId) {
        abrirModalQrCode(cobrarId);
    }
});

function filtrarTabelaRecepcao() {
    const termo = (document.getElementById('buscaRecepcao').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('#tab-agenda tbody tr');
    rows.forEach(tr => {
        const txt = tr.innerText.toLowerCase();
        tr.style.display = txt.includes(termo) ? '' : 'none';
    });
}

function avancarEtapaCancelarRecepcao(irParaEtapa2) {
    if (irParaEtapa2) {
        const motivoEl = document.getElementById('recMotivo');
        if (!motivoEl.value.trim()) {
            motivoEl.focus();
            motivoEl.reportValidity();
            return;
        }
    }
    document.getElementById('recCancEtapa1').style.display = irParaEtapa2 ? 'none' : 'block';
    document.getElementById('recCancFooter1').style.display = irParaEtapa2 ? 'none' : 'flex';
    document.getElementById('recCancEtapa2').style.display = irParaEtapa2 ? 'block' : 'none';
    document.getElementById('recCancFooter2').style.display = irParaEtapa2 ? 'flex' : 'none';
}

function abrirModalCancelarRecepcao(ag, nomeExibicao) {
    avancarEtapaCancelarRecepcao(false);
    document.getElementById('recCancId').value = ag.id;
    const nome = nomeExibicao || ag.cliente_nome_cadastrado;
    document.getElementById('recCancCliente').innerText = nome;
    document.getElementById('recCancCodigo').innerText = ag.codigo_reserva;
    document.getElementById('recCancClienteConfirma').innerText = nome;
    document.getElementById('recCancCodigoConfirma').innerText = '#' + ag.codigo_reserva;
    valorTotalCancelamentoRecepcao = parseFloat(ag.valor_total) || 0;
    document.getElementById('recCancValor').innerText = 'R$ ' + valorTotalCancelamentoRecepcao.toFixed(2).replace('.', ',');
    
    const ehBalcao = (ag.origem === 'balcao' || ag.cliente_balcao == 1);
    const alertaBalcao = document.getElementById('alertaBalcaoIsento');
    const cardControle = document.getElementById('cardControleTaxa');

    if (ehBalcao) {
        alertaBalcao.style.display = 'block';
        cardControle.style.display = 'none';
        document.getElementById('recHiddenIsentar').value = '1';
        document.getElementById('recInputTaxaPerc').value = '0';
    } else {
        alertaBalcao.style.display = 'none';
        cardControle.style.display = 'block';
        document.getElementById('recOptCobrar').checked = true;
        document.getElementById('recHiddenIsentar').value = '0';
        document.getElementById('recInputTaxaPerc').value = '<?= $taxaPadraoCDC ?>';
        document.getElementById('blocoCalculoTaxaRecepcao').style.display = 'block';
        calcularValoresCancelamentoRecepcao();
    }

    document.getElementById('recMotivo').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalCancelarRecepcao'));
    modal.show();
}

const TAXA_OFICIAL_DONO = <?= (float)$taxaPadraoCDC ?>;

function selecionarOpcaoTaxaRecepcao(opcao) {
    const bloco = document.getElementById('blocoCalculoTaxaRecepcao');
    const hiddenIsentar = document.getElementById('recHiddenIsentar');

    if (opcao === 'isentar') {
        hiddenIsentar.value = '1';
        bloco.style.display = 'none';
    } else {
        hiddenIsentar.value = '0';
        bloco.style.display = 'block';
        calcularValoresCancelamentoRecepcao();
    }
}

function calcularValoresCancelamentoRecepcao() {
    // Imutável: sempre utiliza a taxa oficial configurada pelo Dono
    const perc = TAXA_OFICIAL_DONO;
    const valorRetencao = valorTotalCancelamentoRecepcao * (perc / 100.0);
    const valorReembolso = Math.max(0, valorTotalCancelamentoRecepcao - valorRetencao);

    document.getElementById('recValorRetencaoPreview').innerText = 'R$ ' + valorRetencao.toFixed(2).replace('.', ',');
    document.getElementById('recValorReembolsoPreview').innerText = 'R$ ' + valorReembolso.toFixed(2).replace('.', ',');
}

function ativarAbaBalcao() {
    const btn = document.getElementById('tab-balcao-btn');
    if (btn) btn.click();
}

function abrirModalQrBalcao() {
    let modalEl = document.getElementById('modalQrBalcao');
    if (!modalEl) {
        console.warn('Elemento #modalQrBalcao não encontrado no DOM.');
        return;
    }
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }
}
</script>

<?php 
require_once __DIR__ . '/../includes/modal_caixa.php';
require_once __DIR__ . '/../includes/footer.php'; 
?>
