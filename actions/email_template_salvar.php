<?php
/**
 * Salvar Template de Mensagem de E-mail
 * Apenas Dono (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/email_templates.php');
    exit;
}

$slug     = trim($_POST['slug'] ?? '');
$titulo   = trim($_POST['titulo'] ?? '');
$assunto  = trim($_POST['assunto'] ?? '');
$corpoHtml = trim($_POST['corpo_html'] ?? '');

if (empty($slug) || empty($assunto) || empty($corpoHtml)) {
    setMensagemToast('warning', 'Campos Obrigatórios', 'Assunto e corpo da mensagem não podem ficar vazios.');
    header('Location: ' . BASE_URL . '/admin/email_templates.php?slug=' . urlencode($slug));
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE templates_email 
        SET titulo = :titulo, assunto = :assunto, corpo_html = :corpo
        WHERE slug = :slug
    ");
    $stmt->execute([
        ':titulo'  => $titulo,
        ':assunto' => $assunto,
        ':corpo'   => $corpoHtml,
        ':slug'    => $slug
    ]);

    setMensagemToast('success', 'Template Salvo!', 'O modelo de mensagem foi atualizado com sucesso.');
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar o template: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/admin/email_templates.php?slug=' . urlencode($slug));
exit;

