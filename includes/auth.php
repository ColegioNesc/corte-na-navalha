<?php
/**
 * Gerenciamento de Autenticação e Sessões Nativas
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';

function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

iniciarSessao();

/**
 * Retorna se há um usuário autenticado
 */
function usuarioLogado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Retorna os dados do usuário autenticado na sessão
 */
function getUsuarioLogado() {
    if (!usuarioLogado()) {
        return null;
    }
    return [
        'id'               => $_SESSION['usuario_id'],
        'nome'             => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email'            => $_SESSION['usuario_email'] ?? '',
        'perfil'           => $_SESSION['usuario_perfil'] ?? 'cliente',
        'cpf'              => $_SESSION['usuario_cpf'] ?? '',
        'telefone'         => $_SESSION['usuario_telefone'] ?? '',
        'permissoes'       => $_SESSION['usuario_permissoes'] ?? [],
        'permite_catalogo' => $_SESSION['permite_catalogo'] ?? 0
    ];
}

/**
 * Verifica se o usuário autenticado possui determinada permissão
 */
function temPermissao($permissao) {
    if (!usuarioLogado()) return false;
    $perfil = $_SESSION['usuario_perfil'] ?? '';
    // Proprietário tem sempre acesso irrestrito
    if ($perfil === 'dono') return true;
    $perms = $_SESSION['usuario_permissoes'] ?? [];
    if (!is_array($perms)) return false;
    return in_array($permissao, $perms);
}

/**
 * Protege rotas exigindo autenticação e perfis permitidos
 * @param array|string $perfisPermitidos
 */
function exigirLogin($perfisPermitidos = []) {
    if (!usuarioLogado()) {
        setMensagemToast('warning', 'Acesso Restrito', 'Faça login para acessar esta página.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    if (!empty($perfisPermitidos)) {
        if (is_string($perfisPermitidos)) {
            $perfisPermitidos = [$perfisPermitidos];
        }
        $perfilAtual = $_SESSION['usuario_perfil'] ?? '';
        if (!in_array($perfilAtual, $perfisPermitidos)) {
            setMensagemToast('danger', 'Acesso Negado', 'Você não tem permissão para acessar esta área.');
            redirecionarPorPerfil($perfilAtual);
            exit;
        }
    }
}

/**
 * Retorna a URL da Home principal conforme o perfil do usuário
 */
function getHomeUrl($perfil = null) {
    if (!$perfil) {
        $usuario = getUsuarioLogado();
        $perfil = $usuario['perfil'] ?? null;
    }
    switch ($perfil) {
        case 'dono':
            return BASE_URL . '/admin/dashboard.php';
        case 'barbeiro':
            return BASE_URL . '/barbeiro/dashboard.php';
        case 'recepcao':
            return BASE_URL . '/recepcao/dashboard.php';
        case 'cliente':
            return BASE_URL . '/cliente/dashboard.php';
        default:
            return BASE_URL . '/index.php';
    }
}

/**
 * Redireciona o usuário para o dashboard apropriado de acordo com seu perfil
 */
function redirecionarPorPerfil($perfil) {
    header('Location: ' . getHomeUrl($perfil));
    exit;
}

/**
 * Retorna se o usuário é um profissional (Barbeiro, Recepção ou Dono)
 */
function isProfissional($usuario = null) {
    if (!$usuario) $usuario = getUsuarioLogado();
    if (!$usuario) return false;
    return in_array($usuario['perfil'], ['barbeiro', 'recepcao', 'dono']);
}

/**
 * Retorna se o usuário é um cliente
 */
function isCliente($usuario = null) {
    if (!$usuario) $usuario = getUsuarioLogado();
    if (!$usuario) return false;
    return ($usuario['perfil'] === 'cliente');
}

/**
 * Retorna o título legível da categoria do usuário (Cliente ou Profissional)
 */
function getTipoUsuarioFormatado($perfil) {
    switch ($perfil) {
        case 'barbeiro':
            return 'Profissional (Barbeiro)';
        case 'recepcao':
            return 'Profissional (Recepção)';
        case 'dono':
            return 'Profissional (Proprietário)';
        case 'cliente':
        default:
            return 'Cliente';
    }
}

/**
 * Define mensagens estilo Toast para exibição na próxima tela
 */
function setMensagemToast($tipo, $titulo, $mensagem) {
    iniciarSessao();
    $_SESSION['toast_mensagem'] = [
        'tipo'     => $tipo, // success, danger, warning, info
        'titulo'   => $titulo,
        'mensagem' => $mensagem
    ];
}

/**
 * Recupera e consome a mensagem Toast pendente
 */
function getMensagensToast() {
    iniciarSessao();
    if (isset($_SESSION['toast_mensagem'])) {
        $msg = $_SESSION['toast_mensagem'];
        unset($_SESSION['toast_mensagem']);
        return $msg;
    }
    return null;
}

/**
 * Validação de CPF brasileiro
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

/**
 * Validação de Gmail/Email
 */
function validarEmail($email, $exigirGmail = false) {
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if (!$email) return false;
    if ($exigirGmail) {
        return (bool)preg_match('/@(gmail\.com|googlemail\.com)$/i', $email);
    }
    return true;
}
