<?php
/**
 * Modal Global de Pagamento PIX com QR Code Dinâmico e Escuta em Tempo Real
 * Barbearia Corte na Navalha
 */
?>
<div class="modal fade" id="modalQrCodeGlobal" tabindex="-1" aria-labelledby="modalQrCodeGlobalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card border-danger shadow-lg">
            <!-- Cabeçalho com Tema Vermelho Barbearia -->
            <div class="modal-header border-bottom border-danger border-opacity-50 py-3" style="background: linear-gradient(135deg, rgba(220, 38, 38, 0.25) 0%, rgba(153, 27, 27, 0.1) 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-2 bg-danger text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-qr-code fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title brand-font text-white fw-bold mb-0" id="modalQrCodeGlobalLabel">PAGAMENTO PIX INSTANTÂNEO</h5>
                        <small class="text-danger-subtle" style="font-size: 0.75rem;">Corte na Navalha Barbearia</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar" onclick="pararEscutaPagamento()"></button>
            </div>

            <div class="modal-body py-4 text-center">
                <!-- Info do Atendimento -->
                <div class="p-3 rounded mb-3 text-start border border-secondary border-opacity-25" style="background: rgba(0,0,0,0.35);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small">Reserva: <strong id="modalQrReservaCodigo" class="text-danger font-monospace">CN-...</strong></span>
                        <span id="modalQrStatusBadge" class="badge bg-warning text-dark text-uppercase">AGUARDANDO PAGAMENTO</span>
                    </div>
                    <div class="fw-bold text-white fs-6 mb-1" id="modalQrProcedimentos">Procedimentos</div>
                    <div class="d-flex justify-content-between small text-secondary">
                        <span>Cliente: <strong id="modalQrClienteNome" class="text-white">-</strong></span>
                        <span>Profissional: <strong id="modalQrBarbeiroNome" class="text-danger">-</strong></span>
                    </div>
                </div>

                <!-- Valor em Destaque Vermelho -->
                <div class="mb-3">
                    <small class="text-secondary text-uppercase fw-semibold d-block" style="letter-spacing: 1px;">Total a Pagar</small>
                    <h2 class="text-danger fw-bold display-6 mb-0" id="modalQrValorDisplay">R$ 0,00</h2>
                </div>

                <!-- Box da Imagem do QR Code -->
                <div class="position-relative d-inline-block p-3 bg-white rounded-3 shadow mb-3 border border-2 border-danger">
                    <div id="modalQrSpinner" class="position-absolute top-50 start-50 translate-middle text-danger d-none">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Gerando QR Code...</span>
                        </div>
                    </div>
                    <img id="modalQrImagem" src="" alt="QR Code PIX" style="width: 220px; height: 220px; display: block; object-fit: contain;">
                </div>

                <!-- Escutando Pagamento -->
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3" id="modalQrEscutaStatus">
                    <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                    <span class="text-secondary small fw-semibold">Escutando confirmação de pagamento...</span>
                </div>

                <!-- Mensagem de Sucesso (Oculta até pagar) -->
                <div id="modalQrSucessoBox" class="alert alert-success d-none py-2 px-3 mb-3 text-start">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        <div>
                            <strong class="d-block">Pagamento Confirmado com Sucesso!</strong>
                            <small>O status do atendimento foi atualizado para quitado.</small>
                        </div>
                    </div>
                </div>

                <!-- Campo PIX Copia e Cola Descrito -->
                <div class="text-start mb-2">
                    <label for="modalQrCopiaCola" class="form-label small fw-bold text-uppercase d-flex justify-content-between align-items-center mb-1">
                        <span><i class="bi bi-clipboard-data text-danger me-1"></i> Chave PIX Copia e Cola (Oficial):</span>
                        <span class="text-muted" style="font-size: 0.75rem;">Clique no botão para copiar</span>
                    </label>
                    <div class="input-group">
                        <input type="text" id="modalQrCopiaCola" class="form-control font-monospace form-control-sm text-light bg-dark border-secondary" readonly placeholder="Código PIX dinâmico do atendimento">
                        <button class="btn btn-danger btn-sm px-3 fw-bold" type="button" id="btnCopiarPixModal" onclick="copiarPixModal()">
                            <i class="bi bi-copy me-1"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="pararEscutaPagamento()">
                    Fechar
                </button>

                <!-- Botão de Confirmação Rápida de Pagamento -->
                <form id="formConfirmarPagamentoModal" action="<?= BASE_URL ?>/actions/agendamento_pagar.php" method="POST" class="d-inline">
                    <input type="hidden" name="agendamento_id" id="modalQrAgendamentoIdInput" value="">
                    <input type="hidden" name="redirect" id="modalQrRedirectInput" value="">
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Confirmar Recebimento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let timerEscutaPagamento = null;
let agendamentoIdAtual = 0;

/**
 * Abre o Modal de QR Code para qualquer atendimento e inicia escuta
 */
function abrirModalQrCode(agendamentoId, clienteNome = '', valor = '', procedimentos = '', codigoReserva = '') {
    agendamentoIdAtual = agendamentoId;
    document.getElementById('modalQrAgendamentoIdInput').value = agendamentoId;
    document.getElementById('modalQrRedirectInput').value = window.location.href;
    
    // Resetar UI
    document.getElementById('modalQrSucessoBox').classList.add('d-none');
    document.getElementById('modalQrEscutaStatus').classList.remove('d-none');
    document.getElementById('modalQrSpinner').classList.remove('d-none');
    document.getElementById('modalQrImagem').style.opacity = '0.3';
    
    if (clienteNome) document.getElementById('modalQrClienteNome').innerText = clienteNome;
    if (valor) document.getElementById('modalQrValorDisplay').innerText = valor.startsWith('R$') ? valor : 'R$ ' + valor;
    if (procedimentos) document.getElementById('modalQrProcedimentos').innerText = procedimentos;
    if (codigoReserva) document.getElementById('modalQrReservaCodigo').innerText = codigoReserva;

    const modalEl = document.getElementById('modalQrCodeGlobal');
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();

    // Buscar dados atualizados via API e escutar
    carregarDadosPix(agendamentoId);
    iniciarEscutaPagamento(agendamentoId);
}

function carregarDadosPix(id) {
    fetch('<?= BASE_URL ?>/api/agendamento_pix.php?id=' + encodeURIComponent(id))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalQrImagem').src = data.qr_code_url;
                document.getElementById('modalQrImagem').style.opacity = '1';
                document.getElementById('modalQrSpinner').classList.add('d-none');
                document.getElementById('modalQrCopiaCola').value = data.pix_copia_cola;
                document.getElementById('modalQrValorDisplay').innerText = data.valor_formatado;
                document.getElementById('modalQrClienteNome').innerText = data.cliente_nome;
                document.getElementById('modalQrBarbeiroNome').innerText = data.barbeiro_nome;
                document.getElementById('modalQrProcedimentos').innerText = data.procedimentos;
                document.getElementById('modalQrReservaCodigo').innerText = data.codigo_reserva;

                if (data.status_pagamento === 'pago') {
                    marcarComoPagoNoModal();
                }
            } else {
                showToast('danger', 'Erro no QR Code', data.message || 'Falha ao gerar o QR Code.');
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('modalQrSpinner').classList.add('d-none');
        });
}

function iniciarEscutaPagamento(id) {
    pararEscutaPagamento();
    timerEscutaPagamento = setInterval(() => {
        fetch('<?= BASE_URL ?>/api/agendamento_pix.php?id=' + encodeURIComponent(id))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.status_pagamento === 'pago') {
                    marcarComoPagoNoModal();
                    pararEscutaPagamento();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                }
            })
            .catch(() => {});
    }, 3000);
}

function pararEscutaPagamento() {
    if (timerEscutaPagamento) {
        clearInterval(timerEscutaPagamento);
        timerEscutaPagamento = null;
    }
}

function marcarComoPagoNoModal() {
    document.getElementById('modalQrEscutaStatus').classList.add('d-none');
    document.getElementById('modalQrSucessoBox').classList.remove('d-none');
    const badge = document.getElementById('modalQrStatusBadge');
    badge.className = 'badge bg-success text-white text-uppercase';
    badge.innerText = 'PAGAMENTO CONFIRMADO';
    showToast('success', 'Pagamento Recebido!', 'O pagamento via PIX foi confirmado com sucesso.');
}

function copiarPixModal() {
    const input = document.getElementById('modalQrCopiaCola');
    if (!input || !input.value) return;
    copiarParaClipboard(input.value, 'Código PIX copiado! Cole no aplicativo do seu banco.');
    
    const btn = document.getElementById('btnCopiarPixModal');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Copiado!';
    btn.classList.replace('btn-danger', 'btn-success');
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.replace('btn-success', 'btn-danger');
    }, 2000);
}
</script>
