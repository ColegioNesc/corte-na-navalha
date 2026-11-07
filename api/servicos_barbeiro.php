<?php
/**
 * API: Obter Serviços Disponíveis por Barbeiro
 * Respeita a regra global e individual do Dono (mods.md)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$barbeiroId = (int)($_GET['barbeiro_id'] ?? 0);
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$servicos = [];
$usaIndividual = false;

if ($barbeiroId > 0) {
    // Verificar se a barbearia liberou ou se o barbeiro tem permissão individual
    $stmtB = $pdo->prepare("SELECT permite_catalogo_proprio, nome FROM usuarios WHERE id = :id AND perfil = 'barbeiro' LIMIT 1");
    $stmtB->execute([':id' => $barbeiroId]);
    $barbeiro = $stmtB->fetch();

    if ($barbeiro) {
        $podeCatalogoProprio = ($empresa['tipo_catalogo'] === 'individual') || !empty($barbeiro['permite_catalogo_proprio']);
        
        if ($podeCatalogoProprio) {
            // Verificar se o barbeiro tem serviços cadastrados
            $stmtCustom = $pdo->prepare("SELECT id, nome, descricao, preco, tempo_minutos, categoria FROM barbeiro_servicos WHERE barbeiro_id = :id AND ativo = 1 ORDER BY categoria ASC, preco ASC");
            $stmtCustom->execute([':id' => $barbeiroId]);
            $customServices = $stmtCustom->fetchAll();

            if (!empty($customServices)) {
                $usaIndividual = true;
                foreach ($customServices as $cs) {
                    $servicos[] = [
                        'id_input'      => 'custom_' . $cs['id'],
                        'id_real'       => $cs['id'],
                        'tipo'          => 'individual',
                        'nome'          => $cs['nome'],
                        'descricao'     => $cs['descricao'],
                        'preco'         => (float)$cs['preco'],
                        'tempo_minutos' => (int)$cs['tempo_minutos'],
                        'categoria'     => $cs['categoria']
                    ];
                }
            }
        }
    }
}

// Se não usa catálogo individual, carrega a tabela geral da barbearia
if (!$usaIndividual) {
    $stmtGeral = $pdo->query("SELECT id, nome, descricao, preco, tempo_minutos, categoria FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC");
    $gerais = $stmtGeral->fetchAll();
    foreach ($gerais as $g) {
        $servicos[] = [
            'id_input'      => 'global_' . $g['id'],
            'id_real'       => $g['id'],
            'tipo'          => 'global',
            'nome'          => $g['nome'],
            'descricao'     => $g['descricao'],
            'preco'         => (float)$g['preco'],
            'tempo_minutos' => (int)$g['tempo_minutos'],
            'categoria'     => $g['categoria']
        ];
    }
}

echo json_encode([
    'success'         => true,
    'usa_individual'  => $usaIndividual,
    'total'           => count($servicos),
    'servicos'        => $servicos
], JSON_UNESCAPED_UNICODE);
exit;

