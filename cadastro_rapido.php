<?php
/**
 * Cadastro Rápido de Balcão (Via QR Code da Recepção)
 * Barbearia Corte na Navalha
 * Solicita apenas Nome, E-mail e CPF
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$sucesso = false;
$erro = '';
$nomeCadastrado = '';
$cpfCadastrado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    // Se senha vazia, usa os 6 primeiros dígitos do CPF ou padrão 123456
    if (empty($senha)) {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        $senha = (strlen($cpfLimpo) >= 6) ? substr($cpfLimpo, 0, 6) : '123456';
    }

    if (empty($nome) || empty($email) || empty($cpf)) {
        $erro = 'Por favor, preencha Nome, E-mail e CPF.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Formato de e-mail inválido.';
    } else {
        try {
            // Verificar se o e-mail ou CPF já existem
            $stmtCheck = $pdo->prepare("SELECT id, nome, email, cpf FROM usuarios WHERE email = :email OR cpf = :cpf LIMIT 1");
            $stmtCheck->execute([':email' => $email, ':cpf' => $cpf]);
            $existente = $stmtCheck->fetch();

            if ($existente) {
                // Já cadastrado
                $sucesso = true;
                $nomeCadastrado = $existente['nome'];
                $cpfCadastrado = $existente['cpf'];
                $jaExistia = true;

                // Loga o cliente automaticamente para conveniência
                $_SESSION['usuario_id'] = $existente['id'];
                $_SESSION['usuario_nome'] = $existente['nome'];
                $_SESSION['usuario_email'] = $existente['email'];
                $_SESSION['usuario_perfil'] = 'cliente';
                $_SESSION['usuario_tipo'] = 'cliente';
            } else {
                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, cpf, senha, perfil, ativo, criado_em)
                    VALUES (:nome, :email, :cpf, :senha, 'cliente', 1, NOW())
                ");
                $stmtInsert->execute([
                    ':nome'  => $nome,
                    ':email' => $email,
                    ':cpf'   => $cpf,
                    ':senha' => $hashSenha
                ]);

                $novoId = $pdo->lastInsertId();
                $sucesso = true;
                $nomeCadastrado = $nome;
                $cpfCadastrado = $cpf;
                $jaExistia = false;

                // Loga o cliente automaticamente
                $_SESSION['usuario_id'] = $novoId;
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_perfil'] = 'cliente';
                $_SESSION['usuario_tipo'] = 'cliente';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao processar cadastro: ' . $e->getMessage();
        }
    }
}

$paginaTitulo = 'Cadastro Rápido de Balcão | ' . ($empresa['nome_barbearia'] ?? 'Corte na Navalha');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($paginaTitulo) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background-color: #0b0c10;
            color: #f1f5f9;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .fast-card {
            background: #111319;
            border: 1px solid rgba(220, 38, 38, 0.4);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            max-width: 440px;
            width: 100%;
            padding: 2rem;
        }
        .brand-title {
            font-family: 'Cinzel', serif;
            color: #dc2626;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="fast-card">
    <div class="text-center mb-3">
        <div class="stat-icon mx-auto mb-2" style="width: 55px; height: 55px; font-size: 1.6rem; background: rgba(220, 38, 38, 0.15); color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #dc2626;">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <h4 class="brand-title fw-bold mb-1"><?= htmlspecialchars($empresa['nome_barbearia'] ?? 'CORTE NA NAVALHA') ?></h4>
        <p class="text-secondary small mb-0">Cadastro Rápido de Balcão</p>
    </div>

    <?php if ($sucesso): ?>
        <div class="text-center py-4">
            <div class="text-success fs-1 mb-2">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="text-white fw-bold mb-1">
                <?= !empty($jaExistia) ? 'Identificação Confirmada!' : 'Cadastro Realizado com Sucesso!' ?>
            </h5>
            <p class="text-secondary small mb-3">
                Olá, <strong class="text-white"><?= htmlspecialchars($nomeCadastrado) ?></strong>!<br>
                <?= !empty($jaExistia) ? 'Seu cadastro já estava em nossa base.' : 'Seus dados foram vinculados à barbearia.' ?>
            </p>

            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-4 text-start">
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <span>Nome:</span>
                    <strong class="text-white"><?= htmlspecialchars($nomeCadastrado) ?></strong>
                </div>
                <div class="d-flex justify-content-between small text-secondary">
                    <span>CPF:</span>
                    <strong class="text-gold font-monospace"><?= htmlspecialchars($cpfCadastrado) ?></strong>
                </div>
            </div>

            <div class="alert alert-warning py-2 small mb-4">
                <i class="bi bi-person-badge me-1"></i> Informe seu <strong>Nome ou CPF</strong> ao atendente no balcão para confirmar o agendamento!
            </div>

            <a href="<?= BASE_URL ?>/cliente/dashboard.php" class="btn btn-gold w-100 py-2 fw-bold">
                <i class="bi bi-scissors me-1"></i> Acessar Meu Painel e Catálogo
            </a>
        </div>
    <?php else: ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small fw-semibold">
                <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formCadastroRapido">
            <!-- Nome -->
            <div class="mb-3">
                <label for="nome" class="form-label small text-secondary fw-semibold">
                    Nome Completo <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-gold"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Lucas Oliveira" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" autofocus>
                </div>
            </div>

            <!-- E-mail -->
            <div class="mb-3">
                <label for="email" class="form-label small text-secondary fw-semibold">
                    E-mail <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-gold"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="lucas@exemplo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Tip: utilizado para envio de comprovantes e recibos.</small>
            </div>

            <!-- CPF -->
            <div class="mb-3">
                <label for="cpf" class="form-label small text-secondary fw-semibold">
                    CPF <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-gold"><i class="bi bi-card-text"></i></span>
                    <input type="text" class="form-control font-monospace" id="cpf" name="cpf" placeholder="000.000.000-00" required maxlength="14" oninput="mascaraCpf(this)" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Tip: utilizado para localizar seu atendimento no balcão.</small>
            </div>

            <!-- Senha -->
            <div class="mb-4">
                <label for="senha" class="form-label small text-secondary fw-semibold d-flex justify-content-between">
                    <span>Criar Senha de Acesso</span>
                    <span class="badge bg-secondary text-light">Opcional</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-gold"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite uma senha ou deixe em branco">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Tip: se deixar em branco, sua senha inicial será os 6 primeiros dígitos do seu CPF.</small>
            </div>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100 py-3 fw-bold fs-6 shadow">
                <i class="bi bi-check2-circle me-1"></i> Concluir Cadastro Rápido
            </button>
        </form>

        <div class="text-center mt-3 pt-2 border-top border-secondary border-opacity-25">
            <small class="text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-shield-lock me-1"></i> Seus dados estão protegidos conforme as leis vigentes e o CDC.
            </small>
        </div>
    <?php endif; ?>
</div>

<script>
function mascaraCpf(i) {
    let v = i.value.replace(/\D/g, "");
    if (v.length > 11) v = v.substring(0, 11);
    v = v.replace(/(\d{3})(\d)/, "$1.$2");
    v = v.replace(/(\d{3})(\d)/, "$1.$2");
    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    i.value = v;
}
</script>

</body>
</html>

