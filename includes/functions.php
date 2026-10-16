<?php
/**
 * Funções utilitárias do sistema
 */

/**
 * Calcula a distância em metros entre dois pontos geográficos (Fórmula de Haversine)
 * 
 * @param float $lat1 Latitude de origem
 * @param float $lon1 Longitude de origem
 * @param float $lat2 Latitude de destino
 * @param float $lon2 Longitude de destino
 * @return float Distância em metros
 */
function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $raioTerraMetros = 6371000; // Raio médio da Terra em metros

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($raioTerraMetros * $c, 2);
}

/**
 * Sanitiza strings para exibição segura em HTML
 */
function escape(string $valor): string {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna uma resposta JSON padronizada para endpoints de API
 */
function jsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
