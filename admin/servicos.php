<?php
/**
 * Gestão da Tabela de Preços e Procedimentos - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$pdo = getDBConnection();

// Buscar todos os serviços
$servicos = $pdo->query("SELECT * FROM servicos ORDER BY categoria ASC, preco ASC")->fetchAll();

$paginaTitulo = 'Tabela de Preços & Procedimentos | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Tabela de Preços & Procedimentos</h4>
                        <p class="text-secondary small mb-0">Gerencie valores de cortes, barbas, combos e tempos de atendimento</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-gold py-2 px-3" onclick="abrirModalServico()">
                    <i class="bi bi-plus-circle me-1"></i> Adicionar Novo Procedimento
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Serviços -->
    <div class="barber-card p-0">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold"><i class="bi bi-scissors text-gold me-2"></i>Procedimentos da Barbearia</h5>
            <span class="text-secondary small">Total: <?= count($servicos) ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead style="background: #111319;">
                    <tr>
                        <th class="ps-3">Ícone</th>
                        <th>Procedimento</th>
                        <th>Categoria</th>
                        <th>Tempo Médio</th>
                        <th>Preço Vigente</th>
                        <th>Destaque</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $s): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                    <i class="bi <?= htmlspecialchars($s['icone'] ?? 'bi-scissors') ?>"></i>
                                </div>
                            </td>
                            <td>
                                <strong class="text-white d-block"><?= htmlspecialchars($s['nome']) ?></strong>
                                <small class="text-secondary"><?= htmlspecialchars($s['descricao']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-uppercase">
                                    <?= htmlspecialchars($s['categoria']) ?>
                                </span>
                            </td>
                            <td><i class="bi bi-clock me-1 text-gold"></i> <?= (int)$s['tempo_minutos'] ?> min</td>
                            <td class="text-gold fw-bold fs-6">R$ <?= number_format($s['preco'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($s['destaque']): ?>
                                    <span class="badge bg-gold text-dark fw-bold">Mais Pedido</span>
                                <?php else: ?>
                                    <span class="text-muted small">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['ativo']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-gold py-1 px-2" onclick='editarServico(<?= json_encode($s) ?>)'>
                                    <i class="bi bi-pencil me-1"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Cadastro / Edição de Serviço -->
<div class="modal fade" id="modalServico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-gold" id="modalServicoTitulo">Procedimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/servico_salvar.php" method="POST">
                <input type="hidden" name="id" id="srvId" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Procedimento</label>
                        <input type="text" class="form-control" name="nome" id="srvNome" required placeholder="Ex: Degradê Navalhado">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição Detalhada</label>
                        <textarea class="form-control" name="descricao" id="srvDescricao" rows="2" placeholder="Descreva os diferenciais e acabamento..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Preço (R$)</label>
                            <input type="number" step="0.50" min="1" class="form-control" name="preco" id="srvPreco" required placeholder="50.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duração (Minutos)</label>
                            <input type="number" step="5" min="5" class="form-control" name="tempo_minutos" id="srvTempo" value="30" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" name="categoria" id="srvCategoria">
                                <option value="cabelo">Cabelo</option>
                                <option value="barba">Barba</option>
                                <option value="combo">Combo</option>
                                <option value="especial">Especial</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ícone Bootstrap</label>
                            <select class="form-select" name="icone" id="srvIcone">
                                <option value="bi-scissors">✂️ Tesoura (bi-scissors)</option>
                                <option value="bi-lightning-charge">⚡ Raio (bi-lightning-charge)</option>
                                <option value="bi-person-badge">🧔 Barba (bi-person-badge)</option>
                                <option value="bi-star-fill">⭐ Combo (bi-star-fill)</option>
                                <option value="bi-eye">👁️ Sobrancelha (bi-eye)</option>
                                <option value="bi-palette">🎨 Pigmentação (bi-palette)</option>
                                <option value="bi-droplet">💧 Lavagem (bi-droplet)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="destaque" id="srvDestaque" value="1">
                            <label class="form-check-label text-secondary small" for="srvDestaque">
                                Destacar como "Mais Pedido"
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" id="srvAtivo" value="1" checked>
                            <label class="form-check-label text-secondary small" for="srvAtivo">
                                Serviço Ativo no Cardápio
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-check2-circle me-1"></i> Salvar na Tabela
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalServico() {
    document.getElementById('modalServicoTitulo').innerText = 'Novo Procedimento';
    document.getElementById('srvId').value = 0;
    document.getElementById('srvNome').value = '';
    document.getElementById('srvDescricao').value = '';
    document.getElementById('srvPreco').value = '';
    document.getElementById('srvTempo').value = 30;
    document.getElementById('srvCategoria').value = 'cabelo';
    document.getElementById('srvIcone').value = 'bi-scissors';
    document.getElementById('srvDestaque').checked = false;
    document.getElementById('srvAtivo').checked = true;
    new bootstrap.Modal(document.getElementById('modalServico')).show();
}

function editarServico(s) {
    document.getElementById('modalServicoTitulo').innerText = 'Editar Procedimento';
    document.getElementById('srvId').value = s.id;
    document.getElementById('srvNome').value = s.nome;
    document.getElementById('srvDescricao').value = s.descricao || '';
    document.getElementById('srvPreco').value = parseFloat(s.preco).toFixed(2);
    document.getElementById('srvTempo').value = s.tempo_minutos;
    document.getElementById('srvCategoria').value = s.categoria;
    document.getElementById('srvIcone').value = s.icone || 'bi-scissors';
    document.getElementById('srvDestaque').checked = (s.destaque == 1);
    document.getElementById('srvAtivo').checked = (s.ativo == 1);
    new bootstrap.Modal(document.getElementById('modalServico')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
