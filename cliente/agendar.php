<?php
/**
 * Agendamento de Atendimento - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Exigir login (se não estiver logado, vai para login e pode voltar)
exigirLogin(['cliente', 'recepcao', 'dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

// Buscar todos os serviços ativos
$stmtServicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC");
$servicos = $stmtServicos->fetchAll();

// Buscar barbeiros ativos
$stmtBarbeiros = $pdo->query("SELECT id, nome, especialidade FROM usuarios WHERE perfil = 'barbeiro' AND ativo = 1 ORDER BY nome ASC");
$barbeiros = $stmtBarbeiros->fetchAll();

// Serviço pré-selecionado via URL (se houver)
$servicoPreSelecionado = (int)($_GET['servico_id'] ?? 0);
$barbeiroPreSelecionado = (int)($_GET['barbeiro_id'] ?? 0);

$paginaTitulo = 'Agendar Atendimento | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb e Voltar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-gold text-decoration-none">Início</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cliente/dashboard.php" class="text-gold text-decoration-none">Área do Cliente</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Novo Agendamento</li>
            </ol>
        </nav>
        <a href="<?= BASE_URL ?>/cliente/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="row g-4">
        <!-- Coluna Formulário -->
        <div class="col-lg-8">
            <div class="barber-card p-4">
                <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary border-opacity-25 pb-3">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-plus fs-3"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Agendar Horário na Barbearia</h4>
                        <p class="text-secondary small mb-0">Escolha os procedimentos desejados, profissional e horário</p>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>/actions/agendamento_salvar.php" method="POST" id="formAgendamento">
                    
                    <?php if ($usuario['perfil'] !== 'cliente'): ?>
                        <!-- Identificação de Balcão para Recepção e Gestão -->
                        <div class="card p-3 mb-4 bg-dark bg-opacity-75 border border-secondary border-opacity-50 rounded-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-white fw-bold mb-0"><i class="bi bi-person-fill text-gold me-2"></i>Identificação do Cliente no Balcão</h6>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success">Balcão: Isento de Taxa</span>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="checkBalcaoAnonimo" name="cliente_anonimo" value="1" onchange="alternarClienteBalcao(this.checked)">
                                <label class="form-check-label text-white small fw-bold" for="checkBalcaoAnonimo">
                                    Agendamento Não Identificado (Cliente sem cadastro no balcão)
                                </label>
                            </div>
                            <div id="campoNomeAvulso" style="display: none;" class="mt-2">
                                <label class="form-label small text-secondary">Referência / Apelido do Cliente no Balcão</label>
                                <input type="text" class="form-control form-control-sm" name="cliente_nome_avulso" placeholder="Ex: Sr. Carlos / rapaz de camisa azul">
                                <small class="text-muted" style="font-size: 0.72rem;">Deixe vazio para registrar como "Cliente Balcão (Não Identificado)".</small>
                            </div>
                            <input type="hidden" name="origem" value="balcao">
                        </div>
                    <?php endif; ?>

                    <!-- PASSO 1: Escolha do Barbeiro -->
                    <div class="mb-4">
                        <label for="barbeiro_id" class="form-label fw-bold">
                            <i class="bi bi-person-badge text-gold me-1"></i> 1. Escolha o Barbeiro de Preferência
                        </label>
                        <select class="form-select py-3" id="barbeiro_id" name="barbeiro_id" required onchange="carregarServicosDoBarbeiro(this.value)">
                            <option value="">Selecione um barbeiro...</option>
                            <?php foreach ($barbeiros as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($b['id'] === $barbeiroPreSelecionado) ? 'selected' : '' ?>>
                                    ✂️ <?= htmlspecialchars($b['nome']) ?> (<?= htmlspecialchars($b['especialidade'] ?? 'Especialista') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- PASSO 2: Escolha de Procedimentos / Serviços -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">
                                <i class="bi bi-scissors text-gold me-1"></i> 2. Escolha os Procedimentos (Corte, Barba ou Ambos)
                            </label>
                            <span id="badgeTipoCatalogo" class="badge bg-danger text-white">Tabela da Barbearia</span>
                        </div>

                        <!-- Campo de Pesquisa em Tempo Real de Serviços -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-danger">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="buscaProcedimento" class="form-control" placeholder="Pesquisar corte, barba, sobrancelha, degradê..." aria-label="Pesquisar procedimentos" oninput="filtrarProcedimentosAgendar()">
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Digite para filtrar a lista de procedimentos abaixo instantaneamente.</small>
                        </div>

                        <div class="row g-3" id="containerListaProcedimentos">
                            <?php foreach ($servicos as $srv): ?>
                                <?php 
                                    $isCheck = ($srv['id'] === $servicoPreSelecionado);
                                ?>
                                <div class="col-md-6 item-servico-col">
                                    <div class="p-3 rounded border border-secondary border-opacity-25 h-100 card-servico-item" style="background: #111319; cursor: pointer;" onclick="toggleServico('global_<?= $srv['id'] ?>')">
                                        <div class="form-check d-flex align-items-start gap-2">
                                            <input class="form-check-input mt-1 check-servico" type="checkbox" name="servicos[]" value="global_<?= $srv['id'] ?>" id="srv_global_<?= $srv['id'] ?>" data-preco="<?= $srv['preco'] ?>" data-tempo="<?= $srv['tempo_minutos'] ?>" data-nome="<?= htmlspecialchars($srv['nome']) ?>" <?= $isCheck ? 'checked' : '' ?> onclick="event.stopPropagation(); atualizarResumo();">
                                            <label class="form-check-label w-100" for="srv_global_<?= $srv['id'] ?>" style="cursor: pointer;">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong class="text-white item-servico-nome"><?= htmlspecialchars($srv['nome']) ?></strong>
                                                    <span class="text-danger fw-bold">R$ <?= number_format($srv['preco'], 2, ',', '.') ?></span>
                                                </div>
                                                <small class="text-secondary d-block mt-1 item-servico-desc"><?= htmlspecialchars($srv['descricao']) ?></small>
                                                <small class="text-muted d-block mt-1"><i class="bi bi-clock me-1 text-danger"></i><?= $srv['tempo_minutos'] ?> min</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- PASSO 3: Data e Horário -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="data_agendamento" class="form-label">3. Data do Atendimento</label>
                            <input type="date" class="form-control py-3" id="data_agendamento" name="data_agendamento" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="horario" class="form-label">Horário Disponível</label>
                            <select class="form-select py-3" id="horario" name="horario" required>
                                <option value="">Escolha o horário...</option>
                                <?php
                                    $inicio = strtotime('08:00');
                                    $fim = strtotime('19:30');
                                    while ($inicio <= $fim) {
                                        $hora = date('H:i', $inicio);
                                        echo "<option value='{$hora}:00'>{$hora}</option>";
                                        $inicio = strtotime('+30 minutes', $inicio);
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- PASSO 4: Método de Pagamento -->
                    <div class="mb-4">
                        <label class="form-label">4. Forma de Pagamento</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-secondary border-opacity-25 d-flex align-items-center gap-3 w-100" style="background: #111319; cursor: pointer;">
                                    <input type="radio" name="metodo_pagamento" value="pix" class="form-check-input mt-0" checked>
                                    <div>
                                        <strong class="text-white d-block"><i class="bi bi-qr-code text-gold me-1"></i> PIX Instantâneo</strong>
                                        <small class="text-secondary">Gera QR Code Copia e Cola com confirmação</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-secondary border-opacity-25 d-flex align-items-center gap-3 w-100" style="background: #111319; cursor: pointer;">
                                    <input type="radio" name="metodo_pagamento" value="recepcao" class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-white d-block"><i class="bi bi-cash-coin text-gold me-1"></i> Pagar na Recepção</strong>
                                        <small class="text-secondary">Pague no balcão ao chegar na barbearia</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="mb-4">
                        <label for="observacoes" class="form-label">Observações ou Preferências (Opcional)</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2" placeholder="Ex: Preferência por fade alto, disfarçado na zero, barba bem desenhada..."></textarea>
                    </div>

                    <?php 
                        $horasMinimas = (float)($empresa['horas_minimas_cancelamento'] ?? 2);
                        $taxaCancelamento = (float)($empresa['taxa_cancelamento_percentual'] ?? 20);
                    ?>
                    <!-- Política de Cancelamento -->
                    <div class="card bg-dark bg-opacity-75 border border-secondary border-opacity-50 p-3 mb-4 rounded-3 shadow-sm">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-gold fs-4 mt-1"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <h6 class="text-white fw-bold mb-1">Política de Cancelamento & Reembolso</h6>
                                <p class="text-secondary small mb-2 lh-sm">
                                    • <strong>Cancelamento Gratuito:</strong> Reembolso integral ao cancelar com pelo menos <strong><?= $horasMinimas ?>h de antecedência</strong>.<br>
                                    • <strong>Cancelamento Próximo ao Horário:</strong> Para cancelamentos com menos de <?= $horasMinimas ?>h, poderá incidir taxa de até <strong><?= $taxaCancelamento ?>%</strong> pela reserva do profissional.<br>
                                    • <span class="text-muted">Tip: Em caso de imprevistos ou força maior, a administração poderá conceder isenção.</span>
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="concordo_cdc" required checked>
                                    <label class="form-check-label small text-white-50" for="concordo_cdc">
                                        Declaro estar ciente e de acordo com a política de cancelamento da barbearia.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-calendar-check me-2"></i> Confirmar Agendamento
                    </button>
                </form>
            </div>
        </div>

        <!-- Coluna Resumo Dinâmico -->
        <div class="col-lg-4">
            <div class="barber-card p-4 sticky-top" style="top: 100px;">
                <h5 class="text-white fw-bold border-bottom border-secondary border-opacity-25 pb-3 mb-3">
                    <i class="bi bi-receipt text-gold me-2"></i>Resumo do Pedido
                </h5>

                <div id="listaResumoServicos" class="mb-3">
                    <p class="text-secondary small fst-italic">Nenhum procedimento selecionado ainda.</p>
                </div>

                <div class="border-top border-secondary border-opacity-25 pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Tempo Estimado:</span>
                        <strong class="text-white" id="tempoEstimadoTotal">0 min</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                        <span class="text-white fw-bold">Total a Pagar:</span>
                        <span class="text-gold fs-3 fw-bold" id="valorTotalResumo">R$ 0,00</span>
                    </div>
                </div>

                <div class="mt-4 p-3 rounded bg-dark border border-secondary border-opacity-25">
                    <small class="text-secondary d-block">
                        <i class="bi bi-shield-check text-gold me-1"></i> <strong>Garantia de Horário:</strong> Chegue com 5 minutos de antecedência para desfrutar de nosso café expresso.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleServico(id) {
    const check = document.getElementById('srv_' + id);
    if (check) {
        check.checked = !check.checked;
        atualizarResumo();
    }
}

async function carregarServicosDoBarbeiro(barbeiroId) {
    if (!barbeiroId) return;
    try {
        const resp = await fetch('<?= BASE_URL ?>/api/servicos_barbeiro.php?barbeiro_id=' + encodeURIComponent(barbeiroId));
        const data = await resp.json();
        if (!data.success || !data.servicos) return;

        const badge = document.getElementById('badgeTipoCatalogo');
        if (data.usa_individual) {
            badge.className = 'badge bg-gold text-dark fw-bold';
            badge.innerText = '✂️ Catálogo Exclusivo do Barbeiro';
        } else {
            badge.className = 'badge bg-danger text-white';
            badge.innerText = 'Tabela Oficial da Barbearia';
        }

        const container = document.getElementById('containerListaProcedimentos');
        let html = '';
        data.servicos.forEach(srv => {
            html += `
                <div class="col-md-6 item-servico-col">
                    <div class="p-3 rounded border border-secondary border-opacity-25 h-100 card-servico-item" style="background: #111319; cursor: pointer;" onclick="toggleServico('${srv.id_input}')">
                        <div class="form-check d-flex align-items-start gap-2">
                            <input class="form-check-input mt-1 check-servico" type="checkbox" name="servicos[]" value="${srv.id_input}" id="srv_${srv.id_input}" data-preco="${srv.preco}" data-tempo="${srv.tempo_minutos}" data-nome="${srv.nome}" onclick="event.stopPropagation(); atualizarResumo();">
                            <label class="form-check-label w-100" for="srv_${srv.id_input}" style="cursor: pointer;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-white item-servico-nome">${srv.nome}</strong>
                                    <span class="text-danger fw-bold">R$ ${srv.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                                </div>
                                <small class="text-secondary d-block mt-1 item-servico-desc">${srv.descricao || ''}</small>
                                <small class="text-muted d-block mt-1"><i class="bi bi-clock me-1 text-danger"></i>${srv.tempo_minutos} min</small>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        atualizarResumo();
    } catch(e) {
        console.error('Erro ao carregar serviços do barbeiro:', e);
    }
}

function atualizarResumo() {
    const checks = document.querySelectorAll('.check-servico:checked');
    const containerLista = document.getElementById('listaResumoServicos');
    let totalPreco = 0;
    let totalTempo = 0;

    if (checks.length === 0) {
        containerLista.innerHTML = '<p class="text-secondary small fst-italic">Nenhum procedimento selecionado ainda.</p>';
        document.getElementById('tempoEstimadoTotal').innerText = '0 min';
        document.getElementById('valorTotalResumo').innerText = 'R$ 0,00';
        return;
    }

    let html = '<ul class="list-unstyled mb-0">';
    checks.forEach(chk => {
        const preco = parseFloat(chk.dataset.preco);
        const tempo = parseInt(chk.dataset.tempo);
        const nome = chk.dataset.nome;
        totalPreco += preco;
        totalTempo += tempo;

        html += `
            <li class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-light">• ${nome}</span>
                <span class="text-gold fw-semibold">R$ ${preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
            </li>
        `;
    });
    html += '</ul>';

    containerLista.innerHTML = html;
    document.getElementById('tempoEstimadoTotal').innerText = totalTempo + ' min';
    document.getElementById('valorTotalResumo').innerText = 'R$ ' + totalPreco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

document.getElementById('formAgendamento').addEventListener('submit', function (e) {
    const checks = document.querySelectorAll('.check-servico:checked');
    if (checks.length === 0) {
        e.preventDefault();
        abrirModalConfirmacao(
            'Selecione um Procedimento',
            'Por favor, marque pelo menos um procedimento (corte, barba ou outro serviço) para prosseguir com o agendamento.',
            null,
            'Entendido',
            'btn-warning'
        );
        return false;
    }
});

// Inicializar resumo ao carregar página
document.addEventListener('DOMContentLoaded', atualizarResumo);

function filtrarProcedimentosAgendar() {
    const termo = (document.getElementById('buscaProcedimento').value || '').trim().toLowerCase();
    const cols = document.querySelectorAll('.item-servico-col');
    cols.forEach(col => {
        const nome = col.querySelector('.item-servico-nome').innerText.toLowerCase();
        const desc = col.querySelector('.item-servico-desc').innerText.toLowerCase();
        if (nome.includes(termo) || desc.includes(termo)) {
            col.style.display = '';
        } else {
            col.style.display = 'none';
        }
    });
}
function alternarClienteBalcao(isAnonimo) {
    const campo = document.getElementById('campoNomeAvulso');
    if (campo) {
        campo.style.display = isAnonimo ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
