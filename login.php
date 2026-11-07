<?php
/**
 * Página de Login - Barbearia Corte na Navalha
 * Identificação Inteligente: Cliente vs Profissional
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

$paginaTitulo = 'Entrar | Barbearia Corte na Navalha';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <!-- Card Principal de Login com Borda Vermelha -->
            <div class="barber-card border-danger shadow-lg p-4 p-md-5">
                <!-- Logotipo Oficial da Barbearia -->
                <div class="text-center mb-4">
                    <div class="mb-3 d-inline-block">
                        <?php 
                            $logoTamanho = 'large';
                            $mostrarTexto = true;
                            require __DIR__ . '/includes/logo.php'; 
                        ?>
                    </div>
                    <h4 class="text-white fw-bold mb-1 mt-2">Acesso ao Sistema</h4>
                    <p class="text-secondary small mb-0">Identifique-se para gerenciar seus horários ou atendimentos</p>
                </div>

                <!-- Seletor de Categoria: Cliente vs Profissional -->
                <div class="mb-4">
                    <label class="form-label small text-secondary fw-bold text-uppercase d-block text-center mb-2">
                        Selecione seu Perfil de Acesso:
                    </label>
                    <div class="btn-group w-100 p-1 rounded" style="background: #090a0d; border: 1px solid rgba(220, 38, 38, 0.3);">
                        <a href="?perfil=cliente" class="btn btn-sm <?= ($perfilSelecionado === 'cliente') ? 'btn-red fw-bold shadow-sm' : 'btn-outline-dark text-secondary border-0' ?>">
                            <i class="bi bi-person-fill me-1"></i> Cliente
                        </a>
                        <a href="?perfil=barbeiro" class="btn btn-sm <?= ($perfilSelecionado === 'barbeiro') ? 'btn-red fw-bold shadow-sm' : 'btn-outline-dark text-secondary border-0' ?>">
                            <i class="bi bi-scissors me-1"></i> Barbeiro
                        </a>
                        <a href="?perfil=recepcao" class="btn btn-sm <?= ($perfilSelecionado === 'recepcao') ? 'btn-red fw-bold shadow-sm' : 'btn-outline-dark text-secondary border-0' ?>">
                            <i class="bi bi-receipt me-1"></i> Recepção
                        </a>
                        <a href="?perfil=dono" class="btn btn-sm <?= ($perfilSelecionado === 'dono') ? 'btn-red fw-bold shadow-sm' : 'btn-outline-dark text-secondary border-0' ?>">
                            <i class="bi bi-shield-lock me-1"></i> Gestão
                        </a>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-danger" style="font-size: 0.72rem;">
                            <i class="bi bi-info-circle me-1"></i> O sistema reconhece automaticamente sua conta como <strong>Cliente</strong> ou <strong>Profissional</strong>.
                        </small>
                    </div>
                </div>

                <!-- Formulário de Login Seguro -->
                <form action="<?= BASE_URL ?>/actions/auth_login.php" method="POST">
                    <input type="hidden" name="perfil" id="inputPerfil" value="<?= htmlspecialchars($perfilSelecionado) ?>">

                    <div class="mb-3">
                        <label for="login" class="form-label">E-mail ou CPF</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-danger">
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
                            <span class="input-group-text bg-dark border-secondary text-danger">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('senha', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-red w-100 py-3 fw-bold mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar no Sistema
                    </button>
                </form>

                <?php if ($perfilSelecionado === 'cliente'): ?>
                    <div class="text-center pt-3 border-top border-secondary border-opacity-25">
                        <p class="text-secondary small mb-2">Primeira vez na Corte na Navalha?</p>
                        <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-outline-red btn-sm w-100 py-2">
                            <i class="bi bi-person-plus me-1"></i> Criar Nova Conta de Cliente
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!acabouOTeste()): ?>
                <!-- Atalhos de Acesso Rápido para Teste (Ocultados automaticamente quando ACABOUOTESTE for ativado) -->
                <div class="mt-4 p-3 rounded" style="background: rgba(220, 38, 38, 0.05); border: 1px dashed rgba(220, 38, 38, 0.4);">
                    <small class="text-danger fw-bold d-block mb-2 text-center text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="bi bi-lightning-charge-fill"></i> Acessos Rápidos de Demonstração
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
                        <small class="text-muted" style="font-size: 0.72rem;">Senha padrão de teste: 123456</small>
                    </div>
                </div>
                <?php endif; ?>
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
    showToast('info', 'Dados Preenchidos', 'Perfil de teste selecionado: ' + perfil.toUpperCase());
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
