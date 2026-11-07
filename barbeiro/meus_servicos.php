<?php
/**
 * Meus Serviços & Preços - Catálogo Individual do Barbeiro
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

// Verificar se o catálogo individual está liberado
$stmtMe = $pdo->prepare("SELECT permite_catalogo_proprio, especialidade FROM usuarios WHERE id = :id LIMIT 1");
$stmtMe->execute([':id' => $usuario['id']]);
$barbeiroData = $stmtMe->fetch();
$catalogoLiberado = ($empresa['tipo_catalogo'] === 'individual') || !empty($barbeiroData['permite_catalogo_proprio']);

// Buscar serviços individuais do barbeiro
$meusServicos = [];
if ($catalogoLiberado) {
    $stmtSrv = $pdo->prepare("SELECT * FROM barbeiro_servicos WHERE barbeiro_id = :id ORDER BY categoria ASC, preco ASC");
    $stmtSrv->execute([':id' => $usuario['id']]);
    $meusServicos = $stmtSrv->fetchAll();
}

// Buscar serviços padrão da barbearia para comparação/importação
$servicosGerais = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC")->fetchAll();

$paginaTitulo = 'Meus Procedimentos & Preços | Portal do Barbeiro';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/barbeiro/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar ao Painel do Barbeiro">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Catálogo de Procedimentos do Mestre <?= htmlspecialchars($usuario['nome']) ?></h4>
                        <p class="text-secondary small mb-0">
                            Especialidade: <span class="text-gold"><?= htmlspecialchars($barbeiroData['especialidade'] ?? 'Barbeiro Profissional') ?></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <?php if ($catalogoLiberado): ?>
                    <button type="button" class="btn btn-gold btn-sm py-2 px-3" onclick="abrirModalMeuServico()">
                        <i class="bi bi-plus-circle me-1"></i> Adicionar Procedimento
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$catalogoLiberado): ?>
        <!-- Mensagem de Catálogo Global em Vigor -->
        <div class="barber-card p-4 p-md-5 text-center mb-4">
            <div class="stat-icon mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2.2rem;">
                <i class="bi bi-shield-lock text-gold"></i>
            </div>
            <h4 class="text-white fw-bold">Tabela Geral Unificada Vigente</h4>
            <p class="text-secondary mx-auto mb-4" style="max-width: 650px;">
                A barbearia está atualmente configurada pelo proprietário com uma <strong>tabela de preços tabelada e padronizada</strong>. 
                Seus atendimentos seguem os valores oficiais cadastrados pela casa. Caso deseje praticar preços personalizados ou adicionar cortes exclusivos seus, solicite ao Dono a liberação do seu catálogo individual.
            </p>
            <div class="p-3 bg-dark bg-opacity-50 rounded border border-secondary border-opacity-25 d-inline-block text-start">
                <small class="text-gold fw-bold d-block mb-1"><i class="bi bi-info-circle me-1"></i> Procedimentos Oficiais que você realiza atualmente:</small>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach ($servicosGerais as $sg): ?>
                        <span class="badge bg-dark border border-secondary p-2">
                            <?= htmlspecialchars($sg['nome']) ?> - <strong class="text-white">R$ <?= number_format($sg['preco'], 2, ',', '.') ?></strong>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Catálogo Individual Liberado -->
        <div class="barber-card p-0 mb-4">
            <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                <h5 class="text-white mb-0 fw-bold">
                    <i class="bi bi-list-stars text-gold me-2"></i>Seus Procedimentos Ativos no Aplicativo
                </h5>
                <span class="badge bg-gold text-dark fw-bold">Catálogo Individual Ativo</span>
            </div>

            <?php if (empty($meusServicos)): ?>
                <div class="p-5 text-center">
                    <i class="bi bi-tag text-muted fs-1 mb-2 d-block"></i>
                    <h5 class="text-white">Você ainda não cadastrou procedimentos próprios.</h5>
                    <p class="text-secondary small mb-3">Enquanto não cadastrar, os clientes visualizarão a tabela padrão da barbearia ao agendar com você.</p>
                    <button type="button" class="btn btn-gold btn-sm" onclick="abrirModalMeuServico()">
                        <i class="bi bi-plus-circle me-1"></i> Cadastrar Meu Primeiro Procedimento
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead style="background: #111319;">
                            <tr>
                                <th class="ps-3">Procedimento</th>
                                <th>Categoria</th>
                                <th>Duração Estimada</th>
                                <th>Preço Praticado</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meusServicos as $srv): ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong class="text-white d-block"><?= htmlspecialchars($srv['nome']) ?></strong>
                                        <small class="text-secondary"><?= htmlspecialchars($srv['descricao']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary text-uppercase">
                                            <?= htmlspecialchars($srv['categoria']) ?>
                                        </span>
                                    </td>
                                    <td><i class="bi bi-clock me-1 text-gold"></i> <?= (int)$srv['tempo_minutos'] ?> min</td>
                                    <td class="text-gold fw-bold fs-6">R$ <?= number_format($srv['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($srv['ativo']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-gold py-1 px-2" onclick='editarMeuServico(<?= json_encode($srv) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form id="formExcluirSrv_<?= $srv['id'] ?>" action="<?= BASE_URL ?>/actions/barbeiro_servico_excluir.php" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" title="Excluir Procedimento" onclick="confirmarExclusaoServico('formExcluirSrv_<?= $srv['id'] ?>', '<?= htmlspecialchars($srv['nome'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Cadastrar / Editar Meu Procedimento -->
<div class="modal fade" id="modalMeuServico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-gold" id="modalMeuServicoTitulo">Meu Procedimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/barbeiro_servico_salvar.php" method="POST">
                <input type="hidden" name="id" id="meuSrvId" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Procedimento</label>
                        <input type="text" class="form-control" name="nome" id="meuSrvNome" required placeholder="Ex: Fade Navalhado Exclusivo">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição / Diferenciais</label>
                        <textarea class="form-control" name="descricao" id="meuSrvDescricao" rows="2" placeholder="Detalhes da sua técnica ou produtos utilizados..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Preço (R$)</label>
                            <input type="number" step="0.50" min="1" class="form-control" name="preco" id="meuSrvPreco" required placeholder="60.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duração (Minutos)</label>
                            <input type="number" step="5" min="5" class="form-control" name="tempo_minutos" id="meuSrvTempo" value="35" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria" id="meuSrvCategoria">
                            <option value="cabelo">Cabelo</option>
                            <option value="barba">Barba</option>
                            <option value="combo">Combo</option>
                            <option value="especial">Especial</option>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ativo" id="meuSrvAtivo" value="1" checked>
                        <label class="form-check-label text-white small" for="meuSrvAtivo">
                            Disponível para agendamento online
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-check2-circle me-1"></i> Salvar no Meu Catálogo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalMeuServico() {
    document.getElementById('modalMeuServicoTitulo').innerText = 'Adicionar Meu Procedimento';
    document.getElementById('meuSrvId').value = 0;
    document.getElementById('meuSrvNome').value = '';
    document.getElementById('meuSrvDescricao').value = '';
    document.getElementById('meuSrvPreco').value = '';
    document.getElementById('meuSrvTempo').value = '35';
    document.getElementById('meuSrvCategoria').value = 'cabelo';
    document.getElementById('meuSrvAtivo').checked = true;
    new bootstrap.Modal(document.getElementById('modalMeuServico')).show();
}

function editarMeuServico(s) {
    document.getElementById('modalMeuServicoTitulo').innerText = 'Editar Meu Procedimento';
    document.getElementById('meuSrvId').value = s.id;
    document.getElementById('meuSrvNome').value = s.nome;
    document.getElementById('meuSrvDescricao').value = s.descricao || '';
    document.getElementById('meuSrvPreco').value = s.preco;
    document.getElementById('meuSrvTempo').value = s.tempo_minutos;
    document.getElementById('meuSrvCategoria').value = s.categoria;
    document.getElementById('meuSrvAtivo').checked = parseInt(s.ativo) === 1;
    new bootstrap.Modal(document.getElementById('modalMeuServico')).show();
}

function confirmarExclusaoServico(formId, nome) {
    abrirModalConfirmacao(
        'Excluir Procedimento',
        `Deseja realmente remover o procedimento <strong>${nome}</strong> do seu catálogo?`,
        function() {
            document.getElementById(formId).submit();
        },
        'Sim, Excluir',
        'btn-danger'
    );
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

