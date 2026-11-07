<?php
/**
 * Modal Interativo de Caixa com Calculadora de Troco
 * Barbearia Corte na Navalha (Conforme mods.md)
 */
?>
<div class="modal fade" id="modalCaixaReceber" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card border-danger">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1.2rem;">
                        <i class="bi bi-cash-stack text-gold"></i>
                    </div>
                    <div>
                        <h5 class="modal-title brand-font text-white mb-0">Caixa & Recebimento Presencial</h5>
                        <small class="text-secondary">Registro de entrada com cálculo automático de troco</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/caixa_receber.php" method="POST" id="formCaixaReceber">
                <input type="hidden" name="agendamento_id" id="caixaAgendamentoId" value="">
                <input type="hidden" name="redirect" id="caixaRedirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

                <div class="modal-body p-4">
                    <!-- Resumo do Atendimento -->
                    <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small">Cliente:</span>
                            <strong class="text-white" id="caixaClienteNome">---</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small">Código da Reserva:</span>
                            <span class="text-danger font-monospace fw-bold" id="caixaCodigoReserva">---</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small">Procedimento(s):</span>
                            <span class="text-white small" id="caixaProcedimentos">---</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top border-secondary border-opacity-25">
                            <span class="text-gold fw-bold">VALOR A COBRAR:</span>
                            <span class="fs-4 fw-bold text-gold" id="caixaValorTotalDisplay">R$ 0,00</span>
                        </div>
                    </div>

                    <!-- Forma de Recebimento no Balcão -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-bold text-uppercase">Forma de Pagamento no Caixa:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="metodo_caixa" id="metodoDinheiro" value="dinheiro" checked onchange="alternarMetodoCaixa('dinheiro')">
                            <label class="btn btn-outline-danger btn-sm py-2" for="metodoDinheiro">
                                💵 Dinheiro
                            </label>

                            <input type="radio" class="btn-check" name="metodo_caixa" id="metodoPixBalcao" value="pix_balcao" onchange="alternarMetodoCaixa('pix')">
                            <label class="btn btn-outline-danger btn-sm py-2" for="metodoPixBalcao">
                                📱 PIX Balcão
                            </label>

                            <input type="radio" class="btn-check" name="metodo_caixa" id="metodoCartao" value="cartao" onchange="alternarMetodoCaixa('cartao')">
                            <label class="btn btn-outline-danger btn-sm py-2" for="metodoCartao">
                                💳 Cartão
                            </label>
                        </div>
                    </div>

                    <!-- Bloco Específico para Pagamento em Dinheiro (Calculadora de Troco) -->
                    <div id="blocoDinheiroCaixa">
                        <div class="mb-3">
                            <label for="caixaValorRecebido" class="form-label fw-bold text-white">
                                <i class="bi bi-wallet2 text-gold me-1"></i> Quanto o Cliente Entregou em Dinheiro?
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-dark border-secondary text-gold fw-bold">R$</span>
                                <input type="number" step="0.50" min="0" class="form-control fs-3 fw-bold text-white bg-dark border-secondary" id="caixaValorRecebido" name="valor_recebido" placeholder="0,00" oninput="calcularTrocoCaixa()">
                            </div>
                        </div>

                        <!-- Botões de Atalho Rápido de Notas Comuns -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-white" onclick="definirValorRecebidoCaixa('exato')">
                                Valor Exato
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-white" onclick="definirValorRecebidoCaixa(50)">
                                R$ 50
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-white" onclick="definirValorRecebidoCaixa(100)">
                                R$ 100
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-white" onclick="definirValorRecebidoCaixa(200)">
                                R$ 200
                            </button>
                        </div>

                        <!-- Painel de Exibição do Troco Calculado -->
                        <div id="painelTrocoDisplay" class="p-3 rounded text-center mb-2" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.4);">
                            <small class="text-secondary d-block text-uppercase fw-bold" style="letter-spacing: 1px;">Troco a Devolver</small>
                            <h2 class="text-success fw-bold mb-0" id="caixaTrocoValor">R$ 0,00</h2>
                            <small class="text-muted d-block mt-1" id="caixaStatusTroco">Aguardando digitação da quantia</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold py-2 px-4 fw-bold" id="btnConfirmarCaixa">
                        <i class="bi bi-check2-circle me-1"></i> Confirmar Recebimento & Emitir Comprovante
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let valorTotalAtualCaixa = 0.0;

function abrirModalCaixa(id, clienteNome, valorTotal, procedimentos, codigo) {
    valorTotalAtualCaixa = parseFloat(valorTotal);
    document.getElementById('caixaAgendamentoId').value = id;
    document.getElementById('caixaClienteNome').innerText = clienteNome;
    document.getElementById('caixaCodigoReserva').innerText = codigo;
    document.getElementById('caixaProcedimentos').innerText = procedimentos;
    document.getElementById('caixaValorTotalDisplay').innerText = 'R$ ' + valorTotalAtualCaixa.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    // Resetar para dinheiro e valor exato inicialmente
    document.getElementById('metodoDinheiro').checked = true;
    alternarMetodoCaixa('dinheiro');
    document.getElementById('caixaValorRecebido').value = valorTotalAtualCaixa.toFixed(2);
    calcularTrocoCaixa();

    new bootstrap.Modal(document.getElementById('modalCaixaReceber')).show();
}

function alternarMetodoCaixa(metodo) {
    const blocoDinheiro = document.getElementById('blocoDinheiroCaixa');
    const inputRecebido = document.getElementById('caixaValorRecebido');
    const btnConfirmar = document.getElementById('btnConfirmarCaixa');
    
    if (metodo === 'dinheiro') {
        blocoDinheiro.style.display = 'block';
        inputRecebido.required = true;
        calcularTrocoCaixa();
    } else {
        blocoDinheiro.style.display = 'none';
        inputRecebido.required = false;
        btnConfirmar.disabled = false;
        btnConfirmar.classList.remove('btn-secondary');
        btnConfirmar.classList.add('btn-gold');
    }
}

function definirValorRecebidoCaixa(valor) {
    if (valor === 'exato') {
        document.getElementById('caixaValorRecebido').value = valorTotalAtualCaixa.toFixed(2);
    } else {
        document.getElementById('caixaValorRecebido').value = parseFloat(valor).toFixed(2);
    }
    calcularTrocoCaixa();
}

function calcularTrocoCaixa() {
    const recebido = parseFloat(document.getElementById('caixaValorRecebido').value) || 0.0;
    const troco = recebido - valorTotalAtualCaixa;
    const painel = document.getElementById('painelTrocoDisplay');
    const trocoEl = document.getElementById('caixaTrocoValor');
    const statusEl = document.getElementById('caixaStatusTroco');
    const btnConfirmar = document.getElementById('btnConfirmarCaixa');

    if (recebido < valorTotalAtualCaixa) {
        const falta = valorTotalAtualCaixa - recebido;
        painel.style.background = 'rgba(239, 68, 68, 0.15)';
        painel.style.borderColor = 'rgba(239, 68, 68, 0.6)';
        trocoEl.className = 'text-danger fw-bold mb-0';
        trocoEl.innerText = '- R$ ' + falta.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        statusEl.innerText = 'Valor insuficiente (faltam R$ ' + falta.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ')';
        statusEl.className = 'text-danger d-block mt-1';
        btnConfirmar.disabled = true;
        btnConfirmar.classList.replace('btn-gold', 'btn-secondary');
    } else {
        painel.style.background = 'rgba(34, 197, 94, 0.15)';
        painel.style.borderColor = 'rgba(34, 197, 94, 0.6)';
        trocoEl.className = 'text-success fw-bold mb-0';
        trocoEl.innerText = 'R$ ' + troco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        statusEl.innerText = troco > 0 ? 'Entregar troco ao cliente' : 'Pagamento no valor exato, sem troco';
        statusEl.className = 'text-success d-block mt-1';
        btnConfirmar.disabled = false;
        btnConfirmar.classList.replace('btn-secondary', 'btn-gold');
    }
}
</script>

