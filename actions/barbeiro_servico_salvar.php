<?php
/**
 * Salvar Procedimento do Catálogo Individual do Barbeiro
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
    exit;
}

$usuario = getUsuarioLogado();
$barbeiroId = $usuario['id'];
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

// Verificar se tem permissão para catálogo próprio
$stmtMe = $pdo->prepare("SELECT permite_catalogo_proprio FROM usuarios WHERE id = :id LIMIT 1");
$stmtMe->execute([':id' => $barbeiroId]);
$permiteCatalogo = (int)$stmtMe->fetchColumn();

if ($empresa['tipo_catalogo'] !== 'individual' && !$permiteCatalogo) {
    setMensagemToast('danger', 'Acesso Negado', 'O catálogo individual não está liberado pelo proprietário para o seu perfil.');
    header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
    exit;
}

$id            = (int)($_POST['id'] ?? 0);
$nome          = trim($_POST['nome'] ?? '');
$descricao     = trim($_POST['descricao'] ?? '');
$preco         = (float)($_POST['preco'] ?? 0);
$tempoMinutos  = (int)($_POST['tempo_minutos'] ?? 30);
$categoria     = trim($_POST['categoria'] ?? 'cabelo');
$ativo         = isset($_POST['ativo']) ? 1 : 0;

if (empty($nome) || $preco <= 0) {
    setMensagemToast('warning', 'Dados Inválidos', 'Nome e preço válido são obrigatórios.');
    header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE barbeiro_servicos 
            SET nome = :nome, descricao = :descricao, preco = :preco, tempo_minutos = :tempo, categoria = :categoria, ativo = :ativo
            WHERE id = :id AND barbeiro_id = :barbeiro_id
        ");
        $stmt->execute([
            ':nome'         => $nome,
            ':descricao'    => $descricao,
            ':preco'        => $preco,
            ':tempo'        => $tempoMinutos,
            ':categoria'    => $categoria,
            ':ativo'        => $ativo,
            ':id'           => $id,
            ':barbeiro_id'  => $barbeiroId
        ]);
        setMensagemToast('success', 'Procedimento Atualizado', 'Seu serviço personalizado foi salvo com sucesso.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO barbeiro_servicos (barbeiro_id, nome, descricao, preco, tempo_minutos, categoria, ativo)
            VALUES (:barbeiro_id, :nome, :descricao, :preco, :tempo, :categoria, :ativo)
        ");
        $stmt->execute([
            ':barbeiro_id'  => $barbeiroId,
            ':nome'         => $nome,
            ':descricao'    => $descricao,
            ':preco'        => $preco,
            ':tempo'        => $tempoMinutos,
            ':categoria'    => $categoria,
            ':ativo'        => $ativo
        ]);
        setMensagemToast('success', 'Procedimento Adicionado', 'Novo serviço incluído no seu catálogo individual.');
    }
} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível salvar o procedimento: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/barbeiro/meus_servicos.php');
exit;

