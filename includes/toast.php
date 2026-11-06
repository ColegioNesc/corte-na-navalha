<?php
/**
 * Renderizador de Mensagens Toast Bootstrap
 * Suporte aprimorado para mensagens de ERRO em vermelho vibrante de alta visibilidade
 */
$toast = getMensagensToast();
?>
<div id="toastPlacement" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1095;">
    <?php if ($toast): ?>
        <?php
            $tipo = $toast['tipo'] ?? 'info';
            $isDanger = ($tipo === 'danger');
            $isSuccess = ($tipo === 'success');
            
            $toastClass = $isDanger ? 'barber-toast-danger' : ($isSuccess ? 'barber-toast-success' : 'barber-toast border-warning');
            
            $headerBg = $isDanger ? 'bg-danger text-white' : ($isSuccess ? 'bg-success text-white' : 'bg-dark text-warning border-bottom border-secondary');
            
            $iconMap = [
                'success' => 'bi-check-circle-fill text-white',
                'danger'  => 'bi-exclamation-octagon-fill text-white',
                'warning' => 'bi-exclamation-circle-fill text-warning',
                'info'    => 'bi-info-circle-fill text-info'
            ];
            $iconeClass = $iconMap[$tipo] ?? 'bi-bell-fill text-warning';
        ?>
        <div class="toast barber-toast <?= $toastClass ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="6000">
            <div class="toast-header <?= $headerBg ?> py-2 px-3">
                <i class="bi <?= $iconeClass ?> me-2 fs-5"></i>
                <strong class="me-auto fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                    <?php if ($isDanger): ?>
                        <span class="badge bg-black text-danger me-1">ERRO</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($toast['titulo']) ?>
                </strong>
                <small class="<?= $isDanger || $isSuccess ? 'text-white-50' : 'text-muted' ?>">Agora</small>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body py-3 px-3">
                <?= htmlspecialchars($toast['mensagem']) ?>
            </div>
        </div>
    <?php endif; ?>
</div>
