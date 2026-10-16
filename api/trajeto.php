<?php
/**
 * Endpoint de Gerenciamento de Trajetos / Viagens (JSON API)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $inputData['action'] ?? $action;

$usuarioId = isLoggedIn() ? $_SESSION['user_id'] : null;

try {
    switch ($action) {
        case 'iniciar':
            $destinoNome = trim($inputData['destino_nome'] ?? '');
            $destinoLat = filter_var($inputData['destino_lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $destinoLng = filter_var($inputData['destino_lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $raioMetros = (int)($inputData['raio_metros'] ?? 300);
            $tipoAlarme = in_array($inputData['tipo_alarme'] ?? '', ['som', 'vibracao', 'ambos']) ? $inputData['tipo_alarme'] : 'ambos';
            $somAlarme = in_array($inputData['som_alarme'] ?? '', ['sirene', 'beep', 'toque']) ? $inputData['som_alarme'] : 'sirene';

            if (empty($destinoNome) || $destinoLat === false || $destinoLng === false) {
                jsonResponse(false, 'Destino inválido ou coordenadas ausentes.', [], 400);
            }

            if ($raioMetros < 50 || $raioMetros > 10000) {
                jsonResponse(false, 'O raio de alerta deve ser entre 50m e 10.000m.', [], 400);
            }

            // Cancela trajetos anteriores ainda ativos para este usuário, se houver
            if ($usuarioId) {
                $cancelOld = $pdo->prepare("UPDATE viagens SET status = 'cancelado', concluido_em = NOW() WHERE usuario_id = ? AND status IN ('ativo', 'pausado')");
                $cancelOld->execute([$usuarioId]);
            }

            $stmt = $pdo->prepare("
                INSERT INTO viagens (usuario_id, destino_nome, destino_lat, destino_lng, raio_metros, tipo_alarme, som_alarme, status, acionado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', 0)
            ");
            $stmt->execute([$usuarioId, $destinoNome, $destinoLat, $destinoLng, $raioMetros, $tipoAlarme, $somAlarme]);
            $viagemId = (int)$pdo->lastInsertId();

            jsonResponse(true, 'Monitoramento de trajeto iniciado com sucesso!', [
                'viagem_id' => $viagemId,
                'destino_nome' => $destinoNome,
                'destino_lat' => $destinoLat,
                'destino_lng' => $destinoLng,
                'raio_metros' => $raioMetros,
                'tipo_alarme' => $tipoAlarme,
                'som_alarme' => $somAlarme,
                'status' => 'ativo'
            ], 201);
            break;

        case 'pausar':
            $viagemId = (int)($inputData['viagem_id'] ?? 0);
            if ($viagemId > 0) {
                $stmt = $pdo->prepare("UPDATE viagens SET status = 'pausado' WHERE id = ?");
                $stmt->execute([$viagemId]);
            }
            jsonResponse(true, 'Monitoramento do trajeto pausado.');
            break;

        case 'retomar':
            $viagemId = (int)($inputData['viagem_id'] ?? 0);
            if ($viagemId > 0) {
                $stmt = $pdo->prepare("UPDATE viagens SET status = 'ativo' WHERE id = ?");
                $stmt->execute([$viagemId]);
            }
            jsonResponse(true, 'Monitoramento do trajeto retomado.');
            break;

        case 'acionar':
            $viagemId = (int)($inputData['viagem_id'] ?? 0);
            if ($viagemId > 0) {
                $stmt = $pdo->prepare("UPDATE viagens SET acionado = 1 WHERE id = ?");
                $stmt->execute([$viagemId]);
            }
            jsonResponse(true, 'Alarme acionado com sucesso no sistema!');
            break;

        case 'concluir':
            $viagemId = (int)($inputData['viagem_id'] ?? 0);
            if ($viagemId > 0) {
                $stmt = $pdo->prepare("UPDATE viagens SET status = 'concluido', concluido_em = NOW() WHERE id = ?");
                $stmt->execute([$viagemId]);
            }
            jsonResponse(true, 'Alarme desligado e monitoramento finalizado com sucesso!');
            break;

        case 'cancelar':
            $viagemId = (int)($inputData['viagem_id'] ?? 0);
            if ($viagemId > 0) {
                $stmt = $pdo->prepare("UPDATE viagens SET status = 'cancelado', concluido_em = NOW() WHERE id = ?");
                $stmt->execute([$viagemId]);
            }
            jsonResponse(true, 'Trajeto cancelado com sucesso.');
            break;

        case 'calcular':
            $lat1 = filter_var($inputData['user_lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng1 = filter_var($inputData['user_lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $lat2 = filter_var($inputData['dest_lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng2 = filter_var($inputData['dest_lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $raio = (int)($inputData['raio'] ?? 300);

            if ($lat1 === false || $lng1 === false || $lat2 === false || $lng2 === false) {
                jsonResponse(false, 'Coordenadas incompletas para cálculo.', [], 400);
            }

            $distancia = calcularDistanciaHaversine($lat1, $lng1, $lat2, $lng2);
            $dentroDoRaio = ($distancia <= $raio);

            jsonResponse(true, 'Cálculo realizado com sucesso.', [
                'distancia_metros' => $distancia,
                'dentro_do_raio'   => $dentroDoRaio,
                'raio_configurado' => $raio
            ]);
            break;

        case 'historico':
            if (!$usuarioId) {
                jsonResponse(true, 'Usuário não autenticado. Sem histórico salvo.', ['historico' => []]);
            }

            $stmt = $pdo->prepare("
                SELECT id, destino_nome, destino_lat, destino_lng, raio_metros, tipo_alarme, status, acionado, criado_em, concluido_em
                FROM viagens
                WHERE usuario_id = ?
                ORDER BY criado_em DESC
                LIMIT 30
            ");
            $stmt->execute([$usuarioId]);
            $lista = $stmt->fetchAll();

            jsonResponse(true, 'Histórico obtido com sucesso.', ['historico' => $lista]);
            break;

        default:
            jsonResponse(false, 'Ação de trajeto desconhecida.', [], 400);
    }
} catch (Exception $e) {
    error_log("Erro em api/trajeto.php: " . $e->getMessage());
    jsonResponse(false, 'Erro ao processar requisição de trajeto: ' . $e->getMessage(), [], 500);
}
