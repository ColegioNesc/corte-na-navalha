<?php
/**
 * Gerenciamento de Autenticação e Sessão de Usuário
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se há um usuário autenticado na sessão atual
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtém os dados do usuário atualmente logado
 */
function getCurrentUser(PDO $pdo): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, nome, email, raio_padrao, tipo_alarme_padrao, som_alarme, criado_em FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Autentica o usuário pelo e-mail e senha
 */
function loginUser(PDO $pdo, string $email, string $senha): array {
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, raio_padrao, tipo_alarme_padrao, som_alarme FROM usuarios WHERE email = ?");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    if (!password_verify($senha, $user['senha'])) {
        return ['success' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    // Registra na sessão nativa
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['raio_padrao'] = $user['raio_padrao'];
    $_SESSION['tipo_alarme_padrao'] = $user['tipo_alarme_padrao'];
    $_SESSION['som_alarme'] = $user['som_alarme'];

    return [
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'user' => [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'raio_padrao' => $user['raio_padrao'],
            'tipo_alarme_padrao' => $user['tipo_alarme_padrao'],
            'som_alarme' => $user['som_alarme']
        ]
    ];
}

/**
 * Registra um novo usuário no banco com password_hash seguro
 */
function registerUser(PDO $pdo, string $nome, string $email, string $senha, int $raioPadrao = 300, string $tipoAlarme = 'ambos', string $somAlarme = 'sirene'): array {
    $nome = trim($nome);
    $email = strtolower(trim($email));

    if (empty($nome) || empty($email) || empty($senha)) {
        return ['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Formato de e-mail inválido.'];
    }

    if (strlen($senha) < 6) {
        return ['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres.'];
    }

    // Verifica se e-mail já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Este e-mail já está cadastrado. Tente fazer login.'];
    }

    // Hash seguro da senha
    $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

    $insert = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, raio_padrao, tipo_alarme_padrao, som_alarme)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([$nome, $email, $hashSenha, $raioPadrao, $tipoAlarme, $somAlarme]);

    $novoId = (int)$pdo->lastInsertId();

    // Inicia sessão automaticamente após cadastro
    $_SESSION['user_id'] = $novoId;
    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_email'] = $email;
    $_SESSION['raio_padrao'] = $raioPadrao;
    $_SESSION['tipo_alarme_padrao'] = $tipoAlarme;
    $_SESSION['som_alarme'] = $somAlarme;

    return [
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!',
        'user' => [
            'id' => $novoId,
            'nome' => $nome,
            'email' => $email,
            'raio_padrao' => $raioPadrao,
            'tipo_alarme_padrao' => $tipoAlarme,
            'som_alarme' => $somAlarme
        ]
    ];
}

/**
 * Encerra a sessão do usuário
 */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
