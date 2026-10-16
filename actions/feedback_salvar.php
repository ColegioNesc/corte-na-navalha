<?php
/**
 * Salvar Avaliação e Feedback do Atendimento
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}

$agendamentoId = (int)($_POST['agendamento_id'] ?? 0);
$estrelas      = (int)($_POST['estrelas'] ?? 5);
$comentario    = trim($_POST['comentario'] ?? '');
$usuario       = getUsuarioLogado();

if ($estrelas < 1 || $estrelas > 5) {
    $estrelas = 5;
}

if (empty($agendamentoId)) {
    setMensagemToast('warning', 'Identificador Inválido', 'Agendamento não especificado.');
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Verificar se agendamento pertence ao cliente e está concluído
    $stmt = $pdo->prepare("SELECT id, barbeiro_id, status_atendimento FROM agendamentos WHERE id = :id AND cliente_id = :cliente_id LIMIT 1");
    $stmt->execute([':id' => $agendamentoId, ':cliente_id' => $usuario['id']]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        setMensagemToast('danger', 'Não Permitido', 'Agendamento inválido ou não pertencente ao seu usuário.');
        header('Location: ' . BASE_URL . '/cliente/dashboard.php');
        exit;
    }

    // Inserir ou atualizar avaliação
    $sqlAvaliacao = "
        INSERT INTO avaliacoes (agendamento_id, cliente_id, barbeiro_id, estrelas, comentario)
        VALUES (:agendamento_id, :cliente_id, :barbeiro_id, :estrelas, :comentario)
        ON DUPLICATE KEY UPDATE estrelas = VALUES(estrelas), comentario = VALUES(comentario)
    ";
    $stmtAv = $pdo->prepare($sqlAvaliacao);
    $stmtAv->execute([
        ':agendamento_id' => $agendamentoId,
        ':cliente_id'     => $usuario['id'],
        ':barbeiro_id'    => $agendamento['barbeiro_id'],
        ':estrelas'       => $estrelas,
        ':comentario'     => $comentario
    ]);

    setMensagemToast('success', 'Obrigado pela Avaliação!', 'Sua opinião é fundamental para aprimorarmos nosso atendimento na navalha!');
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro na Avaliação', 'Não foi possível salvar o feedback: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}
