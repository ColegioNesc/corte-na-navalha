/**
 * Corte na Navalha - Script Principal
 * Tratamento de Modais, Toasts, Máscaras e Regras de Interface
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inicializar Toasts do Bootstrap
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    });

    // 2. Máscaras de Entrada de Dados (CPF, Telefone)
    const cpfInputs = document.querySelectorAll('input[name="cpf"], .mask-cpf');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) {
                v = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
            }
            e.target.value = v;
        });
    });

    const phoneInputs = document.querySelectorAll('input[name="telefone"], .mask-phone');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (v.length > 6) {
                v = v.replace(/^(\d{2})(\d{4})(\d{1,4})$/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{1,5})$/, '($1) $2');
            }
            e.target.value = v;
        });
    });
});

/**
 * Exibe Toast Dinâmico na Tela
 * @param {string} tipo - success, danger, warning, info
 * @param {string} titulo 
 * @param {string} mensagem 
 */
function showToast(tipo, titulo, mensagem) {
    const container = document.getElementById('toastPlacement') || createToastContainer();
    const bgMap = {
        success: 'border-success text-success',
        danger: 'border-danger text-danger',
        warning: 'border-warning text-warning',
        info: 'border-info text-info'
    };
    const iconMap = {
        success: 'bi-check-circle-fill text-success',
        danger: 'bi-exclamation-triangle-fill text-danger',
        warning: 'bi-exclamation-circle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };

    const toastHtml = `
        <div class="toast barber-toast ${bgMap[tipo] || 'border-warning text-warning'}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-transparent text-white border-bottom border-secondary">
                <i class="bi ${iconMap[tipo] || 'bi-info-circle'} me-2"></i>
                <strong class="me-auto">${titulo}</strong>
                <small class="text-muted">Agora</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body text-white">
                ${mensagem}
            </div>
        </div>
    `;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = toastHtml;
    const toastEl = wrapper.firstElementChild;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4500 });
    toast.show();
}

function createToastContainer() {
    const c = document.createElement('div');
    c.id = 'toastPlacement';
    c.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(c);
    return c;
}

/**
 * Modal Dinâmico de Confirmação (Substitui confirm() nativo do navegador)
 */
function abrirModalConfirmacao(titulo, mensagem, callbackConfirmacao, textoBotao = 'Confirmar', corBotao = 'btn-gold') {
    let modalEl = document.getElementById('modalConfirmacaoGlobal');
    if (!modalEl) {
        const modalHtml = `
            <div class="modal fade" id="modalConfirmacaoGlobal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content barber-card">
                        <div class="modal-header">
                            <h5 class="modal-title brand-font text-gold" id="modalConfirmacaoTitulo">Atenção</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body text-light" id="modalConfirmacaoCorpo">
                            Deseja continuar?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-gold" id="btnModalConfirmarAcao">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalEl = document.getElementById('modalConfirmacaoGlobal');
    }

    document.getElementById('modalConfirmacaoTitulo').innerText = titulo;
    document.getElementById('modalConfirmacaoCorpo').innerHTML = mensagem;
    const btnConfirmar = document.getElementById('btnModalConfirmarAcao');
    btnConfirmar.className = `btn ${corBotao}`;
    btnConfirmar.innerText = textoBotao;

    const bsModal = new bootstrap.Modal(modalEl);

    // Substituir handler do botão para não duplicar eventos
    const novoBtn = btnConfirmar.cloneNode(true);
    btnConfirmar.parentNode.replaceChild(novoBtn, btnConfirmar);

    novoBtn.addEventListener('click', function () {
        bsModal.hide();
        if (typeof callbackConfirmacao === 'function') {
            callbackConfirmacao();
        }
    });

    bsModal.show();
}

/**
 * Copiar texto para a área de transferência
 */
function copiarParaClipboard(texto, mensagemSucesso = 'Chave PIX copiada com sucesso!') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(() => {
            showToast('success', 'Copiado!', mensagemSucesso);
        }).catch(() => fallbackCopiar(texto, mensagemSucesso));
    } else {
        fallbackCopiar(texto, mensagemSucesso);
    }
}

function fallbackCopiar(texto, mensagemSucesso) {
    const inputTemp = document.createElement('input');
    inputTemp.value = texto;
    document.body.appendChild(inputTemp);
    inputTemp.select();
    try {
        document.execCommand('copy');
        showToast('success', 'Copiado!', mensagemSucesso);
    } catch (err) {
        showToast('warning', 'Atenção', 'Não foi possível copiar automaticamente. Selecione e copie manualmente.');
    }
    document.body.removeChild(inputTemp);
}
