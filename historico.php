<?php
/**
 * Página de Histórico de Viagens e Trajetos
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = "Histórico de Trajetos - Alarme a Distância";
require_once __DIR__ . '/includes/header.php';

$viagens = [];
if (isLoggedIn() && $pdo) {
    $stmt = $pdo->prepare("
        SELECT id, destino_nome, destino_lat, destino_lng, raio_metros, tipo_alarme, status, acionado, criado_em, concluido_em
        FROM viagens
        WHERE usuario_id = ?
        ORDER BY criado_em DESC
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $viagens = $stmt->fetchAll();
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Histórico de Trajetos</h4>
        <small class="text-muted">Veja suas viagens monitoradas anteriores</small>
    </div>
    <a href="index.php" class="btn btn-sm btn-primary rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i> Novo Trajeto
    </a>
</div>

<?php if (!isLoggedIn()): ?>
    <div class="card card-app text-center p-4">
        <i class="bi bi-clock-history text-secondary" style="font-size: 3rem;"></i>
        <h5 class="fw-bold mt-3">Histórico disponível com login</h5>
        <p class="text-muted">Faça login na sua conta para que todas as suas viagens e pontos de descida fiquem salvos.</p>
        <div>
            <a href="login.php" class="btn btn-primary rounded-pill px-4 me-2">Entrar</a>
            <a href="registro.php" class="btn btn-outline-primary rounded-pill px-4">Cadastrar</a>
        </div>
    </div>
<?php elseif (empty($viagens)): ?>
    <div class="card card-app text-center p-4">
        <i class="bi bi-geo-alt text-muted" style="font-size: 3rem;"></i>
        <h5 class="fw-bold mt-3 text-secondary">Nenhum trajeto registrado ainda</h5>
        <p class="text-muted">Inicie seu primeiro monitoramento na página inicial e ele aparecerá aqui.</p>
        <div>
            <a href="index.php" class="btn btn-primary rounded-pill px-4">Ir para o Monitor</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($viagens as $viagem): ?>
            <div class="col-12 col-md-6">
                <div class="card card-app h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge <?= $viagem['status'] === 'concluido' ? 'bg-success' : ($viagem['status'] === 'cancelado' ? 'bg-secondary' : 'bg-warning text-dark') ?> rounded-pill">
                            <?= ucfirst($viagem['status']) ?>
                        </span>
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('d/m/Y H:i', strtotime($viagem['criado_em'])) ?>
                        </small>
                    </div>

                    <h6 class="fw-bold text-dark text-truncate mb-2" title="<?= escape($viagem['destino_nome']) ?>">
                        <i class="bi bi-pin-map-fill text-danger me-1"></i> <?= escape($viagem['destino_nome']) ?>
                    </h6>

                    <div class="small text-secondary mb-3">
                        <div><strong>Raio do alerta:</strong> <?= $viagem['raio_metros'] ?> metros</div>
                        <div><strong>Tipo de alerta:</strong> <?= ucfirst($viagem['tipo_alarme']) ?></div>
                        <div>
                            <strong>Alarme disparado:</strong>
                            <?= $viagem['acionado'] ? '<span class="text-danger fw-bold">Sim (Alerta emitido)</span>' : '<span class="text-muted">Não</span>' ?>
                        </div>
                    </div>

                    <div class="mt-auto pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill w-100"
                                onclick="repetirTrajeto(<?= $viagem['destino_lat'] ?>, <?= $viagem['destino_lng'] ?>, '<?= addslashes(escape($viagem['destino_nome'])) ?>', <?= $viagem['raio_metros'] ?>)">
                            <i class="bi bi-arrow-repeat me-1"></i> Repetir Este Trajeto
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function repetirTrajeto(lat, lng, nome, raio) {
    sessionStorage.setItem('prefill_lat', lat);
    sessionStorage.setItem('prefill_lng', lng);
    sessionStorage.setItem('prefill_nome', nome);
    sessionStorage.setItem('prefill_raio', raio);
    window.location.href = 'index.php';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
