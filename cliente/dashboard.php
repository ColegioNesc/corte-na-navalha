<?php
/**
 * Dashboard do Cliente - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();

// Buscar agendamentos do cliente com dados do barbeiro e avaliação (se houver)
$sql = "
    SELECT a.*, 
           b.nome as barbeiro_nome, b.especialidade as barbeiro_especialidade,
           av.estrelas as avaliacao_estrelas, av.comentario as avaliacao_comentario,
           GROUP_CONCAT(s.nome SEPARATOR ' + ') as procedimentos
    FROM agendamentos a
    JOIN usuarios b ON a.barbeiro_id = b.id
    JOIN agendamento_itens ai ON a.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    LEFT JOIN avaliacoes av ON a.id = av.agendamento_id
    WHERE a.cliente_id = :cliente_id
    GROUP BY a.id
    ORDER BY a.data_agendamento DESC, a.horario DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':cliente_id' => $usuario['id']]);
$agendamentos = $stmt->fetchAll();

// Separar em próximos e anteriores
$hoje = date('Y-m-d');
$proximos = [];
$historico = [];

foreach ($agendamentos as $ag) {
    if ($ag['data_agendamento'] >= $hoje && in_array($ag['status_atendimento'], ['agendado', 'em_atendimento'])) {
        $proximos[] = $ag;
    } else {
        $historico[] = $ag;
    }
}

$paginaTitulo = 'Meus Agendamentos | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho do Perfil -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h4>
                        <p class="text-secondary small mb-0">
                            <i class="bi bi-envelope text-gold me-1"></i> <?= htmlspecialchars($usuario['email']) ?> | 
                            <i class="bi bi-card-text text-gold me-1"></i> CPF: <?= htmlspecialchars($usuario['cpf']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold py-2 px-4">
                    <i class="bi bi-calendar-plus me-1"></i> Novo Agendamento
                </a>
            </div>
        </div>
    </div>

    <!-- Próximos Atendimentos -->
    <div class="mb-5">
        <h4 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-gold"></i> Próximos Atendimentos Confirmados
        </h4>

        <?php if (empty($proximos)): ?>
            <div class="barber-card p-4 text-center">
                <i class="bi bi-calendar2-x text-muted fs-1 mb-2 d-block"></i>
                <h5 class="text-white mb-1">Você não possui nenhum horário marcado no momento.</h5>
                <p class="text-secondary small mb-3">Escolha seu barbeiro e reserve seu corte com antecedência.</p>
                <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-outline-gold">
                    Agendar Agora Meu Corte
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($proximos as $ag): ?>
                    <div class="col-lg-6">
                        <div class="barber-card p-4 h-100 d-flex flex-column justify-content-between border-start border-4 border-warning">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge bg-dark border border-secondary text-gold font-monospace">
                                            <?= htmlspecialchars($ag['codigo_reserva']) ?>
                                        </span>
                                        <h5 class="text-white fw-bold mt-2 mb-1">
                                            <?= htmlspecialchars($ag['procedimentos']) ?>
                                        </h5>
                                        <small class="text-gold">
                                            <i class="bi bi-person-badge me-1"></i> Barbeiro: <?= htmlspecialchars($ag['barbeiro_nome']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-<?= $ag['status_atendimento'] ?> d-block mb-1">
                                            <?= str_replace('_', ' ', $ag['status_atendimento']) ?>
                                        </span>
                                        <span class="badge badge-<?= $ag['status_pagamento'] ?>">
                                            <?= ($ag['status_pagamento'] === 'pago') ? 'PAGO' : 'PAGAMENTO PENDENTE' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block">Data & Horário</small>
                                            <strong class="text-white">
                                                <i class="bi bi-calendar3 me-1 text-gold"></i>
                                                <?= date('d/m/Y', strtotime($ag['data_agendamento'])) ?> às <?= substr($ag['horario'], 0, 5) ?>
                                            </strong>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Valor Total</small>
                                            <strong class="text-gold fs-5">R$ <?= number_format($ag['valor_total'], 2, ',', '.') ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 pt-2 border-top border-secondary border-opacity-25">
                                <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" class="btn btn-outline-gold btn-sm w-100">
                                    <i class="bi bi-receipt me-1"></i> Ver Comprovante
                                </a>
                                <?php if ($ag['status_pagamento'] === 'pendente' && $ag['metodo_pagamento'] === 'pix'): ?>
                                    <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" class="btn btn-gold btn-sm w-100">
                                        <i class="bi bi-qr-code me-1"></i> Pagar PIX
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Histórico de Cortes e Atendimentos Anteriores -->
    <div>
        <h4 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-journal-check text-gold"></i> Histórico de Atendimentos
        </h4>

        <?php if (empty($historico)): ?>
            <div class="barber-card p-4 text-center">
                <p class="text-secondary small mb-0">Nenhum histórico anterior registrado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive barber-card p-0">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead style="background: #111319;">
                        <tr>
                            <th class="ps-4">Código</th>
                            <th>Data/Hora</th>
                            <th>Barbeiro</th>
                            <th>Procedimento(s)</th>
                            <th>Valor</th>
                            <th>Pagamento</th>
                            <th>Status</th>
                            <th>Avaliação</th>
                            <th class="text-end pe-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                            <tr>
                                <td class="ps-4 font-monospace small text-gold"><?= htmlspecialchars($h['codigo_reserva']) ?></td>
                                <td><?= date('d/m/Y', strtotime($h['data_agendamento'])) ?> <?= substr($h['horario'], 0, 5) ?></td>
                                <td><?= htmlspecialchars($h['barbeiro_nome']) ?></td>
                                <td><?= htmlspecialchars($h['procedimentos']) ?></td>
                                <td class="fw-bold">R$ <?= number_format($h['valor_total'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-<?= $h['status_pagamento'] ?>">
                                        <?= strtoupper($h['status_pagamento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $h['status_atendimento'] ?>">
                                        <?= str_replace('_', ' ', $h['status_atendimento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h['status_atendimento'] === 'concluido'): ?>
                                        <?php if (!empty($h['avaliacao_estrelas'])): ?>
                                            <div class="text-warning small" title="<?= htmlspecialchars($h['avaliacao_comentario'] ?? '') ?>">
                                                <?php for ($e = 1; $e <= 5; $e++): ?>
                                                    <i class="bi <?= ($e <= $h['avaliacao_estrelas']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-warning py-0 px-2" onclick="abrirModalAvaliacao(<?= $h['id'] ?>, '<?= htmlspecialchars(addslashes($h['barbeiro_nome'])) ?>')">
                                                <i class="bi bi-star me-1"></i> Avaliar
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($h['codigo_reserva']) ?>" class="btn btn-sm btn-outline-gold">
                                        <i class="bi bi-receipt"></i> Recibo
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Avaliação do Cliente -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-gold">Avaliar Atendimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/feedback_salvar.php" method="POST">
                <input type="hidden" name="agendamento_id" id="modalAvaliacaoAgendamentoId" value="">
                
                <div class="modal-body text-center py-4">
                    <p class="text-secondary mb-3">
                        Como foi sua experiência com o barbeiro <strong id="modalAvaliacaoBarbeiroNome" class="text-white"></strong>?
                    </p>

                    <!-- Seleção de Estrelas -->
                    <div class="star-rating mb-4">
                        <input type="radio" id="star5" name="estrelas" value="5" checked />
                        <label for="star5" title="5 estrelas - Excelente"><i class="bi bi-star-fill"></i></label>
                        
                        <input type="radio" id="star4" name="estrelas" value="4" />
                        <label for="star4" title="4 estrelas - Muito bom"><i class="bi bi-star-fill"></i></label>
                        
                        <input type="radio" id="star3" name="estrelas" value="3" />
                        <label for="star3" title="3 estrelas - Bom"><i class="bi bi-star-fill"></i></label>
                        
                        <input type="radio" id="star2" name="estrelas" value="2" />
                        <label for="star2" title="2 estrelas - Regular"><i class="bi bi-star-fill"></i></label>
                        
                        <input type="radio" id="star1" name="estrelas" value="1" />
                        <label for="star1" title="1 estrela - Ruim"><i class="bi bi-star-fill"></i></label>
                    </div>

                    <div class="text-start">
                        <label for="comentario" class="form-label">Deixe seu depoimento ou sugestão</label>
                        <textarea class="form-control" name="comentario" id="comentario" rows="3" placeholder="Conte como ficou o corte, pontualidade e acabamento..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Depois</button>
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-send me-1"></i> Enviar Avaliação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAvaliacao(id, barbeiroNome) {
    document.getElementById('modalAvaliacaoAgendamentoId').value = id;
    document.getElementById('modalAvaliacaoBarbeiroNome').innerText = barbeiroNome;
    const modal = new bootstrap.Modal(document.getElementById('modalAvaliacao'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
