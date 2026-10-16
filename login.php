<?php
/**
 * Página de Login - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redireciona para seu painel
if (usuarioLogado()) {
    $usuario = getUsuarioLogado();
    redirecionarPorPerfil($usuario['perfil']);
}

$perfilSelecionado = $_GET['perfil'] ?? 'cliente';
if (!in_array($perfilSelecionado, ['cliente', 'barbeiro', 'recepcao', 'dono'])) {
    $perfilSelecionado = 'cliente';
}

$perfisNomes = [
    'cliente'  => 'Área do Cliente',
    'barbeiro' => 'Portal do Barbeiro',
    'recepcao' => 'Acesso da Recepção',
    'dono'     => 'Gestão da Barbearia'
];

$paginaTitulo = 'Entrar | ' . $perfisNomes[$perfilSelecionado];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <!-- Card Principal de Login -->
            <div class="barber-card p-4 p-md-5">
                <!-- Cabeçalho -->
                <div class="text-center mb-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-1">Acesso ao Sistema</h3>
                    <p class="text-secondary small mb-0">Identifique-se para continuar</p>
                </div>

                <!-- Seletor de Perfis -->
                <div class="btn-group w-100 mb-4 p-1 rounded" style="background: #0f1115; border: 1px solid rgba(197, 160, 89, 0.2);">
                    <a href="?perfil=cliente" class="btn btn-sm <?= ($perfilSelecionado === 'cliente') ? 'btn-gold fw-bold' : 'btn-outline-dark text-secondary border-0' ?>">
                        Cliente
                    </a>
                    <a href="?perfil=barbeiro" class="btn btn-sm <?= ($perfilSelecionado === 'barbeiro') ? 'btn-gold fw-bold' : 'btn-outline-dark text-secondary border-0' ?>">
                        Barbeiro
                    </a>
                    <a href="?perfil=recepcao" class="btn btn-sm <?= ($perfilSelecionado === 'recepcao') ? 'btn-gold fw-bold' : 'btn-outline-dark text-secondary border-0' ?>">
                        Recepção
                    </a>
                    <a href="?perfil=dono" class="btn btn-sm <?= ($perfilSelecionado === 'dono') ? 'btn-gold fw-bold' : 'btn-outline-dark text-secondary border-0' ?>">
                        Dono
                    </a>
                </div>

                <!-- Formulário de Login -->
                <form action="<?= BASE_URL ?>/actions/auth_login.php" method="POST">
                    <input type="hidden" name="perfil" id="inputPerfil" value="<?= htmlspecialchars($perfilSelecionado) ?>">

                    <div class="mb-3">
                        <label for="login" class="form-label">E-mail ou CPF</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-gold">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control" id="login" name="login" placeholder="seu.email@gmail.com ou CPF" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="senha" class="form-label">Senha de Acesso</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-gold">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('senha', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-3 fw-bold mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Acessar <?= $perfisNomes[$perfilSelecionado] ?>
                    </button>
                </form>

                <?php if ($perfilSelecionado === 'cliente'): ?>
                    <div class="text-center pt-3 border-top border-secondary border-opacity-25">
                        <p class="text-secondary small mb-2">Primeira vez na Corte na Navalha?</p>
                        <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-outline-gold btn-sm w-100 py-2">
                            <i class="bi bi-person-plus me-1"></i> Criar Nova Conta de Cliente
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Atalhos de Acesso de Demonstração Rápida -->
                <div class="mt-4 p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px dashed rgba(197, 160, 89, 0.3);">
                    <small class="text-gold fw-bold d-block mb-2 text-center text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="bi bi-lightning-charge-fill"></i> Acesso Rápido para Teste
                    </small>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-truncate text-white small" onclick="preencherLogin('joao.cliente@gmail.com', '123456', 'cliente')">
                                👤 Cliente
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-truncate text-white small" onclick="preencherLogin('lucas@cortenanavalha.com.br', '123456', 'barbeiro')">
                                ✂️ Barbeiro
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-truncate text-white small" onclick="preencherLogin('recepcao@cortenanavalha.com.br', '123456', 'recepcao')">
                                📋 Recepção
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-truncate text-white small" onclick="preencherLogin('dono@cortenanavalha.com.br', '123456', 'dono')">
                                👑 Dono
                            </button>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted" style="font-size: 0.75rem;">Senha padrão de teste: 123456</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSenha(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function preencherLogin(email, senha, perfil) {
    document.getElementById('login').value = email;
    document.getElementById('senha').value = senha;
    document.getElementById('inputPerfil').value = perfil;
    showToast('info', 'Dados Preenchidos', 'Credenciais de ' + perfil + ' inseridas. Clique em Acessar!');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
