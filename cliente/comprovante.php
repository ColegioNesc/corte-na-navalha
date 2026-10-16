<?php
/**
 * Comprovante Digital do Cliente - Barbearia Corte na Navalha
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

// URL do QR Code (se for pix e houver copia e cola)
$qrCodeUrl = '';
if (!empty($agendamento['pix_copia_cola'])) {
    $qrCodeUrl = gerarUrlQrCode($agendamento['pix_copia_cola'], 250);
}

$paginaTitulo = 'Comprovante Digital | ' . $agendamento['codigo_reserva'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Barra Superior com Ações -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="<?= BASE_URL ?>/cliente/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar aos Agendamentos
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-gold">
                <i class="bi bi-printer me-1"></i> Imprimir Comprovante
            </button>
            <?php if ($agendamento['status_pagamento'] === 'pendente' && $agendamento['metodo_pagamento'] === 'pix'): ?>
                <form action="<?= BASE_URL ?>/actions/agendamento_pagar.php" method="POST" class="d-inline">
                    <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-check2-circle me-1"></i> Simular Baixa do PIX
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comprovante Digital (Visual Recibo / Voucher) -->
    <div class="receipt-card">
        <!-- Cabeçalho -->
        <div class="receipt-header">
            <div class="fs-1 mb-1">✂️</div>
            <h3 class="receipt-title"><?= htmlspecialchars($empresa['nome_barbearia']) ?></h3>
            <p class="text-muted small mb-1"><?= htmlspecialchars($empresa['slogan']) ?></p>
            <small class="text-secondary d-block">CNPJ: <?= htmlspecialchars($empresa['cnpj']) ?></small>
            <small class="text-secondary d-block"><?= htmlspecialchars($empresa['endereco']) ?></small>
            <div class="mt-3">
                <span class="badge bg-dark text-gold border border-warning px-3 py-2 fw-bold" style="letter-spacing: 1px;">
                    COMPROVANTE OFICIAL DE RESERVA
                </span>
            </div>
        </div>

        <!-- Dados Principais -->
        <div class="mb-3">
            <div class="receipt-row">
                <span class="text-muted">Código da Reserva:</span>
                <strong class="text-dark font-monospace"><?= htmlspecialchars($agendamento['codigo_reserva']) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Data & Hora:</span>
                <strong><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?> às <?= substr($agendamento['horario'], 0, 5) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Barbeiro Especialista:</span>
                <strong><?= htmlspecialchars($agendamento['barbeiro_nome']) ?></strong>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Cliente:</span>
                <span><?= htmlspecialchars($agendamento['cliente_nome']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-muted">CPF do Cliente:</span>
                <span><?= htmlspecialchars($agendamento['cliente_cpf']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-muted">Forma de Pagamento:</span>
                <span class="text-capitalize fw-semibold">
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

        <!-- Tabela de Itens -->
        <div class="my-4">
            <h6 class="text-dark fw-bold border-bottom pb-2 mb-2 text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                Procedimentos Contratados
            </h6>
            <?php foreach ($itens as $item): ?>
                <div class="receipt-row">
                    <div>
                        <strong><?= htmlspecialchars($item['servico_nome']) ?></strong>
                        <small class="text-muted d-block"><?= $item['tempo_minutos'] ?> min</small>
                    </div>
                    <strong class="text-dark">R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?></strong>
                </div>
            <?php endforeach; ?>

            <div class="receipt-total">
                <span>VALOR TOTAL</span>
                <span class="text-gold" style="color: #967636 !important;">R$ <?= number_format($agendamento['valor_total'], 2, ',', '.') ?></span>
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
            <div class="p-3 rounded border border-warning bg-warning bg-opacity-10 my-3 text-center">
                <span class="badge bg-warning text-dark text-uppercase px-3 py-1 mb-2">Aguardando Pagamento</span>
                
                <?php if ($agendamento['metodo_pagamento'] === 'pix' && !empty($qrCodeUrl)): ?>
                    <h6 class="fw-bold text-dark mt-2 mb-1">Escaneie o QR Code abaixo para pagar via PIX</h6>
                    <p class="text-muted small mb-3">Abra o app do seu banco e aponte a câmera</p>
                    
                    <div class="p-2 bg-white d-inline-block rounded shadow-sm border mb-3">
                        <img src="<?= $qrCodeUrl ?>" alt="QR Code PIX" style="width: 200px; height: 200px; display: block;">
                    </div>

                    <div class="input-group mb-2 no-print">
                        <input type="text" id="pixCopiaColaInput" class="form-control form-control-sm text-dark bg-light font-monospace" value="<?= htmlspecialchars($agendamento['pix_copia_cola']) ?>" readonly>
                        <button class="btn btn-dark btn-sm" type="button" onclick="copiarParaClipboard(document.getElementById('pixCopiaColaInput').value)">
                            <i class="bi bi-clipboard"></i> Copiar Código
                        </button>
                    </div>
                    <small class="text-muted d-block">Após realizar o pagamento, a confirmação é registrada no sistema.</small>
                <?php else: ?>
                    <h6 class="fw-bold text-dark mt-2 mb-1">Pagamento no Balcão</h6>
                    <p class="text-secondary small mb-0">
                        O valor de <strong>R$ <?= number_format($agendamento['valor_total'], 2, ',', '.') ?></strong> poderá ser pago na recepção no momento da sua chegada à barbearia via cartão, dinheiro ou PIX.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Rodapé do Comprovante -->
        <div class="receipt-header border-0 pb-0 mb-0 pt-3">
            <small class="text-muted d-block">Apresente este código ou comprovante digital ao comparecer.</small>
            <small class="text-secondary">Obrigado pela preferência! Corte na Navalha Barbearia.</small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
