<?php
/**
 * Integração com API Mercado Pago REST v1
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';

class MercadoPagoService {

    /**
     * Cria um pagamento via PIX através da API oficial do Mercado Pago
     */
    public static function criarPagamentoPix($agendamento, $cliente, $empresa) {
        $accessToken = trim($empresa['mp_access_token'] ?? '');
        if (empty($accessToken)) {
            return ['success' => false, 'error' => 'Access Token do Mercado Pago não configurado.'];
        }

        $cpfLimpo = preg_replace('/[^0-9]/', '', (string)$cliente['cpf']);
        if (empty($cpfLimpo) || strlen($cpfLimpo) !== 11) {
            $cpfLimpo = '11122233344'; // Fallback
        }

        $nomePartes = explode(' ', trim($cliente['nome']));
        $firstName = $nomePartes[0];
        $lastName  = count($nomePartes) > 1 ? end($nomePartes) : 'Cliente';

        $webhookUrl = BASE_URL . '/api/mercadopago_webhook.php';

        $payload = [
            'transaction_amount' => (float)$agendamento['valor_total'],
            'description'        => 'Agendamento ' . $agendamento['codigo_reserva'] . ' - ' . ($empresa['nome_barbearia'] ?? 'Corte na Navalha'),
            'payment_method_id'  => 'pix',
            'payer'              => [
                'email'          => $cliente['email'],
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'identification' => [
                    'type'   => 'CPF',
                    'number' => $cpfLimpo
                ]
            ],
            'notification_url'   => $webhookUrl,
            'external_reference' => (string)$agendamento['id']
        ];

        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'X-Idempotency-Key: CN-' . $agendamento['id'] . '-' . time()
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false // Compatibilidade em ambientes locais de teste
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Falha de comunicação: ' . $curlError];
        }

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($data['id'])) {
            $pointOfInteraction = $data['point_of_interaction'] ?? [];
            $transactionData = $pointOfInteraction['transaction_data'] ?? [];
            $qrCode = $transactionData['qr_code'] ?? '';
            $qrCodeBase64 = $transactionData['qr_code_base64'] ?? '';
            $ticketUrl = $transactionData['ticket_url'] ?? '';

            return [
                'success'        => true,
                'payment_id'     => $data['id'],
                'status'         => $data['status'],
                'pix_copia_cola' => $qrCode,
                'qr_code_base64' => $qrCodeBase64,
                'ticket_url'     => $ticketUrl
            ];
        }

        $mensagemErro = $data['message'] ?? 'Erro desconhecido ao gerar pagamento no Mercado Pago.';
        return ['success' => false, 'error' => $mensagemErro, 'data' => $data];
    }

    /**
     * Consulta status de um pagamento pelo ID no Mercado Pago
     */
    public static function consultarPagamento($paymentId, $accessToken) {
        $ch = curl_init('https://api.mercadopago.com/v1/payments/' . urlencode($paymentId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Testa se as credenciais do Mercado Pago são válidas
     */
    public static function testarCredenciais($accessToken) {
        $ch = curl_init('https://api.mercadopago.com/users/me');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success'  => true,
                'nickname' => $data['nickname'] ?? 'Conta Conectada',
                'email'    => $data['email'] ?? ''
            ];
        }

        return ['success' => false, 'error' => 'Access Token inválido ou expirado (HTTP ' . $httpCode . ').'];
    }
}

