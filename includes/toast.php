<?php
/**
 * Renderizador de Mensagens Toast Bootstrap
 */
$toast = getMensagensToast();
?>
<div id="toastPlacement" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <?php if ($toast): ?>
        <?php
            $tipo = $toast['tipo'] ?? 'info';
            $bgMap = [
                'success' => 'border-success text-success',
                'danger'  => 'border-danger text-danger',
                'warning' => 'border-warning text-warning',
                'info'    => 'border-info text-info'
            ];
            $iconMap = [
                'success' => 'bi-check-circle-fill text-success',
                'danger'  => 'bi-exclamation-triangle-fill text-danger',
                'warning' => 'bi-exclamation-circle-fill text-warning',
                'info'    => 'bi-info-circle-fill text-info'
            ];
            $bordaClass = $bgMap[$tipo] ?? 'border-warning text-warning';
            $iconeClass = $iconMap[$tipo] ?? 'bi-bell-fill text-warning';
        ?>
        <div class="toast barber-toast <?= $bordaClass ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="toast-header bg-transparent text-white border-bottom border-secondary">
                <i class="bi <?= $iconeClass ?> me-2"></i>
                <strong class="me-auto"><?= htmlspecialchars($toast['titulo']) ?></strong>
                <small class="text-muted">Agora</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body text-light">
                <?= htmlspecialchars($toast['mensagem']) ?>
            </div>
        </div>
    <?php endif; ?>
</div>
