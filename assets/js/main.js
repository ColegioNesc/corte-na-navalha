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
 * Suporte aprimorado para Erros em Vermelho de Alta Visibilidade
 * @param {string} tipo - success, danger, warning, info
 * @param {string} titulo 
 * @param {string} mensagem 
 */
function showToast(tipo, titulo, mensagem) {
    const container = document.getElementById('toastPlacement') || createToastContainer();
    const isDanger = (tipo === 'danger');
    const isSuccess = (tipo === 'success');

    const toastClass = isDanger ? 'barber-toast-danger' : (isSuccess ? 'barber-toast-success' : 'barber-toast border-warning');
    const headerBg = isDanger ? 'bg-danger text-white' : (isSuccess ? 'bg-success text-white' : 'bg-dark text-warning border-bottom border-secondary');
    
    const iconMap = {
        success: 'bi-check-circle-fill text-white',
        danger: 'bi-exclamation-octagon-fill text-white',
        warning: 'bi-exclamation-circle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };

    const badgeErro = isDanger ? '<span class="badge bg-black text-danger me-1">ERRO</span> ' : '';

    const toastHtml = `
        <div class="toast barber-toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${headerBg} py-2 px-3">
                <i class="bi ${iconMap[tipo] || 'bi-info-circle'} me-2 fs-5"></i>
                <strong class="me-auto fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                    ${badgeErro}${titulo}
                </strong>
                <small class="${isDanger || isSuccess ? 'text-white-50' : 'text-muted'}">Agora</small>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body py-3 px-3">
                ${mensagem}
            </div>
        </div>
    `;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = toastHtml;
    const toastEl = wrapper.firstElementChild;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 6000 });
    toast.show();
}

/**
 * Busca Global do Sistema - Filtra procedimentos e barbeiros na página ou redireciona
 */
function executarBuscaGlobal(inputEl) {
    const input = inputEl || document.getElementById('navbarSearch') || document.getElementById('navbarSearchMobile');
    if (!input) return;
    const termo = input.value.trim().toLowerCase();
    if (!termo) return;

    // Se estiver na página inicial, rola até serviços e filtra
    const containerServicos = document.getElementById('servicos');
    if (containerServicos) {
        containerServicos.scrollIntoView({ behavior: 'smooth' });
        const cards = document.querySelectorAll('.card-servico-home');
        let encontrados = 0;
        cards.forEach(card => {
            const texto = card.innerText.toLowerCase();
            if (texto.includes(termo)) {
                card.closest('.col-lg-4, .col-md-6').style.display = '';
                card.classList.add('border-danger');
                encontrados++;
            } else {
                card.closest('.col-lg-4, .col-md-6').style.display = 'none';
            }
        });
        showToast('info', 'Busca Realizada', `Exibindo ${encontrados} procedimento(s) encontrado(s) para "${termo}".`);
    } else {
        // Redireciona para o início na seção de serviços de forma dinâmica
        const baseUrl = window.BASE_URL || '';
        window.location.href = baseUrl + '/index.php#servicos';
    }
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
