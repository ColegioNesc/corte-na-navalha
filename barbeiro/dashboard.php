<?php
/**
 * Painel de Atendimento do Barbeiro - Barbearia Corte na Navalha
 * Abas de Navegação, Faturamento, Meus Serviços e Gestão de Cadeira
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['barbeiro']);

$usuario = getUsuarioLogado();
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

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

// Procedimentos próprios cadastrados pelo barbeiro (se permitido)
$stmtMeusServicos = $pdo->prepare("SELECT * FROM barbeiro_servicos WHERE barbeiro_id = :id AND ativo = 1 ORDER BY nome ASC");
$stmtMeusServicos->execute([':id' => $usuario['id']]);
$meusServicosCadastrados = $stmtMeusServicos->fetchAll();

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
    LIMIT 10
");
$stmtAv->execute([':barbeiro_id' => $usuario['id']]);
$minhasAvaliacoes = $stmtAv->fetchAll();

$comissaoPerc = (float)($barbeiroInfo['comissao_percentual'] ?? 50.0);
$minhaComissaoEstimada = ((float)$metricas['faturamento_gerado'] * $comissaoPerc) / 100;

$paginaTitulo = 'Painel do Barbeiro | Corte na Navalha';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Card de Boas-Vindas com Botão Voltar -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar para A Barbearia">
                        <i class="bi bi-arrow-left"></i> A Barbearia
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Mestre <?= htmlspecialchars($usuario['nome']) ?></h4>
                        <p class="text-secondary small mb-0">
                            Especialidade: <strong class="text-gold"><?= htmlspecialchars($barbeiroInfo['especialidade'] ?? 'Barbeiro Profissional') ?></strong> |
                            Chave PIX: <strong class="text-gold"><?= htmlspecialchars($barbeiroInfo['chave_pix'] ?? 'Não informada') ?></strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                <span class="badge bg-dark border border-warning text-gold p-2">
                    <i class="bi bi-pie-chart-fill me-1"></i> Comissão: <?= number_format($comissaoPerc, 0) ?>%
                </span>
                <a href="<?= BASE_URL ?>/barbeiro/meus_servicos.php" class="btn btn-sm btn-gold">
                    <i class="bi bi-tag me-1"></i> Gerenciar Meus Preços
                </a>
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
                        <small class="text-muted d-block text-uppercase fw-semibold">Cortes Concluídos</small>
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
                        <small class="text-muted d-block text-uppercase fw-semibold">Comissão Acumulada</small>
                        <h3 class="text-gold fw-bold mb-0">R$ <?= number_format($minhaComissaoEstimada, 2, ',', '.') ?></h3>
                    </div>
                    <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas de Navegação do Barbeiro (UX Sem Poluição) -->
    <ul class="nav nav-pills mb-4 nav-fill p-2 rounded-3 bg-dark border border-secondary border-opacity-25" id="barbeiroTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active py-2 text-uppercase fw-semibold small" id="tab-hoje-btn" data-bs-toggle="pill" data-bs-target="#tab-hoje" type="button" role="tab" aria-selected="true">
                <i class="bi bi-clock me-1 text-danger"></i> Agenda de Hoje (<?= count($atendimentosHoje) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-futuros-btn" data-bs-toggle="pill" data-bs-target="#tab-futuros" type="button" role="tab" aria-selected="false">
                <i class="bi bi-calendar3 me-1 text-gold"></i> Próximos Atendimentos (<?= count($atendimentosFuturos) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-servicos-btn" data-bs-toggle="pill" data-bs-target="#tab-servicos" type="button" role="tab" aria-selected="false">
                <i class="bi bi-scissors me-1 text-info"></i> Meus Procedimentos & Preços
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link py-2 text-uppercase fw-semibold small" id="tab-avaliacoes-btn" data-bs-toggle="pill" data-bs-target="#tab-avaliacoes" type="button" role="tab" aria-selected="false">
                <i class="bi bi-star me-1 text-warning"></i> Avaliações dos Clientes
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Abas -->
    <div class="tab-content" id="barbeiroTabsContent">
        
        <!-- ABA 1: Agenda de Hoje -->
        <div class="tab-pane fade show active" id="tab-hoje" role="tabpanel" aria-labelledby="tab-hoje-btn">
            <!-- Barra de Pesquisa de Atendimentos -->
            <div class="barber-card p-3 mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-danger"><i class="bi bi-search"></i></span>
                    <input type="text" id="filtroAtendimentosBarbeiro" class="form-control form-control-sm" placeholder="Buscar por cliente, telefone, corte ou código de reserva..." oninput="filtrarAgendaBarbeiro()">
                </div>
            </div>

            <?php if (empty($atendimentosHoje)): ?>
                <div class="barber-card p-5 text-center">
                    <i class="bi bi-calendar-check text-muted fs-1 mb-2 d-block"></i>
                    <h5 class="text-white mb-1">Nenhum atendimento agendado para o dia de hoje.</h5>
                    <p class="text-secondary small mb-0">Novos agendamentos aparecerão aqui em tempo real.</p>
                </div>
            <?php else: ?>
                <div class="row g-3" id="containerCardsHoje">
                    <?php foreach ($atendimentosHoje as $ag): ?>
                        <div class="col-lg-6 item-card-barbeiro">
                            <div class="barber-card p-4 h-100 d-flex flex-column justify-content-between border-start border-4 <?= ($ag['status_atendimento'] === 'em_atendimento') ? 'border-danger' : (($ag['status_atendimento'] === 'concluido') ? 'border-success' : 'border-warning') ?>">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-dark border border-secondary text-gold font-monospace"><?= htmlspecialchars($ag['codigo_reserva']) ?></span>
                                            <?php 
                                                $ehBalcao = ($ag['origem'] === 'balcao' || !empty($ag['cliente_balcao']));
                                                $nomeBarbeiroCli = !empty($ag['cliente_nome_avulso']) 
                                                    ? $ag['cliente_nome_avulso'] 
                                                    : ($ehBalcao ? 'Cliente Balcão (Não Identificado)' : $ag['cliente_nome']);
                                            ?>
                                            <h5 class="text-white fw-bold mt-2 mb-1">
                                                <i class="bi bi-person me-1 text-gold"></i> <?= htmlspecialchars($nomeBarbeiroCli) ?>
                                                <?php if ($ehBalcao): ?>
                                                    <span class="badge bg-secondary text-white ms-1" style="font-size: 0.65rem;">Balcão</span>
                                                <?php endif; ?>
                                            </h5>
                                            <?php if (!$ehBalcao && !empty($ag['cliente_telefone'])): ?>
                                                <small class="text-secondary d-block">
                                                    <i class="bi bi-whatsapp text-success me-1"></i> <?= htmlspecialchars($ag['cliente_telefone']) ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">Atendimento no Balcão</small>
                                            <?php endif; ?>
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
                                            <strong class="text-white d-block">Corte / Procedimento:</strong>
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

                                <!-- Ações do Barbeiro -->
                                <div class="d-flex flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-25">
                                    <?php if ($ag['status_atendimento'] === 'agendado'): ?>
                                        <form action="<?= BASE_URL ?>/actions/agendamento_status.php" method="POST" class="flex-grow-1">
                                            <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                            <input type="hidden" name="status" value="em_atendimento">
                                            <input type="hidden" name="redirect" value="<?= BASE_URL ?>/barbeiro/dashboard.php">
                                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">
                                                <i class="bi bi-play-fill me-1"></i> Iniciar Corte
                                            </button>
                                        </form>
                                    <?php elseif ($ag['status_atendimento'] === 'em_atendimento'): ?>
                                        <button type="button" class="btn btn-success btn-sm flex-grow-1 fw-bold" onclick="concluirCorteECobrar(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($ag['cliente_nome'])) ?>', '<?= number_format($ag['valor_total'], 2, ',', '.') ?>', '<?= htmlspecialchars(addslashes($ag['procedimentos'])) ?>', '<?= $ag['codigo_reserva'] ?>')">
                                            <i class="bi bi-check2-circle me-1"></i> Finalizar & Cobrar PIX
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($ag['status_pagamento'] === 'pendente'): ?>
                                        <button type="button" class="btn btn-danger btn-sm fw-bold" onclick="abrirModalQrCode(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($ag['cliente_nome'])) ?>', '<?= number_format($ag['valor_total'], 2, ',', '.') ?>', '<?= htmlspecialchars(addslashes($ag['procedimentos'])) ?>', '<?= $ag['codigo_reserva'] ?>')" title="Abrir QR Code de Pagamento para o Cliente">
                                            <i class="bi bi-qr-code me-1"></i> Cobrar PIX
                                        </button>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="solicitarFeedbackCliente('<?= htmlspecialchars(addslashes($ag['cliente_nome'])) ?>', '<?= preg_replace('/\D/', '', $ag['cliente_telefone']) ?>', '<?= BASE_URL ?>/cliente/dashboard.php')" title="Pedir Avaliação no WhatsApp">
                                        <i class="bi bi-whatsapp text-success"></i>
                                    </button>

                                    <?php if ($ag['status_atendimento'] !== 'cancelado'): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" title="Cancelar este atendimento" onclick='abrirModalCancelarBarbeiro(<?= json_encode($ag) ?>)'>
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ABA 2: Próximos Atendimentos Futuros -->
        <div class="tab-pane fade" id="tab-futuros" role="tabpanel" aria-labelledby="tab-futuros-btn">
            <div class="barber-card p-0">
                <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0 fw-bold"><i class="bi bi-calendar3 text-gold me-2"></i>Agenda dos Próximos Dias</h5>
                    <span class="text-secondary small">Total: <?= count($atendimentosFuturos) ?></span>
                </div>

                <?php if (empty($atendimentosFuturos)): ?>
                    <div class="p-4 text-center">
                        <p class="text-secondary mb-0">Nenhum atendimento futuro agendado para os próximos dias.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead style="background: #111319;">
                                <tr>
                                    <th class="ps-3">Data</th>
                                    <th>Horário</th>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Telefone</th>
                                    <th>Procedimentos</th>
                                    <th>Valor</th>
                                    <th>Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($atendimentosFuturos as $fut): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-white"><?= date('d/m/Y', strtotime($fut['data_agendamento'])) ?></td>
                                        <td class="text-gold fw-bold"><?= substr($fut['horario'], 0, 5) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars($fut['codigo_reserva']) ?></td>
                                        <td><?= htmlspecialchars($fut['cliente_nome']) ?></td>
                                        <td><?= htmlspecialchars($fut['cliente_telefone']) ?></td>
                                        <td class="small"><?= htmlspecialchars($fut['procedimentos']) ?></td>
                                        <td class="fw-bold">R$ <?= number_format($fut['valor_total'], 2, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $fut['status_pagamento'] ?>">
                                                <?= strtoupper($fut['status_pagamento']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA 3: Meus Procedimentos & Preços -->
        <div class="tab-pane fade" id="tab-servicos" role="tabpanel" aria-labelledby="tab-servicos-btn">
            <div class="barber-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h5 class="text-white fw-bold mb-1"><i class="bi bi-tag-fill text-gold me-2"></i>Seus Serviços Personalizados</h5>
                        <p class="text-secondary small mb-0">Procedimentos exclusivos com preços e tempos configurados por você</p>
                    </div>
                    <a href="<?= BASE_URL ?>/barbeiro/meus_servicos.php" class="btn btn-gold btn-sm">
                        <i class="bi bi-gear-fill me-1"></i> Abrir Painel Completo de Procedimentos
                    </a>
                </div>

                <?php if (empty($meusServicosCadastrados)): ?>
                    <div class="p-4 text-center">
                        <p class="text-secondary small mb-2">Você ainda não cadastrou nenhum procedimento próprio ou a barbearia utiliza o catálogo global.</p>
                        <a href="<?= BASE_URL ?>/barbeiro/meus_servicos.php" class="btn btn-outline-gold btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Cadastrar Meu Procedimento
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($meusServicosCadastrados as $ms): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <strong class="text-white"><?= htmlspecialchars($ms['nome']) ?></strong>
                                            <span class="badge bg-gold text-dark fw-bold">R$ <?= number_format($ms['preco'], 2, ',', '.') ?></span>
                                        </div>
                                        <small class="text-secondary d-block mb-2"><?= htmlspecialchars($ms['descricao'] ?? 'Sem descrição.') ?></small>
                                    </div>
                                    <small class="text-muted"><i class="bi bi-clock me-1 text-gold"></i> <?= (int)$ms['tempo_minutos'] ?> min</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA 4: Avaliações Recebidas -->
        <div class="tab-pane fade" id="tab-avaliacoes" role="tabpanel" aria-labelledby="tab-avaliacoes-btn">
            <div class="barber-card p-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <i class="bi bi-chat-quote-fill text-gold me-2"></i>Últimos Depoimentos dos Seus Clientes
                </h5>

                <?php if (empty($minhasAvaliacoes)): ?>
                    <div class="p-4 text-center">
                        <p class="text-secondary small mb-0">Nenhuma avaliação recebida ainda. Ao concluir cortes, envie o link no WhatsApp do cliente.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($minhasAvaliacoes as $av): ?>
                            <div class="col-md-6">
                                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="text-white"><?= htmlspecialchars($av['cliente_nome']) ?></strong>
                                        <div class="text-warning small">
                                            <?php for ($e = 1; $e <= 5; $e++): ?>
                                                <i class="bi <?= ($e <= $av['estrelas']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-1 fst-italic">"<?= htmlspecialchars($av['comentario'] ?? 'Ótimo atendimento!') ?>"</p>
                                    <small class="text-muted font-monospace" style="font-size: 0.75rem;">Reserva: <?= htmlspecialchars($av['codigo_reserva']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal de Cancelamento pelo Barbeiro -->
<div class="modal fade" id="modalCancelarBarbeiro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cancelar Atendimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/agendamento_cancelar.php" method="POST">
                <input type="hidden" name="agendamento_id" id="barbCancId" value="">
                <input type="hidden" name="redirect" value="<?= BASE_URL ?>/barbeiro/dashboard.php">
                
                <div class="modal-body py-3">
                    <p class="text-white mb-2">
                        Cliente: <strong id="barbCancCliente"></strong><br>
                        Reserva: <strong id="barbCancCodigo" class="text-gold font-monospace"></strong>
                    </p>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Como o cancelamento está sendo feito pelo barbeiro/equipe, o cliente é 100% isento e terá estorno total.
                    </div>
                    <div class="mb-3">
                        <label for="barbMotivo" class="form-label small text-secondary">Motivo do Cancelamento</label>
                        <textarea class="form-control form-control-sm" name="motivo_cancelamento" id="barbMotivo" rows="2" placeholder="Ex: Imprevisto de força maior, reagendado..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filtrarAgendaBarbeiro() {
    const termo = (document.getElementById('filtroAtendimentosBarbeiro').value || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.item-card-barbeiro');
    cards.forEach(card => {
        const txt = card.innerText.toLowerCase();
        card.style.display = txt.includes(termo) ? '' : 'none';
    });
}

function abrirModalCancelarBarbeiro(ag) {
    document.getElementById('barbCancId').value = ag.id;
    document.getElementById('barbCancCliente').innerText = ag.cliente_nome;
    document.getElementById('barbCancCodigo').innerText = ag.codigo_reserva;
    document.getElementById('barbMotivo').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalCancelarBarbeiro'));
    modal.show();
}

function concluirCorteECobrar(agendamentoId, clienteNome, valor, procedimentos, codigoReserva) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= BASE_URL ?>/actions/agendamento_status.php';

    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'agendamento_id';
    inputId.value = agendamentoId;

    const inputStatus = document.createElement('input');
    inputStatus.type = 'hidden';
    inputStatus.name = 'status';
    inputStatus.value = 'concluido';

    const inputRedirect = document.createElement('input');
    inputRedirect.type = 'hidden';
    inputRedirect.name = 'redirect';
    inputRedirect.value = '<?= BASE_URL ?>/barbeiro/dashboard.php?concluido_id=' + agendamentoId;

    form.appendChild(inputId);
    form.appendChild(inputStatus);
    form.appendChild(inputRedirect);
    document.body.appendChild(form);
    form.submit();
}

function solicitarFeedbackCliente(nome, telefone, linkAvaliacao) {
    if (!telefone) {
        showToast('warning', 'Telefone Indisponível', 'O cliente não possui telefone/WhatsApp cadastrado para solicitação de avaliação.');
        return;
    }
    const msg = `Olá ${nome}! Aqui é da Barbearia Corte na Navalha. Agradecemos pela sua visita! Poderia nos avaliar rapidamente clicando no link a seguir? ${linkAvaliacao}`;
    const url = `https://wa.me/55${telefone}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
