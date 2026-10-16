<?php
/**
 * Processamento de Login Seguro
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$perfilDesejado = trim($_POST['perfil'] ?? '');

if (empty($login) || empty($senha)) {
    setMensagemToast('warning', 'Campos Obrigatórios', 'Por favor, informe seu e-mail/CPF e a senha.');
    header('Location: ' . BASE_URL . '/login.php' . ($perfilDesejado ? '?perfil=' . urlencode($perfilDesejado) : ''));
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Remove pontuação caso tenha digitado CPF
    $loginLimpo = preg_replace('/[^0-9]/', '', $login);

    // Busca usuário por e-mail ou por CPF
    $sql = "SELECT * FROM usuarios WHERE (email = :loginEmail OR cpf = :loginCpf OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = :loginLimpo) AND ativo = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':loginEmail' => $login,
        ':loginCpf'   => $login,
        ':loginLimpo' => $loginLimpo
    ]);
    
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Se foi especificado perfil no formulário e for diferente (a menos que seja dono testando outro perfil)
        if (!empty($perfilDesejado) && $usuario['perfil'] !== $perfilDesejado && $usuario['perfil'] !== 'dono') {
            setMensagemToast('danger', 'Perfil Incorreto', "Esta conta não possui permissão para o perfil de " . ucfirst($perfilDesejado) . ".");
            header('Location: ' . BASE_URL . '/login.php?perfil=' . urlencode($perfilDesejado));
            exit;
        }

        // Salvar dados na sessão nativa
        $_SESSION['usuario_id']       = $usuario['id'];
        $_SESSION['usuario_nome']     = $usuario['nome'];
        $_SESSION['usuario_email']    = $usuario['email'];
        $_SESSION['usuario_cpf']      = $usuario['cpf'];
        $_SESSION['usuario_telefone'] = $usuario['telefone'];
        $_SESSION['usuario_perfil']   = $usuario['perfil'];

        setMensagemToast('success', 'Bem-vindo(a)!', 'Olá, ' . htmlspecialchars($usuario['nome']) . '! Login efetuado com sucesso.');

        // Redireciona para o portal correto
        redirecionarPorPerfil($usuario['perfil']);
    } else {
        setMensagemToast('danger', 'Acesso Recusado', 'E-mail/CPF ou senha inválidos. Tente novamente.');
        header('Location: ' . BASE_URL . '/login.php' . ($perfilDesejado ? '?perfil=' . urlencode($perfilDesejado) : ''));
        exit;
    }
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro no Sistema', 'Ocorreu uma falha ao tentar autenticar. Tente novamente.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
