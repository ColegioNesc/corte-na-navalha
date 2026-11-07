<?php
/**
 * Configurações da Barbearia - Dados da Empresa
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$paginaTitulo = 'Dados da Barbearia & Chave PIX | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Configuração da Barbearia & Dados da Empresa</h4>
                        <p class="text-secondary small mb-0">Cadastre a empresa, dados de contato, chave PIX e política de cancelamento</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </div>

    <!-- Card de Formulário (Mesmo Alinhamento e Largura do Cabeçalho) -->
    <div class="barber-card p-4 p-md-5 mb-4">
        <form action="<?= BASE_URL ?>/actions/barbearia_salvar.php" method="POST">
            
            <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                <i class="bi bi-shop text-danger me-2"></i>Identidade da Barbearia
            </h5>

            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <label class="form-label">Nome da Barbearia</label>
                    <input type="text" class="form-control" name="nome_barbearia" value="<?= htmlspecialchars($empresa['nome_barbearia'] ?? '') ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">CNPJ</label>
                    <input type="text" class="form-control" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00">
                </div>
                <div class="col-12">
                    <label class="form-label">Slogan Oficial</label>
                    <input type="text" class="form-control" name="slogan" value="<?= htmlspecialchars($empresa['slogan'] ?? '') ?>">
                </div>

                <!-- Paleta de Cores do Salão -->
                <div class="col-12 mt-3">
                    <label class="form-label"><i class="bi bi-palette-fill text-danger me-1"></i>Paleta de Cores do Sistema</label>
                    <div class="row g-2">
                        <?php 
                        $paletasAdmin = getPaletasCores();
                        $corAtualAdmin = $empresa['cor_tema'] ?? 'crimson';
                        foreach ($paletasAdmin as $slugPal => $dadosPal): 
                            $isAtiva = ($corAtualAdmin === $slugPal);
                        ?>
                            <div class="col-6 col-md-4 col-lg-2">
                                <label class="w-100 h-100" style="cursor: pointer;">
                                    <input type="radio" name="cor_tema" value="<?= htmlspecialchars($slugPal) ?>" class="d-none" <?= $isAtiva ? 'checked' : '' ?> onchange="document.querySelectorAll('.card-paleta-admin').forEach(c => c.style.borderColor='rgba(255,255,255,0.1)'); this.nextElementSibling.style.borderColor='<?= $dadosPal['light'] ?>';">
                                    <div class="card-paleta-admin p-2 rounded d-flex align-items-center gap-2 h-100" style="background: #0d0f14; border: 2px solid <?= $isAtiva ? $dadosPal['light'] : 'rgba(255,255,255,0.1)' ?>; transition: all 0.2s ease;">
                                        <span class="rounded-circle flex-shrink-0" style="width: 24px; height: 24px; background: linear-gradient(135deg, <?= $dadosPal['light'] ?>, <?= $dadosPal['dark'] ?>); border: 2px solid rgba(255,255,255,0.4);"></span>
                                        <div class="lh-1">
                                            <strong class="d-block text-white" style="font-size: 0.78rem;"><?= htmlspecialchars($dadosPal['nome']) ?></strong>
                                            <small class="text-secondary" style="font-size: 0.65rem;"><?= htmlspecialchars($dadosPal['descricao']) ?></small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                <i class="bi bi-geo-alt text-gold me-2"></i>Contato & Localização
            </h5>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Telefone / WhatsApp Comercial</label>
                    <input type="text" class="form-control mask-phone" name="telefone" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail Institucional</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Endereço Completo</label>
                    <input type="text" class="form-control" name="endereco" value="<?= htmlspecialchars($empresa['endereco'] ?? '') ?>" required>
                </div>
            </div>

            <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                <i class="bi bi-qr-code text-gold me-2"></i>Chave PIX Principal para Recebimentos
            </h5>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo de Chave PIX</label>
                    <select class="form-select" name="tipo_chave_pix">
                        <option value="E-mail" <?= (($empresa['tipo_chave_pix'] ?? '') === 'E-mail') ? 'selected' : '' ?>>E-mail</option>
                        <option value="CNPJ" <?= (($empresa['tipo_chave_pix'] ?? '') === 'CNPJ') ? 'selected' : '' ?>>CNPJ</option>
                        <option value="Telefone" <?= (($empresa['tipo_chave_pix'] ?? '') === 'Telefone') ? 'selected' : '' ?>>Telefone</option>
                        <option value="Aleatória" <?= (($empresa['tipo_chave_pix'] ?? '') === 'Aleatória') ? 'selected' : '' ?>>Chave Aleatória</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Chave PIX (Gera os QR Codes dos Clientes)</label>
                    <input type="text" class="form-control font-monospace" name="chave_pix_principal" value="<?= htmlspecialchars($empresa['chave_pix_principal'] ?? '') ?>" required>
                    <small class="text-secondary" style="font-size: 0.78rem;">
                        Esta chave é utilizada pelo algoritmo para criar o QR Code Copia e Cola nos agendamentos de todos os clientes.
                    </small>
                </div>
            </div>

            <!-- Seção Política de Cancelamento e Reembolso -->
            <div id="politica_cancelamento" class="pt-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 border-bottom border-secondary border-opacity-25 pb-2 gap-2">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="bi bi-shield-check text-gold me-2"></i>Política de Cancelamento & Reembolso
                    </h5>
                    <span class="badge bg-secondary text-light px-2 py-1 small">
                        Teto máximo: 20%
                    </span>
                </div>
                <p class="text-secondary small mb-3">
                    Defina as regras de cancelamento e prazos para reembolso. As condições são apresentadas com clareza ao cliente no momento do agendamento.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Prazo para Cancelamento Gratuito</label>
                        <div class="input-group">
                            <input type="number" step="1" min="1" max="72" class="form-control" name="horas_minimas_cancelamento" value="<?= (int)($empresa['horas_minimas_cancelamento'] ?? 2) ?>" required>
                            <span class="input-group-text bg-dark border-secondary text-gold">horas antes</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Tip: cancelamentos realizados com antecedência igual ou superior a esta têm 100% de reembolso.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Taxa de Cancelamento Fora do Prazo</label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="0" max="20" class="form-control" name="taxa_cancelamento_percentual" value="<?= (float)($empresa['taxa_cancelamento_percentual'] ?? 20.00) ?>" required>
                            <span class="input-group-text bg-dark border-secondary text-gold">%</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Tip: percentual retido para cobrir custos de reserva de horário (máximo de 20%).</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mensagem Informativa aos Clientes</label>
                        <textarea class="form-control" name="politica_cancelamento_texto" rows="3" placeholder="Informações adicionais exibidas na confirmação de agendamento..."><?= htmlspecialchars($empresa['politica_cancelamento_texto'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
                </div>
            </div>

            <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold">
                <i class="bi bi-save me-1"></i> Salvar Dados da Empresa & Regras de Cancelamento
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
