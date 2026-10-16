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
        'id'        => $_SESSION['usuario_id'],
        'nome'      => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email'     => $_SESSION['usuario_email'] ?? '',
        'perfil'    => $_SESSION['usuario_perfil'] ?? 'cliente',
        'cpf'       => $_SESSION['usuario_cpf'] ?? '',
        'telefone'  => $_SESSION['usuario_telefone'] ?? ''
    ];
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
 * Redireciona o usuário para o dashboard apropriado de acordo com seu perfil
 */
function redirecionarPorPerfil($perfil) {
    switch ($perfil) {
        case 'dono':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        case 'barbeiro':
            header('Location: ' . BASE_URL . '/barbeiro/dashboard.php');
            break;
        case 'recepcao':
            header('Location: ' . BASE_URL . '/recepcao/dashboard.php');
            break;
        case 'cliente':
        default:
            header('Location: ' . BASE_URL . '/cliente/dashboard.php');
            break;
    }
    exit;
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
