<?php
/**
 * Página de Registro de Usuário
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Criar Conta - Alarme a Distância";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-7 col-lg-6">
        <div class="card card-app shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-icon mx-auto mb-2" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="bi bi-person-plus-fill"></i>
                    </span>
                    <h4 class="fw-bold text-dark">Cadastre-se Gratuitamente</h4>
                    <p class="text-muted small">Crie sua conta para personalizar suas preferências de alarme</p>
                </div>

                <form id="registerForm">
                    <div class="mb-3">
                        <label for="nome" class="form-label fw-semibold">Nome Completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nome" name="nome" required placeholder="Ex: Maria Santos">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="senha" class="form-label fw-semibold">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" required minlength="6" placeholder="Mínimo 6 dígitos">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="senha_confirm" class="form-label fw-semibold">Confirmar Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="senha_confirm" name="senha_confirm" required minlength="6" placeholder="Repita a senha">
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mb-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-gear-fill me-1"></i> Preferências Padrão de Alerta</h6>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label for="raio_padrao" class="form-label small fw-semibold">Raio de Antecedência:</label>
                                <select class="form-select form-select-sm" id="raio_padrao">
                                    <option value="200">200 metros</option>
                                    <option value="300" selected>300 metros (Padrão)</option>
                                    <option value="500">500 metros</option>
                                    <option value="1000">1 km</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="tipo_alarme_padrao" class="form-label small fw-semibold">Tipo de Alarme:</label>
                                <select class="form-select form-select-sm" id="tipo_alarme_padrao">
                                    <option value="ambos" selected>🔔 Som + Vibração</option>
                                    <option value="som">🔊 Apenas Som</option>
                                    <option value="vibracao">📳 Apenas Vibração</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill mb-3" id="btnRegisterSubmit">
                        <i class="bi bi-check-circle-fill me-1"></i> Finalizar Cadastro
                    </button>
                </form>

                <div class="text-center border-top pt-3">
                    <small class="text-muted">Já tem uma conta? <a href="login.php" class="text-primary fw-bold text-decoration-none">Faça login aqui</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('btnRegisterSubmit');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const nome = document.getElementById('nome').value.trim();
        const email = document.getElementById('email').value.trim();
        const senha = document.getElementById('senha').value;
        const senhaConfirm = document.getElementById('senha_confirm').value;
        const raio_padrao = document.getElementById('raio_padrao').value;
        const tipo_alarme_padrao = document.getElementById('tipo_alarme_padrao').value;

        if (senha !== senhaConfirm) {
            UI.showToast('As senhas digitadas não coincidem.', 'danger');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Criando conta...';

        try {
            const resp = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'register',
                    nome,
                    email,
                    senha,
                    raio_padrao,
                    tipo_alarme_padrao
                })
            });

            const data = await resp.json();

            if (data.success) {
                UI.showToast('Cadastro realizado com sucesso! Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                UI.showToast(data.message || 'Falha ao cadastrar.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Finalizar Cadastro';
            }
        } catch (err) {
            UI.showToast('Erro de comunicação com o servidor.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Finalizar Cadastro';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
