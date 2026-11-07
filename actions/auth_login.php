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
    $sql = "SELECT * FROM usuarios WHERE (email = :loginEmail OR cpf = :loginCpf OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = :loginLimpo) LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':loginEmail' => $login,
        ':loginCpf'   => $login,
        ':loginLimpo' => $loginLimpo
    ]);
    
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        if (empty($usuario['ativo'])) {
            setMensagemToast('danger', 'Acesso Bloqueado', 'Esta conta foi inativada pela administração da barbearia.');
            header('Location: ' . BASE_URL . '/login.php' . ($perfilDesejado ? '?perfil=' . urlencode($perfilDesejado) : ''));
            exit;
        }

        $perfilReal = $usuario['perfil'];
        $isProf = in_array($perfilReal, ['barbeiro', 'recepcao', 'dono']);
        $tipoUsuario = $isProf ? 'profissional' : 'cliente';
        $tipoFormatado = getTipoUsuarioFormatado($perfilReal);

        // Salvar dados na sessão nativa
        $_SESSION['usuario_id']          = $usuario['id'];
        $_SESSION['usuario_nome']        = $usuario['nome'];
        $_SESSION['usuario_email']       = $usuario['email'];
        $_SESSION['usuario_cpf']         = $usuario['cpf'];
        $_SESSION['usuario_telefone']    = $usuario['telefone'];
        $_SESSION['usuario_perfil']      = $perfilReal;
        $_SESSION['usuario_tipo']        = $tipoUsuario;
        $_SESSION['usuario_permissoes']  = !empty($usuario['permissoes']) ? json_decode($usuario['permissoes'], true) : [];
        $_SESSION['permite_catalogo']    = (int)($usuario['permite_catalogo_proprio'] ?? 0);

        $msgBoasVindas = $isProf 
            ? "Olá, " . htmlspecialchars($usuario['nome']) . "! Acesso operacional liberado como {$tipoFormatado}."
            : "Olá, " . htmlspecialchars($usuario['nome']) . "! Bem-vindo(a) à Barbearia Corte na Navalha.";

        setMensagemToast('success', 'Acesso Concluído!', $msgBoasVindas);

        // Redireciona para o portal correto de acordo com a compreensão do perfil
        redirecionarPorPerfil($perfilReal);
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
