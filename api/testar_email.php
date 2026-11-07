<?php
/**
 * API: Testar Envio de E-mail ao Vivo
 * Apenas Dono (Conforme mods.md)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!usuarioLogado() || getUsuarioLogado()['perfil'] !== 'dono') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$destinatario = trim($_POST['email_teste'] ?? '');
if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Informe um e-mail de teste válido.']);
    exit;
}

$assunto = "Teste de Conexão SMTP - Barbearia Corte na Navalha";
$corpo = "
<div style='font-family: Arial, sans-serif; max-width: 550px; background: #0f1015; color: #f1f5f9; padding: 25px; border-radius: 8px; border: 1px solid #dc2626;'>
    <h3 style='color: #dc2626; margin-top: 0;'>Barbearia Corte na Navalha</h3>
    <h4 style='color: #22c55e;'>Teste de E-mail Realizado com Sucesso!</h4>
    <p>Se você recebeu esta mensagem, o seu servidor de e-mail (Gmail/SMTP Hospedado) está configurado e pronto para disparar comprovantes e notificações aos clientes e barbeiros.</p>
    <p style='color: #94a3b8; font-size: 0.85em;'>Disparado em " . date('d/m/Y H:i:s') . "</p>
</div>
";

$res = BarberMailer::enviar($destinatario, 'Administrador Dono', $assunto, $corpo);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
exit;

