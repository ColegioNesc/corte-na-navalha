/**
 * Gerenciador de UI: Toasts e Modais Bootstrap 5
 * Atende às regras do sistema:
 * - Mensagens de erro/sucesso/info no estilo Toast
 * - Mensagens que requeiram OK ou confirmação sempre em Modais (sem alerts nativos)
 */

const UI = {
    /**
     * Exibe um Toast do Bootstrap 5
     * @param {string} message Mensagem
     * @param {string} type 'success' | 'danger' | 'warning' | 'info'
     * @param {string} title Título opcional
     * @param {number} duration Duração em ms (default 4000)
     */
    showToast(message, type = 'info', title = '', duration = 4500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const id = 'toast_' + Date.now();
        const iconMap = {
            success: 'bi-check-circle-fill text-success',
            danger: 'bi-exclamation-triangle-fill text-danger',
            warning: 'bi-exclamation-circle-fill text-warning',
            info: 'bi-info-circle-fill text-info'
        };

        const defaultTitles = {
            success: 'Sucesso',
            danger: 'Atenção / Erro',
            warning: 'Aviso',
            info: 'Notificação'
        };

        const toastTitle = title || defaultTitles[type] || 'Aviso';
        const iconClass = iconMap[type] || iconMap.info;

        const toastHtml = `
            <div id="${id}" class="toast align-items-center shadow-lg border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-white">
                    <i class="bi ${iconClass} me-2 fs-5"></i>
                    <strong class="me-auto text-dark">${toastTitle}</strong>
                    <small class="text-muted">agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
                </div>
                <div class="toast-body bg-light text-dark">
                    ${message}
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(id);
        const bsToast = new bootstrap.Toast(toastElement, { delay: duration });

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        bsToast.show();
    },

    /**
     * Exibe um Modal customizado em substituição ao window.alert / window.confirm
     * @param {Object} options { title, body, confirmText, cancelText, confirmBtnClass, onConfirm, onCancel, showCancel }
     */
    showModal(options = {}) {
        const {
            title = 'Informação',
            body = '',
            confirmText = 'OK',
            cancelText = 'Cancelar',
            confirmBtnClass = 'btn-primary',
            onConfirm = null,
            onCancel = null,
            showCancel = false
        } = options;

        const modalEl = document.getElementById('appGeneralModal');
        if (!modalEl) {
            console.warn('Elemento #appGeneralModal não encontrado');
            return;
        }

        const modalTitle = document.getElementById('appGeneralModalTitle');
        const modalBody = document.getElementById('appGeneralModalBody');
        const confirmBtn = document.getElementById('appGeneralModalConfirmBtn');
        const cancelBtn = document.getElementById('appGeneralModalCancelBtn');

        modalTitle.textContent = title;
        modalBody.innerHTML = body;
        confirmBtn.textContent = confirmText;
        confirmBtn.className = `btn ${confirmBtnClass}`;

        if (showCancel) {
            cancelBtn.classList.remove('d-none');
            cancelBtn.textContent = cancelText;
        } else {
            cancelBtn.classList.add('d-none');
        }

        const bsModal = new bootstrap.Modal(modalEl);

        // Clona botão para remover listeners anteriores
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', () => {
            bsModal.hide();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        newCancelBtn.addEventListener('click', () => {
            bsModal.hide();
            if (typeof onCancel === 'function') {
                onCancel();
            }
        });

        bsModal.show();
    },

    /**
     * Modal para confirmação de ações críticas (Substituto do confirm)
     */
    confirmAction(title, message, onConfirm, onCancel = null) {
        this.showModal({
            title: title,
            body: `<p class="mb-0 fs-6">${message}</p>`,
            confirmText: 'Sim, confirmar',
            cancelText: 'Voltar',
            confirmBtnClass: 'btn-danger',
            showCancel: true,
            onConfirm: onConfirm,
            onCancel: onCancel
        });
    },

    /**
     * Modal de aviso de GPS desativado (RN-06)
     */
    showGpsErrorModal(message) {
        this.showModal({
            title: 'GPS / Localização Necessária',
            body: `
                <div class="text-center py-2">
                    <i class="bi bi-geo-alt-fill text-danger" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-secondary">
                        ${message || 'Não foi possível obter a sua localização GPS.'}
                    </p>
                    <div class="alert alert-warning text-start mt-3 mb-0 small">
                        <strong>Como ativar:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Verifique se a Localização/GPS está ativada no seu aparelho.</li>
                            <li>Permita o acesso à localização nas permissões do navegador.</li>
                        </ul>
                    </div>
                </div>
            `,
            confirmText: 'Entendido',
            confirmBtnClass: 'btn-primary'
        });
    }
};
