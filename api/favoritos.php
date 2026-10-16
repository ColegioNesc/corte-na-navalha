<?php
/**
 * Endpoint de Destinos Favoritos (JSON API)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $inputData['action'] ?? $action;

if (!isLoggedIn()) {
    jsonResponse(false, 'Você precisa estar logado para gerenciar favoritos.', [], 401);
}

$usuarioId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'listar':
            $stmt = $pdo->prepare("SELECT id, titulo, endereco, lat, lng, raio_preferido FROM destinos_favoritos WHERE usuario_id = ? ORDER BY titulo ASC");
            $stmt->execute([$usuarioId]);
            $favoritos = $stmt->fetchAll();
            jsonResponse(true, 'Favoritos carregados.', ['favoritos' => $favoritos]);
            break;

        case 'adicionar':
            $titulo = trim($inputData['titulo'] ?? '');
            $endereco = trim($inputData['endereco'] ?? '');
            $lat = filter_var($inputData['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($inputData['lng'] ?? null, FILTER_VALIDATE_FLOAT);
            $raio = (int)($inputData['raio_preferido'] ?? 300);

            if (empty($titulo) || empty($endereco) || $lat === false || $lng === false) {
                jsonResponse(false, 'Todos os campos de endereço e localização são obrigatórios.', [], 400);
            }

            $stmt = $pdo->prepare("INSERT INTO destinos_favoritos (usuario_id, titulo, endereco, lat, lng, raio_preferido) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$usuarioId, $titulo, $endereco, $lat, $lng, $raio]);

            jsonResponse(true, 'Destino salvo nos favoritos com sucesso!', ['id' => $pdo->lastInsertId()], 201);
            break;

        case 'excluir':
            $id = (int)($inputData['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(false, 'ID inválido.', [], 400);
            }
            $stmt = $pdo->prepare("DELETE FROM destinos_favoritos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuarioId]);
            jsonResponse(true, 'Favorito removido com sucesso.');
            break;

        default:
            jsonResponse(false, 'Ação inválida.', [], 400);
    }
} catch (Exception $e) {
    error_log("Erro em api/favoritos.php: " . $e->getMessage());
    jsonResponse(false, 'Erro ao processar favoritos: ' . $e->getMessage(), [], 500);
}
