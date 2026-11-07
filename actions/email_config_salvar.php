<?php
/**
 * Salvar Configurações de E-mail (SMTP / Gmail / Hospedado)
 * Apenas Dono (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/email_config.php');
    exit;
}

$driver         = trim($_POST['driver'] ?? 'gmail');
$host           = trim($_POST['host'] ?? 'smtp.gmail.com');
$porta          = (int)($_POST['porta'] ?? 587);
$seguranca      = trim($_POST['seguranca'] ?? 'tls');
$usuario        = trim($_POST['usuario'] ?? '');
$senha          = trim($_POST['senha'] ?? '');
$remetenteNome  = trim($_POST['remetente_nome'] ?? 'Corte na Navalha Barbearia');
$remetenteEmail = trim($_POST['remetente_email'] ?? '');
$ativo          = isset($_POST['ativo']) ? 1 : 0;

try {
    $pdo = getDBConnection();

    // Se senha foi deixada em branco, preserva a senha anterior
    if (empty($senha)) {
        $stmtOld = $pdo->query("SELECT senha FROM configuracao_email LIMIT 1");
        $oldPass = $stmtOld->fetchColumn();
        if ($oldPass) $senha = $oldPass;
    }

    $sql = "
        UPDATE configuracao_email 
        SET driver = :driver, host = :host, porta = :porta, seguranca = :seguranca,
            usuario = :usuario, senha = :senha, remetente_nome = :rem_nome,
            remetente_email = :rem_email, ativo = :ativo
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':driver'    => $driver,
        ':host'      => $host,
        ':porta'     => $porta,
        ':seguranca' => $seguranca,
        ':usuario'   => $usuario,
        ':senha'     => $senha,
        ':rem_nome'  => $remetenteNome,
        ':rem_email' => $remetenteEmail,
        ':ativo'     => $ativo
    ]);

    setMensagemToast('success', 'Configurações de E-mail Salvas!', 'As definições do servidor SMTP foram atualizadas.');
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/email_config.php');
exit;

