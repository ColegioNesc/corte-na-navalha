<?php
/**
 * Painel Administrativo do Dono - Barbearia Corte na Navalha
 * Abas de Gestão, Transações por Funcionário, Política CDC e Acesso Rápido
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$taxaPadraoCDC = (float)($empresa['taxa_cancelamento_percentual'] ?? 20.0);
$horasMinimasCDC = (float)($empresa['horas_minimas_cancelamento'] ?? 2.0);

// Métricas Gerais
$stmtTotalFat = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) as total FROM agendamentos WHERE status_pagamento = 'pago'");
$totalFaturamento = (float)$stmtTotalFat->fetchColumn();

$stmtTotalCortes = $pdo->query("SELECT COUNT(id) FROM agendamentos WHERE status_atendimento = 'concluido'");
$totalCortes = (int)$stmtTotalCortes->fetchColumn();

$stmtTotalClientes = $pdo->query("SELECT COUNT(id) FROM usuarios WHERE perfil = 'cliente'");
$totalClientes = (int)$stmtTotalClientes->fetchColumn();

$stmtTotalBarbeiros = $pdo->query("SELECT COUNT(id) FROM usuarios WHERE perfil = 'barbeiro' AND ativo = 1");
$totalBarbeiros = (int)$stmtTotalBarbeiros->fetchColumn();

// Faturamento e Desempenho por Barbeiro
$sqlBarbeiros = "
    SELECT u.id, u.nome, u.chave_pix, u.tipo_chave_pix, u.comissao_percentual,
           COUNT(a.id) as total_atendimentos,
           COALESCE(SUM(CASE WHEN a.status_pagamento = 'pago' THEN a.valor_total ELSE 0 END), 0) as total_faturado,
           COALESCE(AVG(av.estrelas), 5.0) as media_avaliacao
    FROM usuarios u
    LEFT JOIN agendamentos a ON a.barbeiro_id = u.id AND a.status_atendimento = 'concluido'
    LEFT JOIN avaliacoes av ON av.barbeiro_id = u.id
    WHERE u.perfil = 'barbeiro' AND u.ativo = 1
    GROUP BY u.id
    ORDER BY total_faturado DESC
";
$stmtPerf = $pdo->query($sqlBarbeiros);
$desempenhoBarbeiros = $stmtPerf->fetchAll();

// Atendimentos Recentes
$sqlRecentes = "
    SELECT a.*, c.nome as cliente_nome, b.nome as barbeiro_nome,
           GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN usuarios b ON a.barbeiro_id = b.id
    JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    GROUP BY a.id
    ORDER BY a.id DESC
    LIMIT 15
";
$recentes = $pdo->query($sqlRecentes)->fetchAll();

// Serviços para visualização rápida
$servicosGerais = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC LIMIT 8")->fetchAll();

$paginaTitulo = 'Painel de Gestão da Barbearia | Dono';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho Executivo com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar para A Barbearia">
                        <i class="bi bi-arrow-left"></i> A Barbearia
                    </a>
                    <div class="stat-icon" style="width: 55px; height: 55px; font-size: 1.8rem;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Gestão Central: <?= htmlspecialchars($empresa['nome_barbearia']) ?></h4>
                        <p class="text-secondary small mb-0">Controle integrado de faturamento, equipe, catálogo e configurações</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-sm btn-gold">
                    <i class="bi bi-cash-stack me-1"></i> Livro Caixa
                </a>
                <a href="<?= BASE_URL ?>/admin/funcionarios.php" class="btn btn-sm btn-outline-gold">
                    <i class="bi bi-people-fill me-1"></i> Equipe & PIX
                </a>
                <a href="<?= BASE_URL ?>/admin/pagamentos.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-wallet2 me-1"></i> Gateway
                </a>
                <a href="<?= BASE_URL ?>/admin/empresa.php" class="btn btn-sm btn-outline-secondary" title="Configurações da Barbearia">
                    <i class="bi bi-gear-fill"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Indicadores Principais -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Faturamento Total</small>
                        <h3 class="text-gold fw-bold mb-0">R$ <?= number_format($totalFaturamento, 2, ',', '.') ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Cortes Concluídos</small>
                        <h3 class="text-white fw-bold mb-0"><?= $totalCortes ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-scissors"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Clientes na Base</small>
                        <h3 class="text-white fw-bold mb-0"><?= $totalClientes ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Barbeiros Ativos</small>
                        <h3 class="text-white fw-bold mb-0"><?= $totalBarbeiros ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas de Gestão do Dono -->
    <ul class="nav nav-pills mb-4 nav-fill p-2 rounded-3 bg-dark border border-secondary border-opacity-25" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active py-2 text-uppercase fw-semibold small" id="tab-geral-btn" data-bs-toggle="pill" data-bs-target="#tab-geral" type="button" role="tab" aria-selected="true">
                <i class="bi bi-speedometer me-1 text-gold"></i> Visão Geral
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-balcao-btn" data-bs-toggle="pill" data-bs-target="#tab-balcao" type="button" role="tab" aria-selected="false">
                <i class="bi bi-person-plus me-1 text-danger"></i> Agendamento de Balcão
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-equipe-btn" data-bs-toggle="pill" data-bs-target="#tab-equipe" type="button" role="tab" aria-selected="false">
                <i class="bi bi-people me-1 text-danger"></i> Equipe & Repasses
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-servicos-btn" data-bs-toggle="pill" data-bs-target="#tab-servicos" type="button" role="tab" aria-selected="false">
                <i class="bi bi-tag me-1 text-info"></i> Tabela de Preços
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-caixa-btn" data-bs-toggle="pill" data-bs-target="#tab-caixa" type="button" role="tab" aria-selected="false">
                <i class="bi bi-receipt me-1 text-success"></i> Livro Caixa
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-config-btn" data-bs-toggle="pill" data-bs-target="#tab-config" type="button" role="tab" aria-selected="false">
                <i class="bi bi-gear me-1 text-warning"></i> Configurações
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Abas -->
    <div class="tab-content" id="adminTabsContent">
        
        <!-- ABA 1: Visão Geral & Agendamentos Recentes -->
        <div class="tab-pane fade show active" id="tab-geral" role="tabpanel" aria-labelledby="tab-geral-btn">
            <div class="barber-card p-0">
                <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0 fw-bold"><i class="bi bi-list-stars text-danger me-2"></i>Últimos Agendamentos no Sistema</h5>
                    <span class="text-secondary small">Exibindo os mais recentes</span>
                </div>
                
                <!-- Pesquisa em Tempo Real nos Recentes -->
                <div class="p-3 border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-50">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-danger"><i class="bi bi-search"></i></span>
                        <input type="text" id="filtroAtendimentosRecentes" class="form-control form-control-sm" placeholder="Buscar por cliente, barbeiro, procedimento ou código..." oninput="filtrarAtendimentosRecentes()">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead style="background: #111319;">
                            <tr>
                                <th class="ps-3">Código</th>
                                <th>Data/Hora</th>
                                <th>Cliente</th>
                                <th>Barbeiro</th>
                                <th>Procedimento(s)</th>
                                <th>Valor</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaRecentesBody">
                            <?php foreach ($recentes as $rec): ?>
                                <tr>
                                    <td class="ps-3 font-monospace small text-gold"><?= htmlspecialchars($rec['codigo_reserva']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($rec['data_agendamento'])) ?> <?= substr($rec['horario'], 0, 5) ?></td>
                                    <?php 
                                        $ehBalcao = ($rec['origem'] === 'balcao' || !empty($rec['cliente_balcao']));
                                        $nomeCli = !empty($rec['cliente_nome_avulso']) 
                                            ? $rec['cliente_nome_avulso'] 
                                            : ($ehBalcao ? 'Cliente Balcão (Não Identificado)' : $rec['cliente_nome']);
                                    ?>
                                    <td class="fw-semibold text-white">
                                        <?= htmlspecialchars($nomeCli) ?>
                                        <?php if ($ehBalcao): ?>
                                            <span class="badge bg-secondary text-white ms-1" style="font-size: 0.65rem;">Balcão</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($rec['barbeiro_nome']) ?></td>
                                    <td class="small"><?= htmlspecialchars($rec['procedimentos']) ?></td>
                                    <td class="fw-bold">R$ <?= number_format($rec['valor_total'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $rec['status_pagamento'] ?>">
                                            <?= strtoupper($rec['status_pagamento']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $rec['status_atendimento'] ?>">
                                            <?= str_replace('_', ' ', $rec['status_atendimento']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($rec['codigo_reserva']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Ver Recibo Oficial">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                            <?php if ($rec['status_atendimento'] !== 'cancelado'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 fw-semibold" title="Cancelar Agendamento" onclick='abrirModalCancelarDono(<?= json_encode($rec) ?>)'>
                                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2 fw-semibold" title="Reverter Cancelamento (Exclusivo do Dono)" onclick='abrirModalReverterDono(<?= json_encode($rec) ?>)'>
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reverter
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: Agendamento Rápido no Balcão (Mesmo motor da Recepção com Isenção de Taxa) -->
        <div class="tab-pane fade" id="tab-balcao" role="tabpanel" aria-labelledby="tab-balcao-btn">
            <?php require __DIR__ . '/../includes/componente_agendamento_balcao.php'; ?>
        </div>

        <!-- ABA 2: Equipe, Faturamento Individual & Comissões -->
        <div class="tab-pane fade" id="tab-equipe" role="tabpanel" aria-labelledby="tab-equipe-btn">
            <div class="barber-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h5 class="text-white fw-bold mb-1"><i class="bi bi-trophy text-gold me-2"></i>Desempenho dos Profissionais & Repasses PIX</h5>
                        <p class="text-secondary small mb-0">Consulte faturamento, comissões calculadas e extrato individual de cada colaborador</p>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/funcionarios.php" class="btn btn-sm btn-gold">
                        <i class="bi bi-people-fill me-1"></i> Gerenciar Equipe Completa
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead style="background: #111319;">
                            <tr>
                                <th class="ps-3">Profissional</th>
                                <th>Chave PIX Cadastrada</th>
                                <th>Cortes Feitos</th>
                                <th>Faturado no Salão</th>
                                <th>Comissão (%)</th>
                                <th>Comissão Calculada</th>
                                <th>Avaliação</th>
                                <th class="text-end pe-3">Ações Individuais</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desempenhoBarbeiros as $b): ?>
                                <?php 
                                    $comissaoPerc = (float)$b['comissao_percentual'];
                                    $valorComissao = ((float)$b['total_faturado'] * $comissaoPerc) / 100;
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-white"><?= htmlspecialchars($b['nome']) ?></td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary text-gold font-monospace">
                                            <?= htmlspecialchars($b['chave_pix'] ?? 'Pendente') ?>
                                        </span>
                                    </td>
                                    <td><?= (int)$b['total_atendimentos'] ?></td>
                                    <td class="fw-bold">R$ <?= number_format($b['total_faturado'], 2, ',', '.') ?></td>
                                    <td><?= number_format($comissaoPerc, 0) ?>%</td>
                                    <td class="text-gold fw-bold">R$ <?= number_format($valorComissao, 2, ',', '.') ?></td>
                                    <td>
                                        <span class="text-warning small">
                                            <i class="bi bi-star-fill"></i> <?= number_format($b['media_avaliacao'], 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <!-- Atalho para verificar cada transação do funcionário individualmente -->
                                            <a href="<?= BASE_URL ?>/admin/transacoes.php?funcionario_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2" title="Ver Extrato e Transações Deste Barbeiro">
                                                <i class="bi bi-receipt me-1"></i> Extrato
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/funcionarios.php?pagar_id=<?= $b['id'] ?>" class="btn btn-sm btn-gold py-1 px-2" title="Transferir Comissão via PIX">
                                                <i class="bi bi-send-check me-1"></i> Pagar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 3: Catálogo de Serviços & Tabela de Preços -->
        <div class="tab-pane fade" id="tab-servicos" role="tabpanel" aria-labelledby="tab-servicos-btn">
            <div class="barber-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h5 class="text-white fw-bold mb-1"><i class="bi bi-scissors text-gold me-2"></i>Tabela Geral de Preços & Procedimentos</h5>
                        <p class="text-secondary small mb-0">Valores padrão aplicados nos cortes, barboterapias e combos</p>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/servicos.php" class="btn btn-gold btn-sm">
                        <i class="bi bi-gear-fill me-1"></i> Editar Todos os Preços & Regras
                    </a>
                </div>

                <div class="row g-3">
                    <?php foreach ($servicosGerais as $sg): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="text-white"><?= htmlspecialchars($sg['nome']) ?></strong>
                                        <span class="badge bg-gold text-dark fw-bold">R$ <?= number_format($sg['preco'], 2, ',', '.') ?></span>
                                    </div>
                                    <small class="text-secondary d-block mb-2"><?= htmlspecialchars($sg['descricao'] ?? 'Procedimento padrão.') ?></small>
                                </div>
                                <small class="text-muted"><i class="bi bi-clock me-1 text-gold"></i> <?= (int)$sg['duracao_minutos'] ?> min</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ABA 4: Livro Caixa & Transações por Funcionário -->
        <div class="tab-pane fade" id="tab-caixa" role="tabpanel" aria-labelledby="tab-caixa-btn">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="barber-card p-4">
                        <h5 class="text-white fw-bold mb-3"><i class="bi bi-receipt-cutoff text-gold me-2"></i>Livro Caixa & Transações Individuais</h5>
                        <p class="text-secondary small">
                            Acompanhe cada entrada financeira, pagamentos com dinheiro e cálculo de troco, recebimentos instantâneos do Mercado Pago e estornos realizados.
                        </p>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-gold btn-sm">
                                <i class="bi bi-receipt me-1"></i> Abrir Livro Caixa Completo
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="barber-card p-4">
                        <h5 class="text-white fw-bold mb-3"><i class="bi bi-filter-square text-gold me-2"></i>Filtrar por Colaborador</h5>
                        <p class="text-secondary small">
                            Consulte o faturamento e comissões detalhadas de cada profissional separadamente:
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($desempenhoBarbeiros as $b): ?>
                                <a href="<?= BASE_URL ?>/admin/transacoes.php?funcionario_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-person me-1"></i> <?= htmlspecialchars($b['nome']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 5: Configurações -->
        <div class="tab-pane fade" id="tab-config" role="tabpanel" aria-labelledby="tab-config-btn">
            
            <!-- Banner da Política de Cancelamento -->
            <div class="barber-card p-4 mb-4 border border-secondary border-opacity-50">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon text-gold bg-dark border border-secondary border-opacity-25" style="width: 55px; height: 55px; font-size: 1.6rem;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h5 class="text-white fw-bold mb-0">Política de Cancelamento & Reembolso</h5>
                                    <span class="badge bg-gold text-dark fw-bold">Taxa: <?= $taxaPadraoCDC ?>%</span>
                                    <span class="badge bg-dark border border-secondary text-secondary">Cancelamento grátis até <?= $horasMinimasCDC ?>h antes</span>
                                    <span class="badge bg-secondary text-light px-2 py-1 small" title="Conforme legislação vigente">Máx: 20%</span>
                                </div>
                                <p class="text-secondary small mb-0">
                                    Defina a taxa de retenção aplicada em cancelamentos fora do prazo. Você pode ajustar essa porcentagem a qualquer momento ou isentar clientes individualmente no cancelamento.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
                        <button type="button" class="btn btn-gold btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalConfigCDC">
                            <i class="bi bi-sliders me-1"></i> Ajustar Taxa
                        </button>
                        <a href="<?= BASE_URL ?>/admin/empresa.php#politica_cancelamento" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil-square me-1"></i> Dados da Barbearia
                        </a>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="barber-card p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="stat-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-building"></i>
                            </div>
                            <h5 class="text-white fw-bold mb-2">Dados da Barbearia</h5>
                            <p class="text-secondary small mb-3">Nome, CNPJ, WhatsApp, endereço e texto legal da Política de Cancelamento.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/empresa.php" class="btn btn-outline-gold btn-sm">Configurar Barbearia</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="barber-card p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="stat-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-credit-card-2-front"></i>
                            </div>
                            <h5 class="text-white fw-bold mb-2">Gateway & PIX</h5>
                            <p class="text-secondary small mb-3">Mercado Pago com webhook automático e PIX Fixo estático com QR Code.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/pagamentos.php" class="btn btn-outline-danger btn-sm">Configurar Pagamentos</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="barber-card p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="stat-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-envelope-at"></i>
                            </div>
                            <h5 class="text-white fw-bold mb-2">Servidor SMTP E-mail</h5>
                            <p class="text-secondary small mb-3">Configure envio de e-mails via Gmail ou servidor de hospedagem próprio.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/email_config.php" class="btn btn-outline-info btn-sm">Configurar SMTP</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="barber-card p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="stat-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </div>
                            <h5 class="text-white fw-bold mb-2">Modelos de Mensagem</h5>
                            <p class="text-secondary small mb-3">Editor estilo Word para personalizar e-mails e recibos dinâmicos.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/email_templates.php" class="btn btn-outline-warning btn-sm">Editar Modelos</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal de Configuração da Taxa de Cancelamento -->
<div class="modal fade" id="modalConfigCDC" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-gold"><i class="bi bi-shield-check me-2"></i>Política de Cancelamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/politica_cancelamento_salvar.php" method="POST">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/admin/dashboard.php">
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        Ajuste a taxa de retenção aplicada quando o cliente desmarcar fora do prazo de gratuidade.
                    </p>

                    <div class="mb-3">
                        <label class="form-label small text-secondary">Taxa de Cancelamento (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="0" max="20" class="form-control" name="taxa_cancelamento_percentual" value="<?= $taxaPadraoCDC ?>" required>
                            <span class="input-group-text bg-dark border-secondary text-gold">%</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Tip: o limite legal máximo permitido é de 20%.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary">Prazo para Cancelamento Gratuito <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="1" min="1" max="72" class="form-control" name="horas_minimas_cancelamento" value="<?= (int)$horasMinimasCDC ?>" required>
                            <span class="input-group-text bg-dark border-secondary text-gold">horas antes</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Tip: cancelamentos antes desse prazo têm reembolso integral.</small>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-gold btn-sm fw-bold">
                        <i class="bi bi-save me-1"></i> Salvar Regras
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento do Dono -->
<div class="modal fade" id="modalCancelarDono" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-danger"><i class="bi bi-x-circle me-2"></i>Cancelar Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/agendamento_cancelar.php" method="POST">
                <input type="hidden" name="agendamento_id" id="donoCancId" value="">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/admin/dashboard.php">
                <input type="hidden" name="isentar_taxa" id="donoHiddenIsentar" value="0">
                
                <div class="modal-body py-3">
                    <div id="donoCancEtapa1">
                        <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
                            <span class="text-secondary small d-block">Cliente: <strong id="donoCancCliente" class="text-white"></strong></span>
                            <span class="text-secondary small d-block">Reserva: <strong id="donoCancCodigo" class="text-gold font-monospace"></strong></span>
                            <span class="text-secondary small d-block">Valor Total: <strong id="donoCancValor" class="text-gold"></strong></span>
                        </div>

                    <!-- Alerta de Isenção para Agendamentos de Balcão -->
                    <div id="donoAlertaBalcaoIsento" class="alert alert-success py-2 small mb-3" style="display: none;">
                        <i class="bi bi-shield-check me-1"></i> <strong>Atendimento de Balcão:</strong> Isento de taxa de cancelamento.
                    </div>

                    <!-- Opções de Cobrança / Isenção -->
                    <div id="donoCardControleTaxa" class="card p-3 mb-3 border border-secondary border-opacity-50 bg-dark">
                        <label class="form-label text-gold fw-bold small text-uppercase mb-2">
                            <i class="bi bi-percent me-1"></i> Taxa de Cancelamento
                        </label>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao_taxa_dono" id="donoOptIsentar" value="isentar" onchange="selecionarOpcaoTaxaDono(this.value)">
                            <label class="form-check-label text-white fw-bold small" for="donoOptIsentar">
                                Isentar Taxa (Reembolso de 100%)
                            </label>
                            <small class="text-secondary d-block ps-4">Utilize para cortesias ou acordos com o cliente.</small>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao_taxa_dono" id="donoOptCobrar" value="cobrar" checked onchange="selecionarOpcaoTaxaDono(this.value)">
                            <label class="form-check-label text-white fw-bold small" for="donoOptCobrar">
                                Aplicar Taxa
                            </label>
                            <small class="text-secondary d-block ps-4">Reter taxa operacional pela reserva do horário.</small>
                        </div>

                        <div id="blocoCalculoTaxaDono" class="mt-2 p-3 rounded bg-black border border-secondary border-opacity-25">
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7">
                                    <label class="form-label small text-secondary mb-0">Porcentagem a Reter:</label>
                                </div>
                                <div class="col-5">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="0" max="20" class="form-control" name="taxa_personalizada" id="donoInputTaxaPerc" value="<?= $taxaPadraoCDC ?>" oninput="calcularValoresCancelamentoDono()">
                                        <span class="input-group-text bg-dark border-secondary text-secondary">%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between small text-secondary border-top border-secondary border-opacity-25 pt-2">
                                <span>Retenção da Barbearia:</span>
                                <strong class="text-danger" id="donoValorRetencaoPreview">R$ 0,00</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-secondary pt-1">
                                <span>Saldo a Devolver ao Cliente:</span>
                                <strong class="text-success" id="donoValorReembolsoPreview">R$ 0,00</strong>
                            </div>
                        </div>
                    </div>

                        <div class="mb-3">
                            <label for="donoMotivo" class="form-label small text-secondary">Motivo do Cancelamento</label>
                            <textarea class="form-control form-control-sm" name="motivo_cancelamento" id="donoMotivo" rows="2" placeholder="Ex: Cancelamento solicitado pelo cliente..." required></textarea>
                        </div>
                    </div>

                    <div id="donoCancEtapa2" style="display: none;">
                        <div class="alert alert-danger border-danger bg-danger bg-opacity-10 text-white mb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold text-danger mb-2">
                                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                <span>Confirmação de Cancelamento (Etapa 2 de 2)</span>
                            </div>
                            <p class="small mb-2">
                                Tem certeza que deseja cancelar o agendamento de <strong id="donoCancClienteConfirma" class="text-white"></strong> (<strong id="donoCancCodigoConfirma" class="text-gold font-monospace"></strong>)?
                            </p>
                            <p class="small text-secondary mb-0">
                                Caso precise desfazer depois, você poderá reverter o cancelamento diretamente nesta tabela.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="donoCancFooter1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="avancarEtapaCancelarDono(true)">
                        <i class="bi bi-arrow-right-circle me-1"></i> Prosseguir para Confirmação
                    </button>
                </div>
                <div class="modal-footer" id="donoCancFooter2" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="avancarEtapaCancelarDono(false)">
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

<!-- Modal de Reversão de Cancelamento (Exclusivo do Dono) -->
<div class="modal fade" id="modalReverterDono" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title brand-font text-warning"><i class="bi bi-arrow-counterclockwise me-2"></i>Reverter Cancelamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/agendamento_reverter.php" method="POST">
                <input type="hidden" name="agendamento_id" id="donoRevId" value="">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/admin/dashboard.php">
                <div class="modal-body py-3">
                    <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
                        <span class="text-secondary small d-block">Cliente: <strong id="donoRevCliente" class="text-white"></strong></span>
                        <span class="text-secondary small d-block">Reserva: <strong id="donoRevCodigo" class="text-gold font-monospace"></strong></span>
                    </div>
                    <div class="alert alert-warning border-warning bg-warning bg-opacity-10 text-white small mb-0">
                        <i class="bi bi-shield-lock me-1 text-warning"></i>
                        <strong>Ação Exclusiva do Dono:</strong> Ao reverter, o agendamento voltará ao status <strong>Agendado</strong>, as taxas de cancelamento registradas serão zeradas e eventuais estornos lançados serão anulados.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Confirmar Reversão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let valorTotalAgendamentoDonoAtual = 0;

function filtrarAtendimentosRecentes() {
    const termo = (document.getElementById('filtroAtendimentosRecentes').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('#tabelaRecentesBody tr');
    rows.forEach(tr => {
        const txt = tr.innerText.toLowerCase();
        tr.style.display = txt.includes(termo) ? '' : 'none';
    });
}

function avancarEtapaCancelarDono(irParaEtapa2) {
    if (irParaEtapa2) {
        const motivoEl = document.getElementById('donoMotivo');
        if (!motivoEl.value.trim()) {
            motivoEl.focus();
            motivoEl.reportValidity();
            return;
        }
    }
    document.getElementById('donoCancEtapa1').style.display = irParaEtapa2 ? 'none' : 'block';
    document.getElementById('donoCancFooter1').style.display = irParaEtapa2 ? 'none' : 'flex';
    document.getElementById('donoCancEtapa2').style.display = irParaEtapa2 ? 'block' : 'none';
    document.getElementById('donoCancFooter2').style.display = irParaEtapa2 ? 'flex' : 'none';
}

function abrirModalReverterDono(rec) {
    document.getElementById('donoRevId').value = rec.id;
    document.getElementById('donoRevCliente').innerText = rec.cliente_nome_avulso || rec.cliente_nome;
    document.getElementById('donoRevCodigo').innerText = rec.codigo_reserva;
    const modal = new bootstrap.Modal(document.getElementById('modalReverterDono'));
    modal.show();
}

function abrirModalCancelarDono(rec) {
    avancarEtapaCancelarDono(false);
    document.getElementById('donoCancId').value = rec.id;
    const nomeCli = rec.cliente_nome_avulso || rec.cliente_nome;
    document.getElementById('donoCancCliente').innerText = nomeCli;
    document.getElementById('donoCancCodigo').innerText = rec.codigo_reserva;
    document.getElementById('donoCancClienteConfirma').innerText = nomeCli;
    document.getElementById('donoCancCodigoConfirma').innerText = '#' + rec.codigo_reserva;
    
    valorTotalAgendamentoDonoAtual = parseFloat(rec.valor_total) || 0;
    document.getElementById('donoCancValor').innerText = 'R$ ' + valorTotalAgendamentoDonoAtual.toFixed(2).replace('.', ',');
    
    const ehBalcao = (rec.origem === 'balcao' || rec.cliente_balcao == 1);
    const alertaBalcao = document.getElementById('donoAlertaBalcaoIsento');
    const cardControle = document.getElementById('donoCardControleTaxa');

    if (ehBalcao) {
        alertaBalcao.style.display = 'block';
        cardControle.style.display = 'none';
        document.getElementById('donoHiddenIsentar').value = '1';
    } else {
        alertaBalcao.style.display = 'none';
        cardControle.style.display = 'block';
        
        // Padrão: Cobrar taxa CDC padrão
        document.getElementById('donoOptCobrar').checked = true;
        document.getElementById('donoHiddenIsentar').value = '0';
        document.getElementById('donoInputTaxaPerc').value = '<?= $taxaPadraoCDC ?>';
        document.getElementById('blocoCalculoTaxaDono').style.display = 'block';
        calcularValoresCancelamentoDono();
    }

    document.getElementById('donoMotivo').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalCancelarDono'));
    modal.show();
}

function selecionarOpcaoTaxaDono(opcao) {
    const blocoCalculo = document.getElementById('blocoCalculoTaxaDono');
    const hiddenIsentar = document.getElementById('donoHiddenIsentar');

    if (opcao === 'isentar') {
        hiddenIsentar.value = '1';
        blocoCalculo.style.display = 'none';
    } else {
        hiddenIsentar.value = '0';
        blocoCalculo.style.display = 'block';
        calcularValoresCancelamentoDono();
    }
}

function calcularValoresCancelamentoDono() {
    let perc = parseFloat(document.getElementById('donoInputTaxaPerc').value) || 0;
    if (perc < 0) perc = 0;
    if (perc > 20) {
        perc = 20;
        document.getElementById('donoInputTaxaPerc').value = 20;
    }

    const valorRetencao = (valorTotalAgendamentoDonoAtual * perc) / 100.0;
    const valorReembolso = Math.max(0, valorTotalAgendamentoDonoAtual - valorRetencao);

    document.getElementById('donoValorRetencaoPreview').innerText = 'R$ ' + valorRetencao.toFixed(2).replace('.', ',');
    document.getElementById('donoValorReembolsoPreview').innerText = 'R$ ' + valorReembolso.toFixed(2).replace('.', ',');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
