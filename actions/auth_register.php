<?php
/**
 * Processamento de Cadastro de Novo Usuário (Cliente / Dono)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/cadastro.php');
    exit;
}

$nome      = trim($_POST['nome'] ?? '');
$email     = trim($_POST['email'] ?? '');
$cpf       = trim($_POST['cpf'] ?? '');
$telefone  = trim($_POST['telefone'] ?? '');
$senha     = trim($_POST['senha'] ?? '');
$confirma  = trim($_POST['confirma_senha'] ?? '');
$perfil    = trim($_POST['perfil'] ?? 'cliente');

// Normalizar perfil
if (!in_array($perfil, ['cliente', 'dono'])) {
    $perfil = 'cliente';
}

// 1. Validação de preenchimento
if (empty($nome) || empty($email) || empty($cpf) || empty($senha)) {
    setMensagemToast('warning', 'Campos Obrigatórios', 'Por favor, preencha todos os campos obrigatórios.');
    header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
    exit;
}

// 2. Validação do Gmail conforme pré-condição dos requisitos
if (!validarEmail($email, true)) {
    setMensagemToast('warning', 'E-mail Inválido', 'Por favor, informe uma conta de Gmail válida (ex: seu.nome@gmail.com).');
    header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
    exit;
}

// 3. Validação do CPF
if (!validarCPF($cpf)) {
    setMensagemToast('warning', 'CPF Inválido', 'O CPF informado não é válido. Verifique os números digitados.');
    header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
    exit;
}

// 4. Confirmação de senha
if ($senha !== $confirma) {
    setMensagemToast('warning', 'Senhas Divergentes', 'A confirmação de senha não coincide com a senha digitada.');
    header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
    exit;
}

if (strlen($senha) < 6) {
    setMensagemToast('warning', 'Senha Curta', 'A senha deve conter no mínimo 6 caracteres.');
    header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
    exit;
}

try {
    $pdo = getDBConnection();

    // 5. Verificar se e-mail ou CPF já estão cadastrados
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email OR cpf = :cpf LIMIT 1");
    $stmtCheck->execute([':email' => $email, ':cpf' => $cpf]);
    if ($stmtCheck->fetch()) {
        setMensagemToast('danger', 'Conta Já Existente', 'Já existe um cadastro com este e-mail ou CPF. Faça login ou use outro.');
        header('Location: ' . BASE_URL . '/cadastro.php' . ($perfil === 'dono' ? '?perfil=dono' : ''));
        exit;
    }

    // 6. Criptografia segura da senha
    $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

    // 7. Inserção com Prepared Statement
    $stmtInsert = $pdo->prepare("
        INSERT INTO usuarios (nome, email, cpf, telefone, senha, perfil, ativo)
        VALUES (:nome, :email, :cpf, :telefone, :senha, :perfil, 1)
    ");
    $stmtInsert->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':cpf'      => $cpf,
        ':telefone' => $telefone,
        ':senha'    => $hashSenha,
        ':perfil'   => $perfil
    ]);

    $novoId = $pdo->lastInsertId();

    // 8. Iniciar sessão automaticamente
    $_SESSION['usuario_id']       = $novoId;
    $_SESSION['usuario_nome']     = $nome;
    $_SESSION['usuario_email']    = $email;
    $_SESSION['usuario_cpf']      = $cpf;
    $_SESSION['usuario_telefone'] = $telefone;
    $_SESSION['usuario_perfil']   = $perfil;

    setMensagemToast('success', 'Cadastro Concluído!', 'Sua conta foi criada com sucesso. Seja bem-vindo à Barbearia Corte na Navalha!');
    redirecionarPorPerfil($perfil);

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro no Cadastro', 'Ocorreu um erro ao salvar o cadastro. Tente novamente mais tarde.');
    header('Location: ' . BASE_URL . '/cadastro.php');
    exit;
}
