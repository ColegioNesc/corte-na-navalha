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
    <!-- Cabeçalho -->
    <div class="barber-card p-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon" style="width: 60px; height: 60px; font-size: 1.8rem;">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <h4 class="text-white fw-bold mb-1">Configuração da Barbearia & Dados da Empresa</h4>
                <p class="text-secondary small mb-0">Cadastre a empresa, dados de contato e a chave PIX principal para recebimentos dos clientes</p>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="barber-card p-4 p-md-5">
                <form action="<?= BASE_URL ?>/actions/barbearia_salvar.php" method="POST">
                    
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <i class="bi bi-shop text-gold me-2"></i>Identidade do Estabelecimento
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
                                Esta chave é utilizada dinamicamente pelo algoritmo para criar o QR Code Copia e Cola nos agendamentos de todos os clientes.
                            </small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-save me-1"></i> Salvar Dados da Empresa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
