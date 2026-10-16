<?php
/**
 * Página Principal / Monitoramento em Tempo Real
 * Alarme a Distância
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = "Alarme a Distância - Monitor de Trajeto";

$user = null;
$raioPadrao = 300;
$tipoAlarmePadrao = 'ambos';
$somAlarmePadrao = 'sirene';
$favoritos = [];

if (isLoggedIn() && $pdo) {
    $user = getCurrentUser($pdo);
    if ($user) {
        $raioPadrao = (int)$user['raio_padrao'];
        $tipoAlarmePadrao = $user['tipo_alarme_padrao'];
        $somAlarmePadrao = $user['som_alarme'];
    }

    // Carrega destinos favoritos do usuário
    try {
        $stmtFav = $pdo->prepare("SELECT id, titulo, endereco, lat, lng, raio_preferido FROM destinos_favoritos WHERE usuario_id = ? LIMIT 6");
        $stmtFav->execute([$_SESSION['user_id']]);
        $favoritos = $stmtFav->fetchAll();
    } catch (Exception $e) {
        $favoritos = [];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header do Monitor com Status -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Monitor de Trajeto</h4>
        <small class="text-muted">Acompanhe seu deslocamento em tempo real</small>
    </div>
    <div id="statusBadge" class="status-badge-tracker inativo">
        <span class="badge bg-secondary me-1">Parado</span> Monitoramento inativo
    </div>
</div>

<!-- Barra de Busca de Destino (RN-01, RF02) -->
<div class="card card-app mb-3 position-relative">
    <div class="card-body p-3">
        <label for="destinationInput" class="form-label fw-bold text-secondary mb-1">
            <i class="bi bi-search me-1 text-primary"></i> 1. Onde você vai descer? (Destino)
        </label>
        <div class="input-group">
            <input type="text" id="destinationInput" class="form-control form-control-lg fs-6" 
                   placeholder="Digite um endereço, ponto de referência ou clique no mapa..." autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="btnCenterUser" title="Centralizar na Minha Localização">
                <i class="bi bi-crosshair"></i>
            </button>
        </div>
        <!-- Caixa de Autocomplete -->
        <div id="searchSuggestions" class="autocomplete-suggestions d-none"></div>

        <!-- Atalhos de Favoritos (se houver) -->
        <?php if (!empty($favoritos)): ?>
            <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                <small class="text-muted me-1"><i class="bi bi-star-fill text-warning"></i> Favoritos:</small>
                <?php foreach ($favoritos as $fav): ?>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 small"
                            onclick="App.setDestination(<?= $fav['lat'] ?>, <?= $fav['lng'] ?>, '<?= addslashes(escape($fav['titulo'] . ' - ' . $fav['endereco'])) ?>');">
                        <?= escape($fav['titulo']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Painel de Distância em Tempo Real (RF05, RF06) -->
<div class="card-app distance-display-box mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div class="text-uppercase text-info small fw-bold tracking-wide">Distância até o Destino</div>
            <div class="d-flex align-items-baseline">
                <span id="currentDistanceValue" class="distance-number">--</span>
                <span id="currentDistanceUnit" class="distance-unit">m</span>
            </div>
        </div>
        <div class="text-end">
            <div class="text-white-50 small">Raio configurado</div>
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill" id="radiusDisplay"><?= $raioPadrao ?>m</span>
        </div>
    </div>
    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small text-light-50" id="distanceSubtext">
        Selecione um destino no mapa ou pela busca acima.
    </div>
</div>

<!-- Mapa Interativo Leaflet (RF01, RF02, RF03) -->
<div class="card card-app mb-3 p-2">
    <div id="map"></div>
    <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted small">
        <span><i class="bi bi-info-circle me-1"></i> Toque em qualquer local do mapa para marcar seu ponto</span>
        <span class="text-primary"><i class="bi bi-circle-fill me-1" style="color: #0d6efd;"></i> Você &nbsp; <i class="bi bi-flag-fill me-1 text-danger"></i> Destino</span>
    </div>
</div>

<!-- Painel de Configurações de Alerta (RN-03, RF03, RF04) -->
<div class="card card-app mb-3" id="configPanelInputs">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-sliders me-1 text-primary"></i> Configuração do Alarme</span>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" id="btnTestAlarm">
            <i class="bi bi-volume-up me-1"></i> Testar Alerta
        </button>
    </div>
    <div class="card-body">
        <!-- Raio de Cobertura -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="radiusInput" class="form-label fw-bold mb-0">Raio de Antecedência para o Alerta:</label>
                <span class="fw-bold text-danger fs-6" id="radiusLabel"><?= $raioPadrao ?> metros</span>
            </div>
            <input type="range" class="form-range" min="100" max="2500" step="50" id="radiusInput" value="<?= $raioPadrao ?>"
                   oninput="document.getElementById('radiusLabel').textContent = this.value + ' metros';">
            
            <div class="d-flex gap-2 mt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-radius" data-radius="200" onclick="document.getElementById('radiusLabel').textContent = '200 metros';">200m</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-radius" data-radius="300" onclick="document.getElementById('radiusLabel').textContent = '300 metros';">300m</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-radius" data-radius="500" onclick="document.getElementById('radiusLabel').textContent = '500 metros';">500m</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-radius" data-radius="1000" onclick="document.getElementById('radiusLabel').textContent = '1000 metros';">1 km</button>
            </div>
            <div class="form-text small">O alarme tocará e vibrará assim que você estiver dentro dessa distância.</div>
        </div>

        <div class="row g-2">
            <!-- Tipo de Alerta -->
            <div class="col-sm-6">
                <label for="alarmTypeSelect" class="form-label fw-bold small">Tipo de Notificação:</label>
                <select id="alarmTypeSelect" class="form-select form-select-sm">
                    <option value="ambos" <?= $tipoAlarmePadrao === 'ambos' ? 'selected' : '' ?>>🔔 Som + Vibração (Recomendado)</option>
                    <option value="som" <?= $tipoAlarmePadrao === 'som' ? 'selected' : '' ?>>🔊 Apenas Som</option>
                    <option value="vibracao" <?= $tipoAlarmePadrao === 'vibracao' ? 'selected' : '' ?>>📳 Apenas Vibração</option>
                </select>
            </div>
            <!-- Som do Alarme -->
            <div class="col-sm-6">
                <label for="soundSelect" class="form-label fw-bold small">Toque Sonoro:</label>
                <select id="soundSelect" class="form-select form-select-sm">
                    <option value="sirene" <?= $somAlarmePadrao === 'sirene' ? 'selected' : '' ?>>🚨 Sirene de Alerta</option>
                    <option value="beep" <?= $somAlarmePadrao === 'beep' ? 'selected' : '' ?>>⏱️ Bip Contínuo Rápido</option>
                    <option value="toque" <?= $somAlarmePadrao === 'toque' ? 'selected' : '' ?>>🎵 Melodia Despertador</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Controles de Ação do Trajeto (RN-05) -->
<div class="d-grid gap-2 mb-4">
    <!-- Iniciar Trajeto -->
    <button id="btnStartTrip" type="button" class="btn btn-primary btn-lg btn-action-mobile shadow">
        <i class="bi bi-play-circle-fill me-2 fs-5"></i> INICIAR MONITORAMENTO
    </button>
    
    <div class="row g-2">
        <!-- Pausar Trajeto -->
        <div class="col-6">
            <button id="btnPauseTrip" type="button" class="btn btn-warning btn-lg btn-action-mobile w-100 d-none text-dark shadow-sm">
                <i class="bi bi-pause-circle-fill me-1"></i> Pausar
            </button>
        </div>
        <!-- Retomar Trajeto -->
        <div class="col-6">
            <button id="btnResumeTrip" type="button" class="btn btn-success btn-lg btn-action-mobile w-100 d-none shadow-sm">
                <i class="bi bi-play-circle-fill me-1"></i> Retomar
            </button>
        </div>
        <!-- Cancelar Trajeto -->
        <div class="col-12">
            <button id="btnCancelTrip" type="button" class="btn btn-outline-danger btn-lg btn-action-mobile w-100 d-none">
                <i class="bi bi-x-circle me-1"></i> Cancelar Trajeto
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
