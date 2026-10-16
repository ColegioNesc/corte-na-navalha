<?php
/**
 * Salvar / Atualizar Serviço ou Procedimento
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/servicos.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$nome      = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$preco     = (float)($_POST['preco'] ?? 0.0);
$tempo     = (int)($_POST['tempo_minutos'] ?? 30);
$categoria = trim($_POST['categoria'] ?? 'cabelo');
$icone     = trim($_POST['icone'] ?? 'bi-scissors');
$destaque  = isset($_POST['destaque']) ? 1 : 0;
$ativo     = isset($_POST['ativo']) ? 1 : 0;

if (empty($nome) || $preco <= 0) {
    setMensagemToast('warning', 'Campos Inválidos', 'Nome e preço do serviço são obrigatórios.');
    header('Location: ' . BASE_URL . '/admin/servicos.php');
    exit;
}

try {
    $pdo = getDBConnection();

    if ($id > 0) {
        $sql = "
            UPDATE servicos 
            SET nome = :nome, descricao = :descricao, preco = :preco,
                tempo_minutos = :tempo, categoria = :categoria, icone = :icone,
                destaque = :destaque, ativo = :ativo
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'      => $nome,
            ':descricao' => $descricao,
            ':preco'     => $preco,
            ':tempo'     => $tempo,
            ':categoria' => $categoria,
            ':icone'     => $icone,
            ':destaque'  => $destaque,
            ':ativo'     => $ativo,
            ':id'        => $id
        ]);
        setMensagemToast('success', 'Procedimento Atualizado!', 'O serviço foi atualizado na tabela de preços.');
    } else {
        $sql = "
            INSERT INTO servicos (nome, descricao, preco, tempo_minutos, categoria, icone, destaque, ativo)
            VALUES (:nome, :descricao, :preco, :tempo, :categoria, :icone, :destaque, 1)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'      => $nome,
            ':descricao' => $descricao,
            ':preco'     => $preco,
            ':tempo'     => $tempo,
            ':categoria' => $categoria,
            ':icone'     => $icone,
            ':destaque'  => $destaque
        ]);
        setMensagemToast('success', 'Serviço Cadastrado!', 'Novo procedimento adicionado com sucesso à tabela de preços.');
    }

    header('Location: ' . BASE_URL . '/admin/servicos.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar o procedimento: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/admin/servicos.php');
    exit;
}
