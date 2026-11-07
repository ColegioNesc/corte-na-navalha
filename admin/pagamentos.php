<?php
/**
 * Gestão de Pagamentos, PIX Fixo & Mercado Pago Gateway
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$empresa = getEmpresaConfig();

$paginaTitulo = 'Gestão de Pagamentos & Gateway | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar ao Painel Principal">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Gestão de Pagamentos & Gateway de Cobrança</h4>
                        <p class="text-secondary small mb-0">
                            Defina o método de cobrança: <strong>PIX Fixo Manual</strong> ou <strong>Mercado Pago Gateway</strong> com baixa automática
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-receipt me-1"></i> Livro Caixa
                </a>
            </div>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/actions/pagamento_config_salvar.php" method="POST">
        <div class="row g-4">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <!-- 1. Método Ativo -->
                <div class="barber-card p-4 mb-4">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-credit-card-2-front text-danger me-2"></i>Método Principal de Recebimento
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="p-3 rounded border <?= ($empresa['metodo_pagamento_ativo'] === 'pix_fixo') ? 'border-danger bg-danger bg-opacity-10' : 'border-secondary border-opacity-25 bg-dark' ?> d-flex align-items-start gap-3 w-100" style="cursor: pointer;">
                                <input type="radio" name="metodo_pagamento_ativo" value="pix_fixo" class="form-check-input mt-1" <?= ($empresa['metodo_pagamento_ativo'] === 'pix_fixo') ? 'checked' : '' ?> onchange="alternarCamposGateway(this.value)">
                                <div>
                                    <strong class="text-white d-block"><i class="bi bi-qr-code text-gold me-1"></i> PIX Fixo (Chave Estática)</strong>
                                    <small class="text-secondary d-block mt-1">O cliente escaneia o QR Code com a chave da barbearia. A conferência e baixa financeira são realizadas manualmente pela recepção.</small>
                                </div>
                            </label>
                        </div>

                        <div class="col-md-6">
                            <label class="p-3 rounded border <?= ($empresa['metodo_pagamento_ativo'] === 'mercadopago') ? 'border-danger bg-danger bg-opacity-10' : 'border-secondary border-opacity-25 bg-dark' ?> d-flex align-items-start gap-3 w-100" style="cursor: pointer;">
                                <input type="radio" name="metodo_pagamento_ativo" value="mercadopago" class="form-check-input mt-1" <?= ($empresa['metodo_pagamento_ativo'] === 'mercadopago') ? 'checked' : '' ?> onchange="alternarCamposGateway(this.value)">
                                <div>
                                    <strong class="text-white d-block"><i class="bi bi-lightning-charge-fill text-gold me-1"></i> Mercado Pago Gateway</strong>
                                    <small class="text-secondary d-block mt-1">Geração de QR Code PIX dinâmico com identificação imediata e baixa automática instantânea via Webhook.</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 2. Configurações do PIX Fixo -->
                <div class="barber-card p-4 mb-4">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-qr-code text-gold me-2"></i>Dados do PIX Fixo da Barbearia
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Chave</label>
                            <select class="form-select" name="tipo_chave_pix">
                                <option value="E-mail" <?= (($empresa['tipo_chave_pix'] ?? '') === 'E-mail') ? 'selected' : '' ?>>E-mail</option>
                                <option value="CNPJ" <?= (($empresa['tipo_chave_pix'] ?? '') === 'CNPJ') ? 'selected' : '' ?>>CNPJ</option>
                                <option value="Telefone" <?= (($empresa['tipo_chave_pix'] ?? '') === 'Telefone') ? 'selected' : '' ?>>Telefone</option>
                                <option value="Aleatória" <?= (($empresa['tipo_chave_pix'] ?? '') === 'Aleatória') ? 'selected' : '' ?>>Chave Aleatória</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Chave PIX Principal</label>
                            <input type="text" class="form-control font-monospace" name="chave_pix_principal" value="<?= htmlspecialchars($empresa['chave_pix_principal'] ?? '') ?>" placeholder="ex: pix@cortenanavalha.com.br">
                        </div>
                    </div>
                </div>

                <!-- 3. Configurações do Mercado Pago Gateway -->
                <div class="barber-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                        <h5 class="text-white fw-bold mb-0">
                            <i class="bi bi-cpu text-danger me-2"></i>Credenciais da API Mercado Pago
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-gold" onclick="testarMercadoPago()">
                            <i class="bi bi-arrow-repeat me-1"></i> Testar Credenciais
                        </button>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">Access Token (Produção ou Teste)</label>
                            <input type="password" class="form-control font-monospace" id="mpAccessToken" name="mp_access_token" value="<?= htmlspecialchars($empresa['mp_access_token'] ?? '') ?>" placeholder="APP_USR-xxxxxxxxxxxxxxxxxxxxxxxx-xxxxxx">
                            <small class="text-muted" style="font-size: 0.75rem;">Obtido no painel de desenvolvedores do Mercado Pago (Suas integrações > Credenciais de produção/teste).</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Public Key</label>
                            <input type="text" class="form-control font-monospace" name="mp_public_key" value="<?= htmlspecialchars($empresa['mp_public_key'] ?? '') ?>" placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Webhook Secret (Opcional)</label>
                            <input type="password" class="form-control font-monospace" name="mp_webhook_secret" value="<?= htmlspecialchars($empresa['mp_webhook_secret'] ?? '') ?>" placeholder="Assinatura secreta de notificação">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="mp_sandbox" id="mpSandbox" value="1" <?= !empty($empresa['mp_sandbox']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white small" for="mpSandbox">
                            Ambiente de Homologação / Sandbox (Modo de Testes do Mercado Pago)
                        </label>
                    </div>

                    <div id="resultadoTesteMp" class="p-3 rounded bg-dark border border-secondary border-opacity-25" style="display: none;">
                    </div>
                </div>

                <!-- 4. Política de Repasse de Comissões -->
                <div class="barber-card p-4 mb-4">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-people-fill text-gold me-2"></i>Política de Repasse de Comissões dos Barbeiros
                    </h5>

                    <p class="text-secondary small mb-3">
                        Conforme a regra do sistema, o dono decide se as comissões são pagas e baixadas manualmente ou se o sistema dá baixa automática quando o pagamento é liquidado pelo Gateway.
                    </p>

                    <div class="form-check form-switch p-3 bg-dark rounded border border-secondary border-opacity-25">
                        <input class="form-check-input ms-0 me-3" type="checkbox" name="comissao_automatica" id="comissaoAutomatica" value="1" <?= !empty($empresa['comissao_automatica']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white fw-bold" for="comissaoAutomatica">
                            Baixa Automática de Comissões via Gateway
                        </label>
                        <small class="text-secondary d-block mt-1">
                            Se ativado, cada pagamento aprovado pelo Mercado Pago gera automaticamente o registro contábil de comissão no percentual cadastrado do barbeiro.
                            Se desativado, o Dono continua fazendo a gestão e os repasses 100% manuais através do painel de equipe.
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold">
                    <i class="bi bi-save me-1"></i> Salvar Todas as Configurações de Pagamento
                </button>
            </div>

            <!-- Coluna Lateral: Guia do Webhook -->
            <div class="col-lg-4">
                <div class="barber-card p-4 sticky-top" style="top: 100px;">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-link-45deg text-danger me-1"></i>URL do Webhook Oficial
                    </h5>
                    <p class="text-secondary small">
                        Copie este endpoint e cadastre no portal de desenvolvedores do Mercado Pago na seção de <strong>Webhooks (Notificações IPN)</strong> para receber as confirmações de PIX em tempo real:
                    </p>

                    <div class="input-group mb-3">
                        <input type="text" id="webhookUrlInput" class="form-control font-monospace small bg-dark text-white border-secondary" readonly value="<?= BASE_URL ?>/api/mercadopago_webhook.php">
                        <button type="button" class="btn btn-outline-danger" onclick="copiarParaClipboard(document.getElementById('webhookUrlInput').value)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 text-secondary small">
                        <strong class="text-gold d-block mb-1"><i class="bi bi-shield-check me-1"></i> Eventos a Selecionar no MP:</strong>
                        <ul class="mb-0 ps-3">
                            <li>Pagamentos (<code>payment</code>)</li>
                            <li>Reclamações / Estornos (<code>chargeback</code>)</li>
                        </ul>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bi bi-receipt me-1"></i> Ver Todas as Transações do Caixa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function alternarCamposGateway(metodo) {
    showToast('info', 'Método Selecionado', 'Método ativo: ' + (metodo === 'mercadopago' ? 'MERCADO PAGO GATEWAY' : 'PIX FIXO MANUAL'));
}

async function testarMercadoPago() {
    const token = document.getElementById('mpAccessToken').value.trim();
    const painel = document.getElementById('resultadoTesteMp');
    painel.style.display = 'block';
    painel.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split me-1"></i> Conectando à API do Mercado Pago...</span>';

    try {
        const formData = new FormData();
        formData.append('token', token);

        const resp = await fetch('<?= BASE_URL ?>/api/testar_mercadopago.php', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            painel.innerHTML = `
                <div class="text-success">
                    <i class="bi bi-check-circle-fill me-1"></i> <strong>Conexão Estabelecida com Sucesso!</strong><br>
                    <small class="text-secondary">Conta Mercado Pago: <strong>${data.nickname}</strong> (${data.email || 'Autenticado'})</small>
                </div>
            `;
            showToast('success', 'Mercado Pago Conectado', 'Credenciais válidas e ativas.');
        } else {
            painel.innerHTML = `
                <div class="text-danger">
                    <i class="bi bi-x-circle-fill me-1"></i> <strong>Falha na Conexão:</strong> ${data.error}
                </div>
            `;
            showToast('danger', 'Erro de Conexão', data.error);
        }
    } catch (e) {
        painel.innerHTML = '<div class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Erro de rede ou requisição.</div>';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

