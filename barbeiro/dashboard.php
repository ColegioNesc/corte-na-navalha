<?php
/**
 * Painel de Atendimento do Barbeiro - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();

// Buscar dados completos do barbeiro (incluindo chave pix e comissão)
$stmtMe = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
$stmtMe->execute([':id' => $usuario['id']]);
$barbeiroInfo = $stmtMe->fetch();

// Atendimentos do dia de hoje
$hoje = date('Y-m-d');
$sqlHoje = "
    SELECT a.*, 
           c.nome as cliente_nome, c.telefone as cliente_telefone, c.email as cliente_email,
           GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos,
           GROUP_CONCAT(s.tempo_minutos SEPARATOR '+') as tempos
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    WHERE a.barbeiro_id = :barbeiro_id AND a.data_agendamento = :hoje
    GROUP BY a.id
    ORDER BY a.horario ASC
";
$stmtHoje = $pdo->prepare($sqlHoje);
$stmtHoje->execute([':barbeiro_id' => $usuario['id'], ':hoje' => $hoje]);
$atendimentosHoje = $stmtHoje->fetchAll();

// Todos os atendimentos futuros (após hoje)
$sqlFuturos = "
    SELECT a.*, 
           c.nome as cliente_nome, c.telefone as cliente_telefone,
           GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
    FROM agendamentos a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    WHERE a.barbeiro_id = :barbeiro_id AND a.data_agendamento > :hoje AND a.status_atendimento = 'agendado'
    GROUP BY a.id
    ORDER BY a.data_agendamento ASC, a.horario ASC
";
$stmtFuturos = $pdo->prepare($sqlFuturos);
$stmtFuturos->execute([':barbeiro_id' => $usuario['id'], ':hoje' => $hoje]);
$atendimentosFuturos = $stmtFuturos->fetchAll();

// Métricas do Barbeiro
$stmtMetricas = $pdo->prepare("
    SELECT 
        COUNT(id) as total_cortes,
        SUM(CASE WHEN status_atendimento = 'concluido' THEN 1 ELSE 0 END) as total_concluidos,
        COALESCE(SUM(CASE WHEN status_atendimento = 'concluido' THEN valor_total ELSE 0 END), 0) as faturamento_gerado
    FROM agendamentos
    WHERE barbeiro_id = :barbeiro_id
");
$stmtMetricas->execute([':barbeiro_id' => $usuario['id']]);
$metricas = $stmtMetricas->fetch();

// Avaliações Recebidas
$stmtAv = $pdo->prepare("
    SELECT av.*, c.nome as cliente_nome, a.codigo_reserva
    FROM avaliacoes av
    JOIN usuarios c ON av.cliente_id = c.id
    JOIN agendamentos a ON av.agendamento_id = a.id
    WHERE av.barbeiro_id = :barbeiro_id
    ORDER BY av.id DESC
    LIMIT 5
");
$stmtAv->execute([':barbeiro_id' => $usuario['id']]);
$minhasAvaliacoes = $stmtAv->fetchAll();

$comissaoPerc = (float)($barbeiroInfo['comissao_percentual'] ?? 50.0);
$minhaComissaoEstimada = ((float)$metricas['faturamento_gerado'] * $comissaoPerc) / 100;

$paginaTitulo = 'Painel do Barbeiro | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Card de Boas-Vindas do Barbeiro -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 65px; height: 65px; font-size: 2rem;">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Mestre <?= htmlspecialchars($usuario['nome']) ?></h4>
                        <p class="text-secondary small mb-0">
                            Especialidade: <strong class="text-gold"><?= htmlspecialchars($barbeiroInfo['especialidade'] ?? 'Barbeiro Profissional') ?></strong> |
                            Sua Chave PIX: <strong class="text-gold"><?= htmlspecialchars($barbeiroInfo['chave_pix'] ?? 'Não informada') ?></strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <span class="badge bg-dark border border-warning text-gold p-2">
                    <i class="bi bi-pie-chart-fill me-1"></i> Sua Comissão Cadastrada: <?= number_format($comissaoPerc, 0) ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- Indicadores Rápidos -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Atendimentos Hoje</small>
                        <h3 class="text-white fw-bold mb-0"><?= count($atendimentosHoje) ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Cortes Realizados</small>
                        <h3 class="text-white fw-bold mb-0"><?= (int)$metricas['total_concluidos'] ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-check2-all"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Sua Comissão Acumulada</small>
                        <h3 class="text-gold fw-bold mb-0">R$ <?= number_format($minhaComissaoEstimada, 2, ',', '.') ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agenda de Hoje -->
    <div class="mb-5">
        <h4 class="text-white fw-bold mb-3 d-flex align-items-center justify-content-between">
            <span><i class="bi bi-clock text-gold me-2"></i>Agenda de Hoje (<?= date('d/m/Y') ?>)</span>
            <span class="badge bg-gold text-dark"><?= count($atendimentosHoje) ?> clientes marcados</span>
        </h4>

        <?php if (empty($atendimentosHoje)): ?>
            <div class="barber-card p-4 text-center">
                <i class="bi bi-calendar-check text-muted fs-1 mb-2 d-block"></i>
                <h5 class="text-white mb-1">Nenhum atendimento agendado para o dia de hoje.</h5>
                <p class="text-secondary small mb-0">Novos agendamentos aparecerão aqui em tempo real.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($atendimentosHoje as $ag): ?>
                    <div class="col-lg-6">
                        <div class="barber-card p-4 h-100 d-flex flex-column justify-content-between border-start border-4 <?= ($ag['status_atendimento'] === 'em_atendimento') ? 'border-primary' : (($ag['status_atendimento'] === 'concluido') ? 'border-success' : 'border-warning') ?>">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-dark border border-secondary text-gold font-monospace"><?= htmlspecialchars($ag['codigo_reserva']) ?></span>
                                        <h5 class="text-white fw-bold mt-2 mb-1">
                                            <i class="bi bi-person me-1 text-gold"></i> <?= htmlspecialchars($ag['cliente_nome']) ?>
                                        </h5>
                                        <small class="text-secondary d-block">
                                            <i class="bi bi-whatsapp text-success me-1"></i> <?= htmlspecialchars($ag['cliente_telefone']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-<?= $ag['status_atendimento'] ?> d-block mb-1">
                                            <?= str_replace('_', ' ', $ag['status_atendimento']) ?>
                                        </span>
                                        <span class="badge badge-<?= $ag['status_pagamento'] ?>">
                                            <?= strtoupper($ag['status_pagamento']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 my-3">
                                    <div class="mb-2">
                                        <strong class="text-white d-block">Corte / Procedimento Escolhido:</strong>
                                        <span class="text-gold fw-semibold"><?= htmlspecialchars($ag['procedimentos']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-secondary">
                                        <span><i class="bi bi-clock me-1 text-gold"></i> Horário: <strong><?= substr($ag['horario'], 0, 5) ?></strong></span>
                                        <span>Valor: <strong class="text-white">R$ <?= number_format($ag['valor_total'], 2, ',', '.') ?></strong></span>
                                    </div>
                                    <?php if (!empty($ag['observacoes'])): ?>
                                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small text-secondary">
                                            <i class="bi bi-chat-left-text me-1 text-gold"></i> Obs: <?= htmlspecialchars($ag['observacoes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Botões de Ação do Barbeiro -->
                            <div class="d-flex flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-25">
                                <?php if ($ag['status_atendimento'] === 'agendado'): ?>
                                    <form action="<?= BASE_URL ?>/actions/agendamento_status.php" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                        <input type="hidden" name="status" value="em_atendimento">
                                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">
                                            <i class="bi bi-play-fill me-1"></i> Iniciar Atendimento
                                        </button>
                                    </form>
                                <?php elseif ($ag['status_atendimento'] === 'em_atendimento'): ?>
                                    <form action="<?= BASE_URL ?>/actions/agendamento_status.php" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                        <input type="hidden" name="status" value="concluido">
                                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                                            <i class="bi bi-check2-circle me-1"></i> Concluir Corte
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Botão de Pedir Feedback no WhatsApp -->
                                <button type="button" class="btn btn-outline-gold btn-sm" onclick="solicitarFeedbackCliente('<?= htmlspecialchars(addslashes($ag['cliente_nome'])) ?>', '<?= preg_replace('/\D/', '', $ag['cliente_telefone']) ?>', '<?= BASE_URL ?>/cliente/dashboard.php')">
                                    <i class="bi bi-star-fill text-warning me-1"></i> Pedir Feedback
                                </button>
                                
                                <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" class="btn btn-outline-secondary btn-sm" target="_blank" title="Ver Comprovante">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Avaliações dos Clientes Recebidas por Este Barbeiro -->
    <div>
        <h4 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-stars text-warning"></i> Avaliações e Elogios dos Clientes
        </h4>

        <?php if (empty($minhasAvaliacoes)): ?>
            <div class="barber-card p-4 text-center">
                <p class="text-secondary small mb-0">Nenhuma avaliação recebida ainda. Ao concluir cortes, envie o link de feedback ao cliente!</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($minhasAvaliacoes as $av): ?>
                    <div class="col-md-6">
                        <div class="barber-card p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-white"><?= htmlspecialchars($av['cliente_nome']) ?></strong>
                                <div class="text-warning small">
                                    <?php for ($e = 1; $e <= 5; $e++): ?>
                                        <i class="bi <?= ($e <= $av['estrelas']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-light fst-italic small mb-1">
                                "<?= htmlspecialchars($av['comentario']) ?>"
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <?= date('d/m/Y H:i', strtotime($av['criado_em'])) ?> - Reserva: <?= htmlspecialchars($av['codigo_reserva']) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function solicitarFeedbackCliente(nome, telefone, link) {
    const textoMensagem = encodeURIComponent(
        `Olá ${nome}! Aqui é da Barbearia Corte na Navalha. Seu corte acabou de ser finalizado! ✂️\n\nPoderia avaliar nosso atendimento no app? Acesse: ${link}\n\nObrigado pela preferência e volte sempre!`
    );
    const linkWhatsApp = `https://wa.me/55${telefone}?text=${textoMensagem}`;
    
    abrirModalConfirmacao(
        'Pedir Avaliação do Cliente',
        `Deseja abrir o WhatsApp para enviar a solicitação de avaliação para <strong>${nome}</strong> (${telefone})?`,
        function() {
            window.open(linkWhatsApp, '_blank');
        },
        'Abrir WhatsApp',
        'btn-success'
    );
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
