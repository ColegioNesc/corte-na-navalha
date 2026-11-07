<?php
/**
 * Motor de Envio de E-mails e SMTP Nativo (Gmail & Hospedado)
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';

class BarberMailer {

    /**
     * Retorna a configuração de e-mail ativa
     */
    public static function getConfig() {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT * FROM configuracao_email LIMIT 1");
            $cfg = $stmt->fetch();
            if ($cfg) return $cfg;
        } catch (Exception $e) {}

        return [
            'driver'          => 'gmail',
            'host'            => 'smtp.gmail.com',
            'porta'           => 587,
            'seguranca'       => 'tls',
            'usuario'         => '',
            'senha'           => '',
            'remetente_nome'  => 'Corte na Navalha Barbearia',
            'remetente_email' => 'contato@cortenanavalha.com.br',
            'ativo'           => 0
        ];
    }

    /**
     * Envia um e-mail via conexão SMTP Socket nativa com SSL/TLS
     */
    public static function enviar($paraEmail, $paraNome, $assunto, $corpoHtml) {
        $cfg = self::getConfig();
        if (empty($cfg['ativo'])) {
            return ['success' => false, 'error' => 'Envio de e-mails está desativado nas configurações.'];
        }

        $host = trim($cfg['host']);
        $porta = (int)$cfg['porta'];
        $seguranca = strtolower(trim($cfg['seguranca']));
        $usuario = trim($cfg['usuario']);
        $senha = trim($cfg['senha']);
        $deEmail = trim($cfg['remetente_email']) ?: $usuario;
        $deNome = trim($cfg['remetente_nome']) ?: 'Corte na Navalha';

        // Fallback para mail() nativo se driver for mail ou se não tiver credenciais
        if ($cfg['driver'] === 'mail' || (empty($usuario) && empty($senha))) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($deNome) . "?= <{$deEmail}>\r\n";
            $ok = @mail($paraEmail, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpoHtml, $headers);
            return $ok ? ['success' => true] : ['success' => false, 'error' => 'Falha na função mail() nativa.'];
        }

        // Conexão SMTP via Socket
        $prefixo = ($seguranca === 'ssl' || $porta === 465) ? 'ssl://' : '';
        $timeout = 15;
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($prefixo . $host, $porta, $errno, $errstr, $timeout);
        if (!$socket) {
            return ['success' => false, 'error' => "Falha ao conectar no servidor SMTP ($host:$porta): $errstr ($errno)"];
        }

        stream_set_timeout($socket, $timeout);

        $lerResposta = function() use ($socket) {
            $resposta = '';
            while ($linha = fgets($socket, 515)) {
                $resposta .= $linha;
                if (substr($linha, 3, 1) === ' ') break;
            }
            return $resposta;
        };

        $enviarComando = function($cmd) use ($socket, $lerResposta) {
            fputs($socket, $cmd . "\r\n");
            return $lerResposta();
        };

        $resp = $lerResposta(); // Banner de boas-vindas
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "Resposta inesperada do SMTP: $resp"];
        }

        $enviarComando("EHLO localhost");

        // STARTTLS para porta 587
        if ($seguranca === 'tls' || $porta === 587) {
            $respTls = $enviarComando("STARTTLS");
            if (substr($respTls, 0, 3) === '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'error' => 'Falha ao negociar criptografia TLS com o servidor.'];
                }
                $enviarComando("EHLO localhost");
            }
        }

        // Autenticação
        if (!empty($usuario) && !empty($senha)) {
            $respAuth = $enviarComando("AUTH LOGIN");
            if (substr($respAuth, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'error' => "Servidor recusou AUTH LOGIN: $respAuth"];
            }

            $respUser = $enviarComando(base64_encode($usuario));
            if (substr($respUser, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'error' => "Usuário SMTP recusado: $respUser"];
            }

            $respPass = $enviarComando(base64_encode($senha));
            if (substr($respPass, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'error' => "Senha SMTP recusada: $respPass"];
            }
        }

        // Envelope
        $respMailFrom = $enviarComando("MAIL FROM:<$deEmail>");
        if (substr($respMailFrom, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "MAIL FROM recusado: $respMailFrom"];
        }

        $respRcptTo = $enviarComando("RCPT TO:<$paraEmail>");
        if (substr($respRcptTo, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "Destinatário ($paraEmail) recusado: $respRcptTo"];
        }

        $respData = $enviarComando("DATA");
        if (substr($respData, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'error' => "DATA recusado: $respData"];
        }

        // Montagem do corpo MIME
        $msgId = '<' . md5(uniqid(time())) . '@' . $host . '>';
        $dataCabecalho = date('r');

        $headers = "Date: $dataCabecalho\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($deNome) . "?= <$deEmail>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($paraNome) . "?= <$paraEmail>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n";
        $headers .= "Message-ID: $msgId\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

        $corpoBase64 = chunk_split(base64_encode($corpoHtml));
        $mensagemCompleta = $headers . $corpoBase64 . "\r\n.\r\n";

        fputs($socket, $mensagemCompleta);
        $respFim = $lerResposta();
        $enviarComando("QUIT");
        fclose($socket);

        if (substr($respFim, 0, 3) === '250') {
            return ['success' => true];
        }

        return ['success' => false, 'error' => "Erro ao finalizar envio: $respFim"];
    }

    /**
     * Interpola variáveis dinâmicas num template de e-mail
     */
    public static function interpolarTemplate($slug, $dados) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM templates_email WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $template = $stmt->fetch();

        if (!$template) {
            return null;
        }

        $empresa = getEmpresaConfig();
        $tags = [
            '{NOME_BARBEARIA}'     => $empresa['nome_barbearia'] ?? 'Corte na Navalha Barbearia',
            '{SLOGAN}'             => $empresa['slogan'] ?? 'Tradição e Estilo em Cada Detalhe',
            '{ENDERECO_BARBEARIA}' => $empresa['endereco'] ?? '',
            '{TELEFONE_BARBEARIA}' => $empresa['telefone'] ?? '',
            '{CLIENTE_NOME}'       => $dados['cliente_nome'] ?? 'Cliente',
            '{CODIGO_RESERVA}'     => $dados['codigo_reserva'] ?? '---',
            '{PROCEDIMENTOS}'      => $dados['procedimentos'] ?? 'Procedimento',
            '{BARBEIRO_NOME}'      => $dados['barbeiro_nome'] ?? 'Barbeiro',
            '{DATA}'               => isset($dados['data_agendamento']) ? date('d/m/Y', strtotime($dados['data_agendamento'])) : date('d/m/Y'),
            '{HORARIO}'            => isset($dados['horario']) ? substr($dados['horario'], 0, 5) : date('H:i'),
            '{VALOR_TOTAL}'        => isset($dados['valor_total']) ? number_format((float)$dados['valor_total'], 2, ',', '.') : '0,00',
            '{METODO_PAGAMENTO}'   => $dados['metodo_pagamento'] ?? 'PIX',
            '{LINK_RECUPERACAO}'   => $dados['link_recuperacao'] ?? BASE_URL . '/login.php'
        ];

        $assunto = str_replace(array_keys($tags), array_values($tags), $template['assunto']);
        $corpo   = str_replace(array_keys($tags), array_values($tags), $template['corpo_html']);

        return [
            'assunto' => $assunto,
            'corpo'   => $corpo
        ];
    }
}

/**
 * Helper para envio do comprovante de pagamento por e-mail
 */
function enviarEmailComprovante($agendamento, $valorRecebido, $troco, $metodo, $codigoTransacao) {
    if (empty($agendamento['cliente_email'])) return;

    $metodoFormatado = ($metodo === 'dinheiro') ? 'Dinheiro (Balcão)' : (($metodo === 'mercadopago_pix') ? 'Mercado Pago PIX' : 'PIX Instantâneo');
    
    $dados = [
        'cliente_nome'     => $agendamento['cliente_nome'],
        'codigo_reserva'   => $agendamento['codigo_reserva'],
        'procedimentos'    => $agendamento['procedimentos'] ?? 'Corte / Barba',
        'barbeiro_nome'    => $agendamento['barbeiro_nome'],
        'data_agendamento' => $agendamento['data_agendamento'],
        'horario'          => $agendamento['horario'],
        'valor_total'      => $agendamento['valor_total'],
        'metodo_pagamento' => $metodoFormatado
    ];

    $template = BarberMailer::interpolarTemplate('comprovante_pagamento', $dados);
    if ($template) {
        BarberMailer::enviar($agendamento['cliente_email'], $agendamento['cliente_nome'], $template['assunto'], $template['corpo']);
    }
}

