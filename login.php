<?php
/**
 * Página de Login
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Entrar - Alarme a Distância";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6 col-lg-5">
        <div class="card card-app shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-icon mx-auto mb-2" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </span>
                    <h4 class="fw-bold text-dark">Acessar Conta</h4>
                    <p class="text-muted small">Entre para sincronizar seus destinos favoritos e histórico</p>
                </div>

                <form id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label fw-semibold">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill mb-3" id="btnLoginSubmit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </button>
                </form>

                <div class="text-center border-top pt-3">
                    <small class="text-muted">Ainda não tem conta? <a href="registro.php" class="text-primary fw-bold text-decoration-none">Cadastre-se grátis</a></small>
                    <div class="mt-2">
                        <a href="index.php" class="text-secondary small"><i class="bi bi-arrow-left me-1"></i> Usar como visitante sem login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('btnLoginSubmit');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const senha = document.getElementById('senha').value;

        if (!email || !senha) {
            UI.showToast('Por favor, informe seu e-mail e senha.', 'warning');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Entrando...';

        try {
            const resp = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', email, senha })
            });

            const data = await resp.json();

            if (data.success) {
                UI.showToast('Login realizado com sucesso! Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                UI.showToast(data.message || 'Falha no login.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> Entrar';
            }
        } catch (err) {
            UI.showToast('Erro de comunicação com o servidor.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> Entrar';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
