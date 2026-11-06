<?php
/**
 * Comprovante Digital do Cliente - Barbearia Corte na Navalha
 * Inclui: QR Code Dinâmico com Escuta de Pagamento, Código Copia e Cola Descrito
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente', 'barbeiro', 'recepcao', 'dono']);

$codigo = trim($_GET['codigo'] ?? '');
if (empty($codigo)) {
    setMensagemToast('warning', 'Agendamento Não Encontrado', 'Informe o código do comprovante.');
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}

$pdo = getDBConnection();
$usuario = getUsuarioLogado();
$empresa = getEmpresaConfig();

// Buscar agendamento e detalhes
$sql = "
    SELECT a.*, 
           c.nome as cliente_nome, c.email as cliente_email, c.cpf as cliente_cpf, c.telefone as cliente_telefone,
           b.nome as barbeiro_nome, b.especialidade as barbeiro_especialidade
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN usuarios b ON a.barbeiro_id = b.id
    WHERE a.codigo_reserva = :codigo
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':codigo' => $codigo]);
$agendamento = $stmt->fetch();

if (!$agendamento) {
    setMensagemToast('danger', 'Não Encontrado', 'Não foi localizado nenhum agendamento com este código.');
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}

// Se o usuário for cliente, garantir que só vê o próprio agendamento
if ($usuario['perfil'] === 'cliente' && $agendamento['cliente_id'] != $usuario['id']) {
    setMensagemToast('danger', 'Acesso Negado', 'Você só pode visualizar comprovantes de seus próprios agendamentos.');
    header('Location: ' . BASE_URL . '/cliente/dashboard.php');
    exit;
}

// Buscar itens do agendamento
$stmtItens = $pdo->prepare("
    SELECT ai.*, s.nome as servico_nome, s.tempo_minutos, s.categoria
    FROM agendamento_itens ai
    JOIN servicos s ON ai.servico_id = s.id
    WHERE ai.agendamento_id = :id
");
$stmtItens->execute([':id' => $agendamento['id']]);
$itens = $stmtItens->fetchAll();

// Garantir QR Code dinâmico para pagamento
$pixData = obterOuGerarPixAgendamento($agendamento['id'], $pdo);
$qrCodeUrl = $pixData['qr_code_url'] ?? '';
$pixCopiaCola = $pixData['pix_copia_cola'] ?? '';

$paginaTitulo = 'Comprovante Digital | ' . $agendamento['codigo_reserva'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Barra Superior com Ações -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
        <?php if ($usuario['perfil'] === 'cliente'): ?>
            <a href="<?= BASE_URL ?>/cliente/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar aos Agendamentos
            </a>
        <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
            <a href="<?= BASE_URL ?>/barbeiro/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel do Barbeiro
            </a>
        <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
            <a href="<?= BASE_URL ?>/recepcao/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar à Recepção
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar à Gestão
            </a>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-danger">
                <i class="bi bi-printer me-1"></i> Imprimir Recibo
            </button>
            <?php if ($agendamento['status_pagamento'] === 'pendente'): ?>
                <form action="<?= BASE_URL ?>/actions/agendamento_pagar.php" method="POST" class="d-inline">
                    <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit" class="btn btn-red fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Confirmar Baixa do Pagamento
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comprovante Digital (Visual Recibo / Voucher) -->
    <div class="receipt-card">
        <!-- Cabeçalho com Emblema da Barbearia -->
        <div class="receipt-header">
            <div class="mb-2 d-inline-block">
                <?php 
                    $logoTamanho = 'large';
                    $mostrarTexto = false;
                    require __DIR__ . '/../includes/logo.php'; 
                ?>
            </div>
            <h3 class="receipt-title text-danger"><?= htmlspecialchars($empresa['nome_barbearia']) ?></h3>
            <p class="text-muted small mb-1"><?= htmlspecialchars($empresa['slogan']) ?></p>
            <small class="text-secondary d-block">CNPJ: <?= htmlspecialchars($empresa['cnpj']) ?></small>
            <small class="text-secondary d-block"><?= htmlspecialchars($empresa['endereco']) ?></small>
            <div class="mt-3">
                <span class="badge bg-danger text-white px-3 py-2 fw-bold text-uppercase" style="letter-spacing: 1px;">
                    COMPROVANTE OFICIAL DA BARBEARIA
                </span>
            </div>
        </div>

        <!-- Dados Principais -->
        <div class="mb-3">
            <div class="receipt-row">
                <span class="text-muted">Código da Reserva:</span>
                <strong class="text-danger font-monospace fs-6"><?= htmlspecialchars($agendamento['codigo_reserva']) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Data & Hora:</span>
                <strong><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?> às <?= substr($agendamento['horario'], 0, 5) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Profissional Barbeiro:</span>
                <strong class="text-danger"><?= htmlspecialchars($agendamento['barbeiro_nome']) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Cliente:</span>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($agendamento['cliente_nome']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-muted">CPF do Cliente:</span>
                <span><?= htmlspecialchars($agendamento['cliente_cpf']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Forma de Pagamento:</span>
                <span class="text-capitalize fw-semibold text-danger">
                    <?= ($agendamento['metodo_pagamento'] === 'pix') ? 'PIX Instantâneo' : 'Pagar na Recepção' ?>
                </span>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Status do Atendimento:</span>
                <span class="badge badge-<?= $agendamento['status_atendimento'] ?> text-uppercase">
                    <?= str_replace('_', ' ', $agendamento['status_atendimento']) ?>
                </span>
            </div>
        </div>

        <!-- Tabela de Procedimentos Contratados -->
        <div class="my-4">
            <h6 class="text-danger fw-bold border-bottom border-danger pb-2 mb-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                <i class="bi bi-scissors me-1"></i> Procedimentos Realizados
            </h6>
            <?php foreach ($itens as $item): ?>
                <div class="receipt-row">
                    <div>
                        <strong class="text-dark"><?= htmlspecialchars($item['servico_nome']) ?></strong>
                        <small class="text-muted d-block"><?= $item['tempo_minutos'] ?> min</small>
                    </div>
                    <strong class="text-dark">R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?></strong>
                </div>
            <?php endforeach; ?>

            <div class="receipt-total">
                <span>VALOR TOTAL</span>
                <span class="text-danger">R$ <?= number_format($agendamento['valor_total'], 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Seção de Pagamento: PAGO ou PENDENTE -->
        <?php if ($agendamento['status_pagamento'] === 'pago'): ?>
            <div class="text-center p-3 rounded bg-success bg-opacity-10 border border-success my-3">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <h5 class="text-success fw-bold mt-1 mb-0">PAGAMENTO CONFIRMADO</h5>
                <small class="text-muted">
                    Quitado em <?= $agendamento['pix_pago_em'] ? date('d/m/Y H:i', strtotime($agendamento['pix_pago_em'])) : date('d/m/Y H:i') ?>
                </small>
            </div>
        <?php else: ?>
            <div class="p-3 rounded border border-danger bg-danger bg-opacity-10 my-3 text-center">
                <span class="badge bg-danger text-white text-uppercase px-3 py-1 mb-2">Aguardando Pagamento</span>
                
                <h6 class="fw-bold text-dark mt-2 mb-1">Escaneie o QR Code abaixo para pagar via PIX</h6>
                <p class="text-muted small mb-3">Abra o app do seu banco, escolha a opção PIX e aponte a câmera</p>
                
                <!-- Imagem do QR Code -->
                <div class="p-2 bg-white d-inline-block rounded shadow-sm border border-danger mb-3">
                    <img src="<?= $qrCodeUrl ?>" alt="QR Code PIX Barbearia" style="width: 200px; height: 200px; display: block;">
                </div>

                <!-- Campo PIX Copia e Cola Claramente Identificado e Descrito -->
                <div class="text-start mb-2 no-print">
                    <label for="pixCopiaColaInput" class="form-label text-dark fw-bold small mb-1 d-flex align-items-center gap-1">
                        <i class="bi bi-qr-code text-danger fs-5"></i>
                        <span>Código PIX Copia e Cola Oficial (Clique no botão para copiar):</span>
                    </label>
                    <div class="input-group">
                        <input type="text" id="pixCopiaColaInput" class="form-control text-dark bg-white font-monospace border-secondary" value="<?= htmlspecialchars($pixCopiaCola) ?>" readonly placeholder="Código PIX dinâmico para pagamento" aria-label="Código PIX Copia e Cola">
                        <button class="btn btn-danger fw-bold" type="button" id="btnCopiarPixComprovante" onclick="copiarPixComprovante()">
                            <i class="bi bi-copy me-1"></i> Copiar Código PIX
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i> Este campo exibe o código gerado pelo sistema. Clique no botão vermelho para copiar e colar no app do seu banco.
                    </small>
                </div>

                <!-- Botão Clicável de Tela Cheia com Escuta Automática -->
                <div class="no-print mt-3">
                    <button type="button" class="btn btn-danger btn-sm w-100 fw-bold py-2" onclick="abrirModalQrCode(<?= $agendamento['id'] ?>, '<?= htmlspecialchars(addslashes($agendamento['cliente_nome'])) ?>', '<?= number_format($agendamento['valor_total'], 2, ',', '.') ?>', '<?= htmlspecialchars(addslashes($empresa['nome_barbearia'])) ?>', '<?= $agendamento['codigo_reserva'] ?>')">
                        <i class="bi bi-qr-code-scan me-1"></i> Abrir QR Code em Tela Cheia & Escutar Pagamento
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rodapé do Comprovante -->
        <div class="receipt-header border-0 pb-0 mb-0 pt-3">
            <small class="text-muted d-block">Apresente este código ou comprovante digital ao comparecer.</small>
            <small class="text-secondary">Obrigado pela preferência! Corte na Navalha Barbearia.</small>
        </div>
    </div>
</div>

<script>
function copiarPixComprovante() {
    const input = document.getElementById('pixCopiaColaInput');
    if (!input || !input.value) return;
    copiarParaClipboard(input.value, 'Código PIX copiado com sucesso! Abra o app do seu banco e selecione Pix Copia e Cola.');
    
    const btn = document.getElementById('btnCopiarPixComprovante');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Código Copiado!';
    btn.classList.replace('btn-danger', 'btn-success');
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.replace('btn-success', 'btn-danger');
    }, 2500);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
