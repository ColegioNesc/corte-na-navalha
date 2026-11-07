<?php
/**
 * Componente Reutilizável: Agendamento Rápido no Balcão
 * Compartilhado entre Recepção e Dono/Administração
 * Barbearia Corte na Navalha
 */

// Garantir variáveis necessárias
if (!isset($barbeiros)) {
    $stmtB = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'barbeiro' AND ativo = 1 ORDER BY nome ASC");
    $barbeiros = $stmtB->fetchAll();
}
if (!isset($clientesCadastrados)) {
    $stmtC = $pdo->query("SELECT id, nome, email, cpf, telefone FROM usuarios WHERE perfil = 'cliente' AND email != 'balcao@cortenanavalha.local' ORDER BY nome ASC");
    $clientesCadastrados = $stmtC->fetchAll();
}
if (!isset($servicosGerais)) {
    $stmtS = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC");
    $servicosGerais = $stmtS->fetchAll();
}

$urlCadastroRapido = BASE_URL . '/cadastro_rapido.php';
$qrCodeCadastroUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($urlCadastroRapido);
$redirectAposSalvar = htmlspecialchars($_SERVER['REQUEST_URI']);
?>

<div class="barber-card p-4">
    <!-- Cabeçalho do Agendamento de Balcão (Sem a coluna estática do QR Code para não ocupar espaço) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25 gap-2">
        <div>
            <h5 class="text-white fw-bold mb-1">
                <i class="bi bi-calendar-plus text-gold me-2"></i>Novo Atendimento de Balcão
            </h5>
            <p class="text-secondary small mb-0">Atenda clientes presentes na barbearia, identificados ou não</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="abrirModalQrBalcao()" title="Exibir QR Code na tela">
                <i class="bi bi-qr-code me-1"></i> QR Code para o Cliente
            </button>
            <span class="badge bg-success bg-opacity-25 text-success border border-success p-2 small">
                <i class="bi bi-shield-check me-1"></i> Balcão Isento de Taxa
            </span>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/actions/agendamento_salvar.php" method="POST" id="formAgendamentoBalcaoComponente">
        <input type="hidden" name="origem" value="balcao">
        <input type="hidden" name="redirect" value="<?= $redirectAposSalvar ?>">

        <!-- Bloco de Identificação do Cliente -->
        <div class="card p-3 mb-3 bg-dark bg-opacity-75 border border-secondary border-opacity-50 rounded-3">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="checkClienteAnonimoBalcao" name="cliente_anonimo" value="1" onchange="alternarIdentificacaoBalcao(this.checked)">
                <label class="form-check-label text-white fw-bold small" for="checkClienteAnonimoBalcao">
                    Cliente sem cadastro no momento
                </label>
            </div>
            <small class="text-secondary d-block lh-sm mb-2">
                Tip: marque esta opção para registrar o atendimento rapidamente sem vincular a um cadastro formal.
            </small>

            <!-- Bloco Cliente Cadastrado -->
            <div id="blocoClienteCadastradoBalcao">
                <label class="form-label small text-secondary">Selecionar Cliente Cadastrado</label>
                <select class="form-select form-select-sm" name="cliente_id" id="balcaoClienteIdSelect">
                    <option value="">Selecione o cliente na lista...</option>
                    <?php foreach ($clientesCadastrados as $cli): ?>
                        <option value="<?= $cli['id'] ?>">
                            <?= htmlspecialchars($cli['nome']) ?> (CPF: <?= htmlspecialchars($cli['cpf'] ?? '---') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" style="font-size: 0.72rem;">Tip: o cliente também pode escanear o QR Code acima para cadastro expresso.</small>
            </div>

            <!-- Bloco Cliente Não Identificado -->
            <div id="blocoClienteAnonimoBalcao" style="display: none;">
                <label class="form-label small text-secondary">Identificação do Cliente no Balcão</label>
                <input type="text" class="form-control form-control-sm" name="cliente_nome_avulso" id="balcaoNomeAvulsoInput" placeholder="Ex: Sr. Carlos / cliente avulso">
                <small class="text-muted" style="font-size: 0.72rem;">Tip: opcional, preencha se desejar identificar o cliente na fila.</small>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <!-- Barbeiro -->
            <div class="col-md-6">
                <label class="form-label small text-secondary">Barbeiro Responsável <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" name="barbeiro_id" required>
                    <option value="">Escolha o barbeiro...</option>
                    <?php foreach ($barbeiros as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data -->
            <div class="col-md-3">
                <label class="form-label small text-secondary">Data <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm" name="data_agendamento" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <!-- Horário -->
            <div class="col-md-3">
                <label class="form-label small text-secondary">Horário <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" name="horario" required>
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

        <!-- Procedimentos -->
        <div class="mb-3">
            <label class="form-label small text-secondary d-block">Procedimento(s) Selecionado(s) <span class="text-danger">*</span></label>
            <div class="row g-2" style="max-height: 180px; overflow-y: auto;">
                <?php foreach ($servicosGerais as $sg): ?>
                    <div class="col-md-6 col-lg-4">
                        <label class="p-2 rounded border border-secondary border-opacity-25 bg-dark d-flex align-items-center gap-2 w-100" style="cursor: pointer; font-size: 0.85rem;">
                            <input type="checkbox" name="servicos[]" value="global_<?= $sg['id'] ?>" class="form-check-input mt-0 check-servico-balcao">
                            <div class="d-flex justify-content-between w-100">
                                <span class="text-white"><?= htmlspecialchars($sg['nome']) ?></span>
                                <strong class="text-gold">R$ <?= number_format($sg['preco'], 2, ',', '.') ?></strong>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Forma de Pagamento no Balcão -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small text-secondary">Forma de Pagamento</label>
                <select class="form-select form-select-sm" name="metodo_pagamento">
                    <option value="recepcao">💵 Pagar no Balcão (Dinheiro / Cartão presencial)</option>
                    <option value="pix">📱 PIX Balcão (QR Code)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-secondary">Observações / Preferências</label>
                <input type="text" class="form-control form-control-sm" name="observacoes" placeholder="Ex: Cliente com pressa, barba desenhada...">
            </div>
        </div>

        <!-- Informação de Balcão -->
        <div class="p-2 px-3 rounded bg-dark border border-secondary border-opacity-25 small text-secondary mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-success fs-6"></i>
            <span>Atendimentos presenciais de balcão são isentos de taxa de cancelamento.</span>
        </div>

        <button type="submit" class="btn btn-gold btn-sm w-100 py-2 fw-bold fs-6">
            <i class="bi bi-calendar-check me-1"></i> Concluir Agendamento no Balcão
        </button>
    </form>
</div>

<!-- Modal QR Code de Cadastro Rápido -->
<div class="modal fade" id="modalQrBalcao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card text-center p-4">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <h4 class="brand-font text-gold mb-1"><?= htmlspecialchars($empresa['nome_barbearia'] ?? 'CORTE NA NAVALHA') ?></h4>
                <p class="text-secondary small mb-3">Aponte a câmera do celular para realizar seu cadastro</p>

                <div class="p-3 bg-white rounded-3 d-inline-block shadow my-2">
                    <img src="<?= $qrCodeCadastroUrl ?>" alt="QR Code Cadastro Rápido" class="img-fluid" style="width: 250px; height: 250px;">
                </div>

                <div class="mt-3 p-2 rounded bg-dark border border-secondary border-opacity-25 small text-secondary">
                    <i class="bi bi-phone me-1 text-gold"></i> Dados solicitados: <strong>Nome, E-mail e CPF</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Imprimir Placa de Balcão
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Garante que o modal de QR Code seja movido diretamente para o <body>
// Isso evita que ele fique preso dentro de abas com display:none (Bootstrap tab-pane)
(function() {
    function moverModalQrBalcaoParaBody() {
        const modalEl = document.getElementById('modalQrBalcao');
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', moverModalQrBalcaoParaBody);
    } else {
        moverModalQrBalcaoParaBody();
    }
})();

function alternarIdentificacaoBalcao(isAnonimo) {
    const blocoCadastrado = document.getElementById('blocoClienteCadastradoBalcao');
    const blocoAnonimo = document.getElementById('blocoClienteAnonimoBalcao');
    const selectCliente = document.getElementById('balcaoClienteIdSelect');

    if (isAnonimo) {
        if (blocoCadastrado) blocoCadastrado.style.display = 'none';
        if (blocoAnonimo) blocoAnonimo.style.display = 'block';
        if (selectCliente) {
            selectCliente.removeAttribute('required');
            selectCliente.value = '';
        }
    } else {
        if (blocoCadastrado) blocoCadastrado.style.display = 'block';
        if (blocoAnonimo) blocoAnonimo.style.display = 'none';
    }
}

function abrirModalQrBalcao() {
    let modalEl = document.getElementById('modalQrBalcao');
    if (!modalEl) {
        console.warn('Elemento #modalQrBalcao não encontrado no DOM.');
        return;
    }
    // Sempre move para o body se ainda estiver aninhado em algum tab-pane
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    } else {
        // Fallback caso Bootstrap ainda esteja inicializando
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }, 100);
    }
}

function copiarLinkCadastroRapido() {
    const url = '<?= $urlCadastroRapido ?>';
    navigator.clipboard.writeText(url).then(() => {
        showToast('success', 'Link Copiado!', 'Link do Cadastro Rápido copiado para a área de transferência.');
    }).catch(() => {
        showToast('info', 'Link do Cadastro', url);
    });
}
</script>

