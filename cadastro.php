<?php
/**
 * Página de Cadastro - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (usuarioLogado()) {
    $usuario = getUsuarioLogado();
    redirecionarPorPerfil($usuario['perfil']);
}

$tipoPerfil = $_GET['perfil'] ?? 'cliente';
if (!in_array($tipoPerfil, ['cliente', 'dono'])) {
    $tipoPerfil = 'cliente';
}

$paginaTitulo = ($tipoPerfil === 'dono') ? 'Cadastro do Dono da Barbearia' : 'Criar Conta de Cliente | Corte na Navalha';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-9">
            <div class="barber-card p-4 p-md-5">
                <!-- Cabeçalho -->
                <div class="text-center mb-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-person-plus-fill fs-3"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-1">
                        <?= ($tipoPerfil === 'dono') ? 'Cadastro do Proprietário' : 'Criar Nova Conta' ?>
                    </h3>
                    <p class="text-secondary small mb-0">Preencha seus dados para começar a agendar</p>
                </div>

                <!-- Formulário de Cadastro -->
                <form action="<?= BASE_URL ?>/actions/auth_register.php" method="POST" id="formCadastro">
                    <input type="hidden" name="perfil" value="<?= htmlspecialchars($tipoPerfil) ?>">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: João da Silva" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail (Gmail Obrigatório)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="seu.nome@gmail.com" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Utilize seu endereço do Gmail</small>
                        </div>

                        <div class="col-md-6">
                            <label for="cpf" class="form-label">CPF Válido</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-card-text"></i></span>
                                <input type="text" class="form-control mask-cpf" id="cpf" name="cpf" placeholder="000.000.000-00" required maxlength="14">
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Necessário para emissão do comprovante</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">WhatsApp / Telefone</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-whatsapp"></i></span>
                            <input type="text" class="form-control mask-phone" id="telefone" name="telefone" placeholder="(11) 98765-4321" required maxlength="15">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="Mínimo 6 dígitos" required minlength="6">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirma_senha" class="form-label">Confirmar Senha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-check2-circle"></i></span>
                                <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termos" required checked>
                        <label class="form-check-label text-secondary small" for="termos">
                            Concordo com as políticas de atendimento e agendamento da barbearia.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-3 fw-bold mb-3">
                        <i class="bi bi-check-lg me-1"></i> Concluir Cadastro & Entrar
                    </button>
                </form>

                <div class="text-center pt-3 border-top border-secondary border-opacity-25">
                    <p class="text-secondary small mb-2">Já possui uma conta?</p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-gold btn-sm w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Fazer Login
                    </a>
                </div>

                <div class="text-center mt-3">
                    <?php if ($tipoPerfil === 'cliente'): ?>
                        <a href="?perfil=dono" class="text-muted small text-decoration-none">
                            É o proprietário da barbearia? Cadastre-se como Dono
                        </a>
                    <?php else: ?>
                        <a href="?perfil=cliente" class="text-muted small text-decoration-none">
                            Deseja se cadastrar como Cliente? Clique aqui
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formCadastro').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    if (!email.toLowerCase().endsWith('@gmail.com') && !email.toLowerCase().endsWith('@googlemail.com')) {
        e.preventDefault();
        abrirModalConfirmacao(
            'E-mail Inválido',
            'Por favor, utilize um endereço válido do <strong>Gmail</strong> (ex: seu.nome@gmail.com) conforme exigido no cadastro.',
            null,
            'Entendido',
            'btn-warning'
        );
        return false;
    }

    const s1 = document.getElementById('senha').value;
    const s2 = document.getElementById('confirma_senha').value;
    if (s1 !== s2) {
        e.preventDefault();
        abrirModalConfirmacao(
            'Senhas Não Conferem',
            'A confirmação de senha não confere com a senha digitada. Por favor, redigite.',
            null,
            'OK',
            'btn-warning'
        );
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
