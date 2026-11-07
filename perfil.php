<?php
/**
 * Meu Perfil & Segurança - Barbearia Corte na Navalha
 * Identificação Clara: Cliente vs Profissional
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin(['cliente', 'barbeiro', 'recepcao', 'dono']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$mensagemSucesso = '';
$mensagemErro = '';

// Buscar dados completos do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $usuario['id']]);
$dadosUsuario = $stmt->fetch();

// Atualizar Perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_perfil') {
    $telefone = trim($_POST['telefone'] ?? '');
    $novaSenha = trim($_POST['nova_senha'] ?? '');
    $chavePix = trim($_POST['chave_pix'] ?? '');
    $tipoChavePix = trim($_POST['tipo_chave_pix'] ?? 'E-mail');

    try {
        if (!empty($novaSenha)) {
            if (strlen($novaSenha) < 6) {
                throw new Exception('A nova senha deve ter no mínimo 6 caracteres.');
            }
            $hashSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE usuarios SET telefone = :telefone, chave_pix = :chave_pix, tipo_chave_pix = :tipo_chave_pix, senha = :senha WHERE id = :id");
            $stmtUp->execute([
                ':telefone'       => $telefone,
                ':chave_pix'      => $chavePix,
                ':tipo_chave_pix' => $tipoChavePix,
                ':senha'          => $hashSenha,
                ':id'             => $usuario['id']
            ]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET telefone = :telefone, chave_pix = :chave_pix, tipo_chave_pix = :tipo_chave_pix WHERE id = :id");
            $stmtUp->execute([
                ':telefone'       => $telefone,
                ':chave_pix'      => $chavePix,
                ':tipo_chave_pix' => $tipoChavePix,
                ':id'             => $usuario['id']
            ]);
        }

        $_SESSION['usuario_telefone'] = $telefone;
        setMensagemToast('success', 'Perfil Atualizado!', 'Suas informações cadastrais foram salvas com sucesso.');
        header('Location: ' . BASE_URL . '/perfil.php');
        exit;

    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();
    }
}

$ehProfissional = isProfissional($dadosUsuario);
$paginaTitulo = 'Meu Perfil | Corte na Navalha';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Botão Voltar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= getHomeUrl() ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Card Principal de Identificação -->
            <div class="barber-card border-danger shadow-lg p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom border-secondary border-opacity-25">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="width: 65px; height: 65px; font-size: 2rem;">
                            <i class="bi <?= $ehProfissional ? 'bi-scissors' : 'bi-person-fill' ?>"></i>
                        </div>
                        <div>
                            <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($dadosUsuario['nome']) ?></h4>
                            <p class="text-secondary small mb-0"><?= htmlspecialchars($dadosUsuario['email']) ?></p>
                        </div>
                    </div>

                    <!-- Visualização e Compreensão de Perfil -->
                    <div>
                        <?php if ($ehProfissional): ?>
                            <span class="badge badge-role-profissional p-2 fs-6">
                                <i class="bi bi-shield-check me-1"></i> PROFISSIONAL • <?= strtoupper($dadosUsuario['perfil']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-role-cliente p-2 fs-6">
                                <i class="bi bi-person-fill me-1"></i> CONTA DE CLIENTE
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($mensagemErro): ?>
                    <div class="alert alert-danger py-2 mt-3 small fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($mensagemErro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="mt-4">
                    <input type="hidden" name="action" value="atualizar_perfil">

                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-person-badge text-danger me-2"></i>Informações Pessoais
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control text-secondary bg-dark" value="<?= htmlspecialchars($dadosUsuario['nome']) ?>" readonly>
                            <small class="text-muted" style="font-size: 0.72rem;">Para alterar o nome, procure a administração.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CPF Cadastrado</label>
                            <input type="text" class="form-control text-secondary bg-dark" value="<?= htmlspecialchars($dadosUsuario['cpf']) ?>" readonly>
                            <small class="text-muted" style="font-size: 0.72rem;">CPF vinculado aos recibos oficiais.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control text-secondary bg-dark" value="<?= htmlspecialchars($dadosUsuario['email']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone / WhatsApp</label>
                            <input type="text" class="form-control mask-phone" id="telefone" name="telefone" value="<?= htmlspecialchars($dadosUsuario['telefone']) ?>" required>
                        </div>
                    </div>

                    <?php if ($ehProfissional): ?>
                        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                            <i class="bi bi-qr-code text-danger me-2"></i>Chave PIX para Comissões
                        </h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="tipo_chave_pix" class="form-label">Tipo de Chave PIX</label>
                                <select class="form-select" id="tipo_chave_pix" name="tipo_chave_pix">
                                    <option value="E-mail" <?= (($dadosUsuario['tipo_chave_pix'] ?? '') === 'E-mail') ? 'selected' : '' ?>>E-mail</option>
                                    <option value="CPF" <?= (($dadosUsuario['tipo_chave_pix'] ?? '') === 'CPF') ? 'selected' : '' ?>>CPF</option>
                                    <option value="Telefone" <?= (($dadosUsuario['tipo_chave_pix'] ?? '') === 'Telefone') ? 'selected' : '' ?>>Telefone</option>
                                    <option value="Aleatória" <?= (($dadosUsuario['tipo_chave_pix'] ?? '') === 'Aleatória') ? 'selected' : '' ?>>Chave Aleatória</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label for="chave_pix" class="form-label">Sua Chave PIX</label>
                                <input type="text" class="form-control font-monospace" id="chave_pix" name="chave_pix" value="<?= htmlspecialchars($dadosUsuario['chave_pix'] ?? '') ?>" placeholder="Informe sua chave para receber comissões">
                            </div>
                        </div>
                    <?php endif; ?>

                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-shield-lock text-danger me-2"></i>Segurança & Senha de Acesso
                    </h5>

                    <div class="mb-4">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para manter a senha atual" minlength="6">
                        <small class="text-muted" style="font-size: 0.72rem;">Tip: opcional. Preencha apenas se desejar trocar a senha atual (mínimo 6 caracteres).</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-red py-3 px-4 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Salvar Alterações
                        </button>
                        
                        <?php if ($dadosUsuario['perfil'] === 'cliente'): ?>
                            <a href="<?= BASE_URL ?>/cliente/dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Voltar aos Agendamentos
                            </a>
                        <?php elseif ($dadosUsuario['perfil'] === 'barbeiro'): ?>
                            <a href="<?= BASE_URL ?>/barbeiro/dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
                            </a>
                        <?php elseif ($dadosUsuario['perfil'] === 'recepcao'): ?>
                            <a href="<?= BASE_URL ?>/recepcao/dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Voltar à Recepção
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Voltar à Gestão
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
