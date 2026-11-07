<?php
/**
 * Salvar Regras de Taxa de Cancelamento CDC (Dono)
 * Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$usuario = getUsuarioLogado();
if (!$usuario || $usuario['perfil'] !== 'dono') {
    http_response_code(403);
    setMensagemToast('danger', 'Acesso Negado', 'Apenas o proprietário da barbearia tem autorização para alterar a taxa de cancelamento do CDC.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$taxaPerc = (float)($_POST['taxa_cancelamento_percentual'] ?? 20.0);
$horasMinimas = (int)($_POST['horas_minimas_cancelamento'] ?? 2);
$textoPolitica = trim($_POST['politica_cancelamento_texto'] ?? '');
$redirect = $_POST['redirect'] ?? (BASE_URL . '/admin/dashboard.php');

// O Código de Defesa do Consumidor (CDC art. 51) proíbe taxas abusivas. Limite máximo permitido: 20%
if ($taxaPerc > 20.0) {
    $taxaPerc = 20.0;
}
if ($taxaPerc < 0.0) {
    $taxaPerc = 0.0;
}
if ($horasMinimas < 1) {
    $horasMinimas = 1;
}

try {
    $pdo = getDBConnection();
    
    if ($textoPolitica !== '') {
        $sql = "
            UPDATE configuracao_empresa 
            SET taxa_cancelamento_percentual = :taxa,
                horas_minimas_cancelamento = :horas,
                politica_cancelamento_texto = :texto
            WHERE id = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':taxa'  => $taxaPerc,
            ':horas' => $horasMinimas,
            ':texto' => $textoPolitica
        ]);
    } else {
        $sql = "
            UPDATE configuracao_empresa 
            SET taxa_cancelamento_percentual = :taxa,
                horas_minimas_cancelamento = :horas
            WHERE id = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':taxa'  => $taxaPerc,
            ':horas' => $horasMinimas
        ]);
    }

    setMensagemToast('success', 'Taxa CDC Atualizada!', "A taxa de cancelamento foi configurada para {$taxaPerc}% (antecedência mínima de {$horasMinimas}h).");
    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    setMensagemToast('danger', 'Erro ao Salvar', 'Não foi possível atualizar a política: ' . $e->getMessage());
    header('Location: ' . $redirect);
    exit;
}

