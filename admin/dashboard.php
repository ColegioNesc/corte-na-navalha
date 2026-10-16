<?php
/**
 * Painel Administrativo do Dono - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

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
    LIMIT 10
";
$recentes = $pdo->query($sqlRecentes)->fetchAll();

$paginaTitulo = 'Painel de Gestão da Barbearia | Dono';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho Executivo -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 65px; height: 65px; font-size: 2rem;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Gestão Central: <?= htmlspecialchars($empresa['nome_barbearia']) ?></h4>
                        <p class="text-secondary small mb-0">Controle financeiro, equipe, faturamento e serviços da barbearia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="<?= BASE_URL ?>/admin/funcionarios.php" class="btn btn-gold">
                    <i class="bi bi-people-fill me-1"></i> Equipe & Pagar PIX
                </a>
                <a href="<?= BASE_URL ?>/admin/servicos.php" class="btn btn-outline-gold">
                    <i class="bi bi-tags-fill me-1"></i> Tabela de Preços
                </a>
                <a href="<?= BASE_URL ?>/admin/empresa.php" class="btn btn-outline-secondary">
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
                        <small class="text-muted d-block text-uppercase fw-semibold">Clientes Cadastrados</small>
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
                        <small class="text-muted d-block text-uppercase fw-semibold">Mestres Barbeiros</small>
                        <h3 class="text-white fw-bold mb-0"><?= $totalBarbeiros ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desempenho e Comissões dos Barbeiros -->
    <div class="barber-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
            <div>
                <h5 class="text-white fw-bold mb-1"><i class="bi bi-trophy text-gold me-2"></i>Desempenho dos Barbeiros & Repasse de Comissão</h5>
                <p class="text-secondary small mb-0">Métricas de produção e atalho para pagamento de comissão via chave PIX</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/funcionarios.php" class="btn btn-sm btn-outline-gold">
                Gerenciar Equipe Completa
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
                        <th>Média Avaliações</th>
                        <th class="text-end pe-3">Ação</th>
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
                                <a href="<?= BASE_URL ?>/admin/funcionarios.php?pagar_id=<?= $b['id'] ?>" class="btn btn-sm btn-gold py-1 px-2">
                                    <i class="bi bi-send-check me-1"></i> Pagar PIX
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Atendimentos Recentes -->
    <div class="barber-card p-0">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold"><i class="bi bi-list-stars text-gold me-2"></i>Últimos Agendamentos no Sistema</h5>
            <span class="text-secondary small">Exibindo os 10 mais recentes</span>
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
                        <th class="text-end pe-3">Recibo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentes as $rec): ?>
                        <tr>
                            <td class="ps-3 font-monospace small text-gold"><?= htmlspecialchars($rec['codigo_reserva']) ?></td>
                            <td><?= date('d/m/Y', strtotime($rec['data_agendamento'])) ?> <?= substr($rec['horario'], 0, 5) ?></td>
                            <td class="fw-semibold text-white"><?= htmlspecialchars($rec['cliente_nome']) ?></td>
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
                                <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($rec['codigo_reserva']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
