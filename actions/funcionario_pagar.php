<?php
/**
 * Realizar Pagamento PIX ao Funcionário
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

$funcionarioId = (int)($_POST['funcionario_id'] ?? 0);
$valor         = (float)($_POST['valor'] ?? 0.0);
$chavePix      = trim($_POST['chave_pix'] ?? '');
$tipoChave     = trim($_POST['tipo_chave'] ?? 'Chave PIX');
$referencia    = trim($_POST['referencia_periodo'] ?? 'Repasse de Comissões');
$observacoes   = trim($_POST['observacoes'] ?? '');
$dono          = getUsuarioLogado();

if ($funcionarioId <= 0 || $valor <= 0 || empty($chavePix)) {
    setMensagemToast('warning', 'Dados Incompletos', 'Informe o funcionário, valor válido e a chave PIX.');
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Buscar funcionário
    $stmtFunc = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmtFunc->execute([':id' => $funcionarioId]);
    $func = $stmtFunc->fetch();

    if (!$func) {
        setMensagemToast('danger', 'Não Encontrado', 'Funcionário não localizado.');
        header('Location: ' . BASE_URL . '/admin/funcionarios.php');
        exit;
    }

    $comprovanteTx = 'PIX-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

    $sql = "
        INSERT INTO pagamentos_funcionarios (
            funcionario_id, dono_id, valor, chave_pix_utilizada,
            tipo_chave, comprovante_transacao, referencia_periodo, observacoes
        ) VALUES (
            :func_id, :dono_id, :valor, :chave,
            :tipo_chave, :tx, :ref, :obs
        )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':func_id'    => $funcionarioId,
        ':dono_id'    => $dono['id'],
        ':valor'      => $valor,
        ':chave'      => $chavePix,
        ':tipo_chave' => $tipoChave,
        ':tx'         => $comprovanteTx,
        ':ref'        => $referencia,
        ':obs'        => $observacoes
    ]);

    setMensagemToast('success', 'Pagamento PIX Realizado!', 'Repasse de R$ ' . number_format($valor, 2, ',', '.') . ' enviado com sucesso para ' . htmlspecialchars($func['nome']) . ' (Chave: ' . htmlspecialchars($chavePix) . '). Comprovante: ' . $comprovanteTx);
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro no Pagamento', 'Falha ao registrar pagamento: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/admin/funcionarios.php');
    exit;
}
