<?php
/**
 * Dashboard do Cliente - Barbearia Corte na Navalha
 * Abas de Navegação, Catálogo Direto e Política de Cancelamento CDC
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['cliente']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

$horasMinimasCancelamento = (float)($empresa['horas_minimas_cancelamento'] ?? 2.0);
$taxaCancelamentoPadrao = (float)($empresa['taxa_cancelamento_percentual'] ?? 20.0);

// Buscar catálogo de serviços da barbearia
$stmtServ = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY categoria ASC, preco ASC");
$servicosCatalogo = $stmtServ->fetchAll();

// Agrupar serviços por categoria
$servicosPorCategoria = [];
foreach ($servicosCatalogo as $srv) {
    $cat = $srv['categoria'] ?? 'Geral';
    $servicosPorCategoria[$cat][] = $srv;
}

// Buscar agendamentos do cliente com dados do barbeiro e avaliação
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

// Separar em próximos e histórico
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

$paginaTitulo = 'Painel do Cliente | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho do Perfil com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar para A Barbearia">
                        <i class="bi bi-arrow-left"></i> A Barbearia
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-0">Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h4>
                        <small class="text-secondary">CPF: <?= htmlspecialchars($usuario['cpf'] ?? '---') ?> • <?= htmlspecialchars($usuario['email']) ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2">
                <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold btn-sm py-2 px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Agendar Novo Horário
                </a>
            </div>
        </div>
    </div>

    <!-- Abas de Navegação Limpas e Responsivas (UX Sem Poluição) -->
    <ul class="nav nav-pills mb-4 nav-fill p-2 rounded-3 bg-dark border border-secondary border-opacity-25" id="painelClienteTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active py-2 text-uppercase fw-semibold small" id="tab-catalogo-btn" data-bs-toggle="pill" data-bs-target="#tab-catalogo" type="button" role="tab" aria-selected="true">
                <i class="bi bi-scissors me-1 text-gold"></i> Catálogo de Serviços
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-proximos-btn" data-bs-toggle="pill" data-bs-target="#tab-proximos" type="button" role="tab" aria-selected="false">
                <i class="bi bi-clock-history me-1 text-danger"></i> Próximos Atendimentos 
                <?php if (count($proximos) > 0): ?>
                    <span class="badge bg-danger ms-1"><?= count($proximos) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-historico-btn" data-bs-toggle="pill" data-bs-target="#tab-historico" type="button" role="tab" aria-selected="false">
                <i class="bi bi-journal-check me-1 text-info"></i> Histórico & Recibos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-perfil-btn" data-bs-toggle="pill" data-bs-target="#tab-perfil" type="button" role="tab" aria-selected="false">
                <i class="bi bi-person-lines-fill me-1 text-warning"></i> Minha Conta
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Abas -->
    <div class="tab-content" id="painelClienteTabsContent">
        
        <!-- ABA 1: Catálogo de Serviços (Ativa por padrão para cair direto no catálogo) -->
        <div class="tab-pane fade show active" id="tab-catalogo" role="tabpanel" aria-labelledby="tab-catalogo-btn">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h4 class="text-white fw-bold mb-1"><i class="bi bi-scissors text-gold me-2"></i>Catálogo & Procedimentos</h4>
                    <p class="text-secondary small mb-0">Selecione o corte ou procedimento desejado para agendar seu horário na cadeira</p>
                </div>
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" id="filtroCatalogoInput" class="form-control form-control-sm" placeholder="Buscar corte, barba, terapia..." oninput="filtrarCatalogoCliente()">
                </div>
            </div>

            <?php if (empty($servicosCatalogo)): ?>
                <div class="barber-card p-5 text-center">
                    <i class="bi bi-scissors text-muted fs-1 mb-2 d-block"></i>
                    <h5 class="text-white">Nenhum serviço disponível no momento.</h5>
                </div>
            <?php else: ?>
                <?php foreach ($servicosPorCategoria as $categoria => $itens): ?>
                    <div class="mb-4 categoria-bloco">
                        <h5 class="text-gold fw-bold text-uppercase border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                            <i class="bi bi-tag-fill me-2"></i><?= htmlspecialchars($categoria) ?>
                        </h5>
                        <div class="row g-3">
                            <?php foreach ($itens as $s): ?>
                                <div class="col-md-6 col-lg-4 item-servico-card">
                                    <div class="barber-card p-3 h-100 d-flex flex-column justify-content-between border border-secondary border-opacity-25 hover-shadow">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="text-white fw-bold mb-0 servico-nome"><?= htmlspecialchars($s['nome']) ?></h6>
                                                <span class="badge bg-dark border border-warning text-gold fw-bold">
                                                    R$ <?= number_format($s['preco'], 2, ',', '.') ?>
                                                </span>
                                            </div>
                                            <p class="text-secondary small mb-2 servico-desc" style="min-height: 38px;">
                                                <?= htmlspecialchars($s['descricao'] ?? 'Procedimento padrão com navalha e acabamento de alta precisão.') ?>
                                            </p>
                                            <small class="text-muted d-block mb-3">
                                                <i class="bi bi-clock me-1 text-gold"></i> Duração estimada: <?= (int)$s['duracao_minutos'] ?> min
                                            </small>
                                        </div>
                                        <a href="<?= BASE_URL ?>/cliente/agendar.php?servico_id=<?= $s['id'] ?>" class="btn btn-gold btn-sm w-100 fw-bold">
                                            <i class="bi bi-calendar-check me-1"></i> Agendar este Serviço
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ABA 2: Próximos Atendimentos Confirmados -->
        <div class="tab-pane fade" id="tab-proximos" role="tabpanel" aria-labelledby="tab-proximos-btn">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="text-white fw-bold mb-1"><i class="bi bi-clock-history text-danger me-2"></i>Horários Marcados</h4>
                    <p class="text-secondary small mb-0">Acompanhe seus horários confirmados, códigos de reserva e comprovantes</p>
                </div>
            </div>

            <?php if (empty($proximos)): ?>
                <div class="barber-card p-5 text-center">
                    <i class="bi bi-calendar2-x text-muted fs-1 mb-3 d-block"></i>
                    <h5 class="text-white mb-2">Você não possui nenhum horário marcado no momento.</h5>
                    <p class="text-secondary small mb-3">Escolha seu procedimento preferido no catálogo e garanta seu horário.</p>
                    <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i> Agendar Agora
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($proximos as $ag): 
                        $dataHoraStr = $ag['data_agendamento'] . ' ' . $ag['horario'];
                        $appTs = strtotime($dataHoraStr);
                        $horasRestantes = ($appTs - time()) / 3600.0;
                    ?>
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

                                <div class="d-flex flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-25">
                                    <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($ag['codigo_reserva']) ?>" class="btn btn-outline-secondary btn-sm flex-fill">
                                        <i class="bi bi-receipt me-1"></i> Recibo
                                    </a>
                                    <?php if ($ag['status_pagamento'] === 'pendente'): ?>
                                        <button type="button" class="btn btn-danger btn-sm flex-fill fw-bold" onclick="abrirModalQrCode(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($usuario['nome'])) ?>', '<?= number_format($ag['valor_total'], 2, ',', '.') ?>', '<?= htmlspecialchars(addslashes($ag['procedimentos'])) ?>', '<?= $ag['codigo_reserva'] ?>')">
                                            <i class="bi bi-qr-code me-1"></i> Pagar PIX
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm flex-fill" onclick='abrirModalCancelar(<?= json_encode($ag) ?>, <?= round($horasRestantes, 1) ?>)'>
                                        <i class="bi bi-x-circle me-1"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ABA 3: Histórico Completo de Atendimentos Anteriores -->
        <div class="tab-pane fade" id="tab-historico" role="tabpanel" aria-labelledby="tab-historico-btn">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="text-white fw-bold mb-1"><i class="bi bi-journal-check text-info me-2"></i>Histórico Contábil & Procedimentos</h4>
                    <p class="text-secondary small mb-0">Todos os seus atendimentos anteriores realizados ou cancelados</p>
                </div>
            </div>

            <?php if (empty($historico)): ?>
                <div class="barber-card p-5 text-center">
                    <p class="text-secondary mb-0">Nenhum atendimento histórico arquivado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive barber-card p-0">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead style="background: #111319;">
                            <tr>
                                <th class="ps-3">Código</th>
                                <th>Data/Hora</th>
                                <th>Barbeiro</th>
                                <th>Procedimento(s)</th>
                                <th>Valor</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th>Avaliação</th>
                                <th class="text-end pe-3">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td class="ps-3 font-monospace small text-gold"><?= htmlspecialchars($h['codigo_reserva']) ?></td>
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
                                        <?php if ($h['status_atendimento'] === 'cancelado' && $h['taxa_cancelamento'] > 0): ?>
                                            <span class="badge bg-danger d-block mt-1" style="font-size: 0.65rem;">
                                                Taxa: R$ <?= number_format($h['taxa_cancelamento'], 2, ',', '.') ?>
                                            </span>
                                        <?php endif; ?>
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
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>/cliente/comprovante.php?codigo=<?= urlencode($h['codigo_reserva']) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Ver Comprovante Oficial">
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

        <!-- ABA 4: Minha Conta & Preferências -->
        <div class="tab-pane fade" id="tab-perfil" role="tabpanel" aria-labelledby="tab-perfil-btn">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="barber-card p-4">
                        <h4 class="text-white fw-bold mb-3"><i class="bi bi-person-circle text-gold me-2"></i>Meus Dados Cadastrais</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-secondary small">Nome Completo</label>
                                <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 text-white fw-bold">
                                    <?= htmlspecialchars($usuario['nome']) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary small">E-mail Cadastrado</label>
                                <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 text-white">
                                    <?= htmlspecialchars($usuario['email']) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary small">CPF</label>
                                <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 text-gold font-monospace">
                                    <?= htmlspecialchars($usuario['cpf'] ?? '---') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary small">Telefone / WhatsApp</label>
                                <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 text-white">
                                    <?= htmlspecialchars($usuario['telefone'] ?? 'Não informado') ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">Para alterar sua senha ou telefone, acerte com a recepção ou edite seu perfil.</span>
                            <a href="<?= BASE_URL ?>/perfil.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Editar Dados
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal de Cancelamento com Cálculo da Taxa CDC -->
<div class="modal fade" id="modalCancelarAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cancelar Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/agendamento_cancelar.php" method="POST">
                <input type="hidden" name="agendamento_id" id="cancelarAgendamentoId" value="">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/cliente/dashboard.php">
                
                <div class="modal-body py-3">
                    <div id="clienteCancEtapa1">
                        <p class="text-white mb-2">
                            Reserva: <strong id="cancelarCodigoReserva" class="text-gold font-monospace"></strong><br>
                            Data/Hora: <span id="cancelarDataHora" class="text-secondary"></span>
                        </p>

                        <!-- Card Informativo CDC Dinâmico -->
                        <div id="cancelarCardCDC" class="card p-3 mb-3 border">
                            <!-- Preenchido via JavaScript -->
                        </div>

                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label small text-secondary">Motivo do cancelamento (opcional)</label>
                            <textarea class="form-control" name="motivo_cancelamento" id="motivo_cancelamento" rows="2" placeholder="Ex: Imprevisto no trabalho, mudança de data..."></textarea>
                        </div>
                    </div>

                    <div id="clienteCancEtapa2" style="display: none;">
                        <div class="alert alert-danger border-danger bg-danger bg-opacity-10 text-white mb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold text-danger mb-2">
                                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                <span>Confirmação de Cancelamento (Etapa 2 de 2)</span>
                            </div>
                            <p class="small mb-2">
                                Tem certeza que deseja cancelar definitivamente o agendamento <strong id="cancelarCodigoReservaConfirma" class="text-gold font-monospace"></strong>?
                            </p>
                            <p class="small text-secondary mb-0">
                                Após confirmado, apenas o proprietário da barbearia poderá reverter este cancelamento caso necessário.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="clienteCancFooter1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Voltar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="avancarEtapaCancelarCliente(true)">
                        <i class="bi bi-arrow-right-circle me-1"></i> Prosseguir para Confirmação
                    </button>
                </div>
                <div class="modal-footer" id="clienteCancFooter2" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="avancarEtapaCancelarCliente(false)">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                        <i class="bi bi-x-circle me-1"></i> Sim, Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
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
                        <textarea class="form-control" name="comentario" id="comentario" rows="3" placeholder="Conte como ficou o corte, acabamento..." required></textarea>
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
const HORAS_MINIMAS_CDC = <?= $horasMinimasCancelamento ?>;
const TAXA_CDC_PADRAO = <?= $taxaCancelamentoPadrao ?>;

function avancarEtapaCancelarCliente(irParaEtapa2) {
    document.getElementById('clienteCancEtapa1').style.display = irParaEtapa2 ? 'none' : 'block';
    document.getElementById('clienteCancFooter1').style.display = irParaEtapa2 ? 'none' : 'flex';
    document.getElementById('clienteCancEtapa2').style.display = irParaEtapa2 ? 'block' : 'none';
    document.getElementById('clienteCancFooter2').style.display = irParaEtapa2 ? 'flex' : 'none';
}

function abrirModalCancelar(agendamento, horasRestantes) {
    avancarEtapaCancelarCliente(false);
    document.getElementById('cancelarAgendamentoId').value = agendamento.id;
    document.getElementById('cancelarCodigoReserva').innerText = agendamento.codigo_reserva;
    document.getElementById('cancelarCodigoReservaConfirma').innerText = '#' + agendamento.codigo_reserva;
    document.getElementById('cancelarDataHora').innerText = agendamento.data_agendamento + ' às ' + (agendamento.horario || '').substr(0, 5);

    const card = document.getElementById('cancelarCardCDC');
    const valorTotal = parseFloat(agendamento.valor_total) || 0;
    const jaPago = (agendamento.status_pagamento === 'pago');

    if (horasRestantes >= HORAS_MINIMAS_CDC) {
        card.className = 'card p-3 mb-3 border border-success bg-dark bg-opacity-75';
        card.innerHTML = `
            <div class="d-flex align-items-center gap-2 text-success fw-bold mb-1">
                <i class="bi bi-check-circle-fill"></i> Cancelamento Gratuito
            </div>
            <p class="small text-secondary mb-0">
                Você está solicitando com mais de <strong>${HORAS_MINIMAS_CDC}h</strong> de antecedência (${horasRestantes}h restantes). 
                ${jaPago ? 'O valor integral de <strong>R$ ' + valorTotal.toFixed(2).replace('.', ',') + '</strong> será reembolsado.' : 'Nenhuma taxa será cobrada.'}
            </p>
        `;
    } else {
        const taxaVal = valorTotal * (TAXA_CDC_PADRAO / 100.0);
        const reembVal = Math.max(0, valorTotal - taxaVal);
        card.className = 'card p-3 mb-3 border border-warning bg-dark bg-opacity-75';
        card.innerHTML = `
            <div class="d-flex align-items-center gap-2 text-warning fw-bold mb-1">
                <i class="bi bi-exclamation-circle-fill"></i> Cancelamento Fora do Prazo Gratuito
            </div>
            <p class="small text-secondary mb-2">
                Faltam <strong>${horasRestantes}h</strong> para o atendimento (prazo gratuito: ${HORAS_MINIMAS_CDC}h).
                Conforme a política da barbearia, incidirá taxa de <strong>${TAXA_CDC_PADRAO}% (R$ ${taxaVal.toFixed(2).replace('.', ',')})</strong> pela reserva do profissional.
            </p>
            ${jaPago ? `
                <div class="p-2 rounded bg-black small border border-secondary border-opacity-25 text-white">
                    Valor a reembolsar: <strong class="text-gold">R$ ${reembVal.toFixed(2).replace('.', ',')}</strong>
                </div>
            ` : ''}
        `;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalCancelarAgendamento'));
    modal.show();
}

function filtrarCatalogoCliente() {
    const termo = (document.getElementById('filtroCatalogoInput').value || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.item-servico-card');
    cards.forEach(card => {
        const nome = card.querySelector('.servico-nome')?.innerText.toLowerCase() || '';
        const desc = card.querySelector('.servico-desc')?.innerText.toLowerCase() || '';
        card.style.display = (nome.includes(termo) || desc.includes(termo)) ? '' : 'none';
    });
}

function abrirModalAvaliacao(id, barbeiroNome) {
    document.getElementById('modalAvaliacaoAgendamentoId').value = id;
    document.getElementById('modalAvaliacaoBarbeiroNome').innerText = barbeiroNome;
    const modal = new bootstrap.Modal(document.getElementById('modalAvaliacao'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
