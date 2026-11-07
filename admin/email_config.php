<?php
/**
 * Configuração de Servidor de E-mail (SMTP / POP / Gmail)
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

exigirLogin(['dono']);

$cfg = BarberMailer::getConfig();

$paginaTitulo = 'Configurações de E-mail & Servidor | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar ao Painel Principal">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-envelope-at-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Central de E-mail & Notificações</h4>
                        <p class="text-secondary small mb-0">Configure contas do Gmail ou SMTP de hospedagem para envio de comprovantes e avisos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?= BASE_URL ?>/admin/email_templates.php" class="btn btn-gold btn-sm py-2 px-3">
                    <i class="bi bi-file-text me-1"></i> Modelos de Mensagem
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Coluna de Configuração -->
        <div class="col-lg-8">
            <div class="barber-card p-4 p-md-5">
                <form action="<?= BASE_URL ?>/actions/email_config_salvar.php" method="POST">
                    
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-3 mb-4">
                        <h5 class="text-white fw-bold mb-0">
                            <i class="bi bi-hdd-network text-danger me-2"></i>Servidor de Saída (SMTP)
                        </h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ativo" id="emailAtivo" value="1" <?= !empty($cfg['ativo']) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white small fw-bold" for="emailAtivo">
                                Habilitar Envio de E-mails
                            </label>
                        </div>
                    </div>

                    <!-- Tipo de Provedor -->
                    <div class="mb-4">
                        <label class="form-label small text-secondary fw-bold text-uppercase">Provedor de E-mail:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="driver" id="driverGmail" value="gmail" <?= ($cfg['driver'] === 'gmail') ? 'checked' : '' ?> onchange="aplicarPreset('gmail')">
                            <label class="btn btn-outline-danger py-2" for="driverGmail">
                                🔴 Google Gmail (POP/SMTP)
                            </label>

                            <input type="radio" class="btn-check" name="driver" id="driverHospedado" value="smtp" <?= ($cfg['driver'] === 'smtp') ? 'checked' : '' ?> onchange="aplicarPreset('smtp')">
                            <label class="btn btn-outline-danger py-2" for="driverHospedado">
                                🌐 E-mail Hospedado (cPanel/Titan)
                            </label>

                            <input type="radio" class="btn-check" name="driver" id="driverMail" value="mail" <?= ($cfg['driver'] === 'mail') ? 'checked' : '' ?> onchange="aplicarPreset('mail')">
                            <label class="btn btn-outline-danger py-2" for="driverMail">
                                ✉️ PHP Mail Nativo
                            </label>
                        </div>
                    </div>

                    <!-- Servidor e Porta -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Host SMTP</label>
                            <input type="text" class="form-control" name="host" id="inputHost" value="<?= htmlspecialchars($cfg['host']) ?>" required placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Porta</label>
                            <input type="number" class="form-control" name="porta" id="inputPorta" value="<?= (int)$cfg['porta'] ?>" required placeholder="587">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Criptografia</label>
                            <select class="form-select" name="seguranca" id="selectSeguranca">
                                <option value="tls" <?= ($cfg['seguranca'] === 'tls') ? 'selected' : '' ?>>TLS (Recomendado)</option>
                                <option value="ssl" <?= ($cfg['seguranca'] === 'ssl') ? 'selected' : '' ?>>SSL (Porta 465)</option>
                                <option value="none" <?= ($cfg['seguranca'] === 'none') ? 'selected' : '' ?>>Nenhuma</option>
                            </select>
                        </div>
                    </div>

                    <!-- Credenciais -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Usuário / E-mail de Login</label>
                            <input type="text" class="form-control" name="usuario" id="inputUsuario" value="<?= htmlspecialchars($cfg['usuario']) ?>" placeholder="suabarbearia@gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha / Senha de App do Google</label>
                            <input type="password" class="form-control" name="senha" placeholder="••••••••••••••••">
                            <small class="text-muted" style="font-size: 0.72rem;">No Gmail, utilize uma 'Senha de App' gerada em Conta Google > Segurança.</small>
                        </div>
                    </div>

                    <!-- Dados do Remetente -->
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-person-lines-fill text-gold me-2"></i>Identificação do Remetente
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome Exibido no Remetente</label>
                            <input type="text" class="form-control" name="remetente_nome" value="<?= htmlspecialchars($cfg['remetente_nome']) ?>" required placeholder="Corte na Navalha Barbearia">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail de Resposta (Reply-To)</label>
                            <input type="email" class="form-control" name="remetente_email" value="<?= htmlspecialchars($cfg['remetente_email']) ?>" required placeholder="contato@cortenanavalha.com.br">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-save me-1"></i> Salvar Configurações de E-mail
                    </button>
                </form>
            </div>
        </div>

        <!-- Coluna de Teste e Instruções -->
        <div class="col-lg-4">
            <!-- Teste de Envio em Tempo Real -->
            <div class="barber-card p-4 mb-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="bi bi-send-check text-gold me-2"></i>Testar Conexão ao Vivo
                </h5>
                <p class="text-secondary small">
                    Envie um e-mail de teste para verificar se o host, a porta e a senha estão funcionando perfeitamente:
                </p>

                <div class="mb-3">
                    <label class="form-label small text-secondary">E-mail de Destino para Teste:</label>
                    <input type="email" class="form-control" id="emailTesteDestino" placeholder="seu.email@gmail.com" value="<?= htmlspecialchars(getUsuarioLogado()['email'] ?? '') ?>">
                </div>

                <button type="button" class="btn btn-outline-danger w-100" onclick="dispararEmailTeste()">
                    <i class="bi bi-play-circle me-1"></i> Disparar Teste de Envio
                </button>

                <div id="statusTesteEmail" class="mt-3 p-3 rounded bg-dark border border-secondary border-opacity-25" style="display: none;">
                </div>
            </div>

            <!-- Guia Rápido para Gmail -->
            <div class="barber-card p-4">
                <h6 class="text-gold fw-bold mb-2">
                    <i class="bi bi-info-circle me-1"></i> Dica de Configuração Gmail:
                </h6>
                <ol class="small text-secondary ps-3 mb-0" style="font-size: 0.8rem; line-height: 1.6;">
                    <li>Acesse sua Conta Google e habilite a <strong>Verificação em 2 etapas</strong>.</li>
                    <li>Vá em <strong>Senhas de app</strong> e crie uma senha chamada "Barbearia".</li>
                    <li>Copie o código de 16 letras gerado e cole no campo de senha acima.</li>
                    <li>A porta padrão é <strong>587 (TLS)</strong> ou <strong>465 (SSL)</strong>.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function aplicarPreset(tipo) {
    const host = document.getElementById('inputHost');
    const porta = document.getElementById('inputPorta');
    const seguranca = document.getElementById('selectSeguranca');

    if (tipo === 'gmail') {
        host.value = 'smtp.gmail.com';
        porta.value = '587';
        seguranca.value = 'tls';
    } else if (tipo === 'smtp') {
        host.value = 'mail.cortenanavalha.com.br';
        porta.value = '465';
        seguranca.value = 'ssl';
    } else if (tipo === 'mail') {
        host.value = 'localhost';
        porta.value = '25';
        seguranca.value = 'none';
    }
}

async function dispararEmailTeste() {
    const dest = document.getElementById('emailTesteDestino').value.trim();
    const box = document.getElementById('statusTesteEmail');

    if (!dest) {
        showToast('warning', 'Atenção', 'Informe um e-mail de destino para testar.');
        return;
    }

    box.style.display = 'block';
    box.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split me-1"></i> Conectando e enviando...</span>';

    try {
        const fd = new FormData();
        fd.append('email_teste', dest);

        const resp = await fetch('<?= BASE_URL ?>/api/testar_email.php', {
            method: 'POST',
            body: fd
        });
        const data = await resp.json();

        if (data.success) {
            box.innerHTML = '<div class="text-success"><i class="bi bi-check-circle-fill me-1"></i> <strong>E-mail enviado com sucesso!</strong> Verifique a caixa de entrada de ' + dest + '.</div>';
            showToast('success', 'E-mail Enviado', 'Teste concluído com sucesso!');
        } else {
            box.innerHTML = '<div class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> <strong>Falha no Envio:</strong><br><small>' + (data.error || 'Erro desconhecido') + '</small></div>';
            showToast('danger', 'Erro no Teste', data.error || 'Falha ao enviar.');
        }
    } catch (e) {
        box.innerHTML = '<div class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Erro de rede ou requisição.</div>';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

