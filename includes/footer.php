</main>

<!-- Container Global para Toasts (Regra: Mensagens claras no estilo Toast) -->
<div id="toastContainer" class="toast-container-custom" aria-live="polite" aria-atomic="true"></div>

<!-- Modal Genérico do Sistema (Regra: Mensagens com OK e confirmações SEMPRE em modal) -->
<div class="modal fade" id="appGeneralModal" tabindex="-1" aria-labelledby="appGeneralModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="appGeneralModalTitle">Informação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-4" id="appGeneralModalBody">
                <!-- Conteúdo dinâmico -->
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 d-none" id="appGeneralModalCancelBtn">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="appGeneralModalConfirmBtn">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay Urgente de Alarme Acionado (RF07, RF08, RF09) -->
<div id="alarmOverlay" class="alarm-overlay-screen d-none">
    <div class="alarm-icon-bounce mb-3">
        <i class="bi bi-bell-fill"></i>
    </div>
    <h1 class="display-4 fw-black text-uppercase mb-2" style="font-weight: 900;">ACORDE! VOCÊ CHEGOU!</h1>
    <p class="fs-4 mb-2">Você entrou no raio de distância do seu destino:</p>
    <div class="p-3 bg-white text-dark rounded-4 shadow mb-4 text-center" style="max-width: 480px; width: 100%;">
        <h4 class="fw-bold mb-1" id="alarmDestinoNome">Destino</h4>
        <p class="text-danger fw-bold mb-0">Distância aproximada: <span id="alarmDistanciaAtual">0m</span></p>
    </div>
    
    <!-- Botão explícito para interromper som/vibração e encerrar monitoramento (RF08, RF09) -->
    <button id="btnStopAlarm" type="button" class="btn-stop-alarm">
        <i class="bi bi-check-circle-fill me-2"></i> DESLIGAR ALARME / CHEGUEI
    </button>
    <p class="mt-4 text-white-50 small">O alarme continuará tocando e vibrando até que você clique no botão acima.</p>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Scripts da Aplicação -->
<script src="assets/js/ui.js"></script>
<script src="assets/js/audio.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>
