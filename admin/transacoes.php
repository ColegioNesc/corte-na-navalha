<?php
/**
 * Relatório Financeiro & Registro de Transações (Livro Caixa)
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$pdo = getDBConnection();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim    = $_GET['data_fim'] ?? date('Y-m-d');
$metodoFiltro = $_GET['metodo'] ?? '';
$funcionarioId = !empty($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : 0;

$where = "WHERE DATE(t.criado_em) BETWEEN :inicio AND :fim";
$params = [':inicio' => $dataInicio, ':fim' => $dataFim];

if (!empty($metodoFiltro)) {
    $where .= " AND t.metodo = :metodo";
    $params[':metodo'] = $metodoFiltro;
}

if ($funcionarioId > 0) {
    $where .= " AND t.funcionario_id = :funcionario_id";
    $params[':funcionario_id'] = $funcionarioId;
}

// Lista de colaboradores para o filtro
$stmtColab = $pdo->query("SELECT id, nome, perfil, comissao_percentual FROM usuarios WHERE perfil IN ('barbeiro', 'recepcao', 'admin', 'dono') ORDER BY nome ASC");
$colaboradores = $stmtColab->fetchAll();

// Dados do colaborador selecionado se houver
$colaboradorSelecionado = null;
if ($funcionarioId > 0) {
    $stmtSel = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtSel->execute([$funcionarioId]);
    $colaboradorSelecionado = $stmtSel->fetch();
}

$sql = "
    SELECT t.*, 
           c.nome as cliente_nome,
           f.nome as funcionario_nome,
           op.nome as operador_nome,
           a.codigo_reserva
    FROM transacoes t
    LEFT JOIN usuarios c ON t.cliente_id = c.id
    LEFT JOIN usuarios f ON t.funcionario_id = f.id
    LEFT JOIN usuarios op ON t.operador_id = op.id
    LEFT JOIN agendamentos a ON t.agendamento_id = a.id
    $where
    ORDER BY t.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transacoes = $stmt->fetchAll();

// Métricas do período
$totalEntradas = 0.0;
$totalDinheiro = 0.0;
$totalPix = 0.0;
$totalTrocoDevolvido = 0.0;

foreach ($transacoes as $trx) {
    if ($trx['status'] === 'concluida') {
        $val = (float)$trx['valor_total'];
        $totalEntradas += $val;
        if ($trx['metodo'] === 'dinheiro') {
            $totalDinheiro += $val;
            $totalTrocoDevolvido += (float)$trx['troco'];
        } else {
            $totalPix += $val;
        }
    }
}

$paginaTitulo = 'Livro Caixa & Transações | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar ao Painel Principal">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Livro Caixa & Registro de Transações</h4>
                        <p class="text-secondary small mb-0">Controle contábil, recebimentos em dinheiro/PIX e extrato individual de profissionais</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2">
                <button onclick="window.print()" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-printer me-1"></i> Imprimir Relatório
                </button>
                <a href="<?= BASE_URL ?>/admin/funcionarios.php" class="btn btn-gold btn-sm">
                    <i class="bi bi-people me-1"></i> Equipe & PIX
                </a>
            </div>
        </div>
    </div>

    <?php if ($colaboradorSelecionado): 
        $comissaoPerc = (float)($colaboradorSelecionado['comissao_percentual'] ?? 50);
        $comissaoDevida = $totalEntradas * ($comissaoPerc / 100);
    ?>
        <!-- Destaque: Extrato Individual do Colaborador -->
        <div class="card border border-gold bg-dark bg-opacity-75 p-4 mb-4 shadow">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-gold text-dark d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px;">
                        <?= strtoupper(substr($colaboradorSelecionado['nome'], 0, 1)) ?>
                    </div>
                    <div>
                        <span class="badge bg-gold text-dark text-uppercase fw-bold mb-1"><?= htmlspecialchars($colaboradorSelecionado['perfil']) ?></span>
                        <h4 class="text-white fw-bold mb-0"><?= htmlspecialchars($colaboradorSelecionado['nome']) ?></h4>
                        <small class="text-secondary"><?= htmlspecialchars($colaboradorSelecionado['email']) ?> • Tel: <?= htmlspecialchars($colaboradorSelecionado['telefone'] ?? '---') ?></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="transacoes.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Limpar Filtro de Funcionário
                    </a>
                    <?php if (!empty($colaboradorSelecionado['chave_pix'])): ?>
                        <span class="badge bg-dark border border-warning text-gold font-monospace p-2">
                            PIX: <?= htmlspecialchars($colaboradorSelecionado['chave_pix']) ?> (<?= htmlspecialchars($colaboradorSelecionado['tipo_chave_pix'] ?? 'Chave') ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 text-center">
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded bg-black bg-opacity-50 border border-secondary border-opacity-25">
                        <small class="text-secondary text-uppercase fw-semibold d-block">Atendimentos</small>
                        <span class="fs-4 fw-bold text-white"><?= count($transacoes) ?></span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded bg-black bg-opacity-50 border border-secondary border-opacity-25">
                        <small class="text-secondary text-uppercase fw-semibold d-block">Total Faturado</small>
                        <span class="fs-4 fw-bold text-gold">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded bg-black bg-opacity-50 border border-secondary border-opacity-25">
                        <small class="text-secondary text-uppercase fw-semibold d-block">Comissão (<?= number_format($comissaoPerc, 0) ?>%)</small>
                        <span class="fs-4 fw-bold text-success">R$ <?= number_format($comissaoDevida, 2, ',', '.') ?></span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded bg-black bg-opacity-50 border border-secondary border-opacity-25">
                        <small class="text-secondary text-uppercase fw-semibold d-block">Líquido Barbearia</small>
                        <span class="fs-4 fw-bold text-white">R$ <?= number_format($totalEntradas - $comissaoDevida, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Indicadores Gerais -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Faturamento Total</small>
                <h3 class="text-white fw-bold mb-0">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Recebido em Dinheiro</small>
                <h3 class="text-gold fw-bold mb-0">R$ <?= number_format($totalDinheiro, 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Recebido via PIX / Gateway</small>
                <h3 class="text-success fw-bold mb-0">R$ <?= number_format($totalPix, 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted d-block text-uppercase fw-semibold">Troco Total Devolvido</small>
                <h3 class="text-secondary fw-bold mb-0">R$ <?= number_format($totalTrocoDevolvido, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <!-- Filtros de Período e Funcionário -->
    <div class="barber-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-secondary">Data Início</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($dataInicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Data Fim</label>
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($dataFim) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Filtrar por Profissional</label>
                <select name="funcionario_id" class="form-select form-select-sm">
                    <option value="">Todos os Funcionários</option>
                    <?php foreach ($colaboradores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($funcionarioId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?> (<?= ucfirst($c['perfil']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">Método</label>
                <select name="metodo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="dinheiro" <?= ($metodoFiltro === 'dinheiro') ? 'selected' : '' ?>>💵 Dinheiro</option>
                    <option value="pix_fixo" <?= ($metodoFiltro === 'pix_fixo') ? 'selected' : '' ?>>📱 PIX Fixo</option>
                    <option value="mercadopago_pix" <?= ($metodoFiltro === 'mercadopago_pix') ? 'selected' : '' ?>>⚡ Mercado Pago</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-gold w-100 py-1" title="Aplicar Filtros">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Transações -->
    <div class="barber-card p-0">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold">
                <i class="bi bi-list-check text-gold me-2"></i>Histórico Contábil de Transações
            </h5>
            <span class="text-secondary small">Total: <?= count($transacoes) ?> registro(s)</span>
        </div>

        <?php if (empty($transacoes)): ?>
            <div class="p-5 text-center">
                <i class="bi bi-receipt text-muted fs-1 mb-2 d-block"></i>
                <h6 class="text-white">Nenhuma transação financeira registrada neste intervalo.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead style="background: #111319;">
                        <tr>
                            <th class="ps-3">Data/Hora</th>
                            <th>Transação / Reserva</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Método</th>
                            <th>Valor Total</th>
                            <th>Dinheiro / Troco</th>
                            <th>Operador</th>
                            <th class="text-end pe-3">Comprovante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacoes as $t): ?>
                            <tr>
                                <td class="ps-3 small text-secondary">
                                    <?= date('d/m/Y H:i', strtotime($t['criado_em'])) ?>
                                </td>
                                <td>
                                    <strong class="text-white font-monospace d-block"><?= htmlspecialchars($t['codigo_transacao']) ?></strong>
                                    <?php if ($t['codigo_reserva']): ?>
                                        <small class="text-gold"><?= htmlspecialchars($t['codigo_reserva']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($t['cliente_nome'] ?? 'Cliente Avulso') ?></td>
                                <td><?= htmlspecialchars($t['funcionario_nome'] ?? '---') ?></td>
                                <td>
                                    <?php if ($t['metodo'] === 'dinheiro'): ?>
                                        <span class="badge bg-dark border border-warning text-gold">💵 Dinheiro</span>
                                    <?php elseif ($t['metodo'] === 'mercadopago_pix'): ?>
                                        <span class="badge bg-dark border border-danger text-danger">⚡ Mercado Pago</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark border border-secondary text-white">📱 PIX Fixo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-success fw-bold fs-6">
                                    R$ <?= number_format($t['valor_total'], 2, ',', '.') ?>
                                </td>
                                <td class="small">
                                    <?php if ($t['metodo'] === 'dinheiro' && $t['valor_recebido'] > 0): ?>
                                        <div>Recebido: R$ <?= number_format($t['valor_recebido'], 2, ',', '.') ?></div>
                                        <?php if ($t['troco'] > 0): ?>
                                            <span class="text-gold fw-bold">Troco: R$ <?= number_format($t['troco'], 2, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sem troco</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-secondary">---</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary">
                                    <?= htmlspecialchars($t['operador_nome'] ?? 'Sistema') ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($t['codigo_reserva']): ?>
                                        <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($t['codigo_reserva']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2" title="Abrir Comprovante Oficial">
                                            <i class="bi bi-file-earmark-text me-1"></i> Recibo
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-dark border border-secondary text-muted">Direto</span>
                                    <?php endif; ?>
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

