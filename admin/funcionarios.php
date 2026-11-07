<?php
/**
 * Gestão de Funcionários & Pagamento PIX - Barbearia Corte na Navalha
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$pdo = getDBConnection();

// Buscar colaboradores (barbeiros e recepção)
$sql = "
    SELECT u.*, 
           COALESCE(SUM(CASE WHEN a.status_pagamento = 'pago' AND a.status_atendimento = 'concluido' THEN a.valor_total ELSE 0 END), 0) as total_faturado,
           COUNT(DISTINCT a.id) as total_cortes
    FROM usuarios u
    LEFT JOIN agendamentos a ON a.barbeiro_id = u.id
    WHERE u.perfil IN ('barbeiro', 'recepcao')
    GROUP BY u.id
    ORDER BY u.perfil ASC, u.nome ASC
";
$funcionarios = $pdo->query($sql)->fetchAll();

// Histórico de pagamentos feitos a funcionários
$sqlPagamentos = "
    SELECT p.*, f.nome as funcionario_nome, f.cpf as funcionario_cpf
    FROM pagamentos_funcionarios p
    JOIN usuarios f ON p.funcionario_id = f.id
    ORDER BY p.id DESC
    LIMIT 15
";
$historicoPagamentos = $pdo->query($sqlPagamentos)->fetchAll();

// Se veio com parâmetro para abrir modal de pagamento direto
$pagarId = (int)($_GET['pagar_id'] ?? 0);

$paginaTitulo = 'Equipe & Pagamento PIX dos Funcionários | Gestão';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho -->
    <div class="barber-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Voltar ao Painel Principal">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Equipe & Repasse PIX aos Colaboradores</h4>
                        <p class="text-secondary small mb-0">Cadastre profissionais, gerencie especialidades e transfira comissões via PIX</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2">
                <a href="<?= BASE_URL ?>/admin/transacoes.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-receipt me-1"></i> Livro Caixa
                </a>
                <button type="button" class="btn btn-gold btn-sm py-2 px-3" onclick="abrirModalFuncionario()">
                    <i class="bi bi-person-plus-fill me-1"></i> Novo Colaborador
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Colaboradores -->
    <div class="barber-card p-0 mb-5">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold"><i class="bi bi-person-badge text-danger me-2"></i>Profissionais Cadastrados</h5>
            <span class="text-secondary small">Total: <?= count($funcionarios) ?></span>
        </div>

        <!-- Campo de Pesquisa em Tempo Real de Funcionários -->
        <div class="p-3 border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-50">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-danger"><i class="bi bi-search"></i></span>
                <input type="text" id="filtroAdminFuncionarios" class="form-control" placeholder="Pesquisar profissionais por nome, perfil, e-mail ou chave PIX..." oninput="filtrarAdminFuncionarios()">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead style="background: #111319;">
                    <tr>
                        <th class="ps-3">Nome & Especialidade</th>
                        <th>Perfil</th>
                        <th>E-mail / Telefone</th>
                        <th>Chave PIX</th>
                        <th>Comissão (%)</th>
                        <th>Faturamento</th>
                        <th>Status / Permissões</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $f): ?>
                        <?php 
                            $comissaoPerc = (float)$f['comissao_percentual'];
                            $valorComissao = ((float)$f['total_faturado'] * $comissaoPerc) / 100;
                            $permissoesAtivas = !empty($f['permissoes']) ? json_decode($f['permissoes'], true) : [];
                            if (!is_array($permissoesAtivas)) $permissoesAtivas = [];
                        ?>
                        <tr class="<?= empty($f['ativo']) ? 'opacity-75' : '' ?>">
                            <td class="ps-3">
                                <strong class="text-white d-block"><?= htmlspecialchars($f['nome']) ?></strong>
                                <small class="text-gold"><?= htmlspecialchars($f['especialidade'] ?? 'Colaborador') ?></small>
                                <?php if (!empty($f['permite_catalogo_proprio'])): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger d-inline-block mt-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-tag-fill me-1"></i> Catálogo Individual Liberado
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-uppercase">
                                    <?= htmlspecialchars($f['perfil']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <div><?= htmlspecialchars($f['email']) ?></div>
                                    <span class="text-secondary"><?= htmlspecialchars($f['telefone']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($f['chave_pix'])): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark border border-warning text-gold font-monospace">
                                            <?= htmlspecialchars($f['chave_pix']) ?>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Copiar Chave PIX" onclick="copiarParaClipboard('<?= htmlspecialchars($f['chave_pix']) ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Tipo: <?= htmlspecialchars($f['tipo_chave_pix'] ?? 'Chave') ?></small>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="bi bi-exclamation-circle"></i> Não cadastrada</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($comissaoPerc, 0) ?>%</td>
                            <td>
                                <span class="fw-bold d-block text-white">R$ <?= number_format($f['total_faturado'], 2, ',', '.') ?></span>
                                <small class="text-gold">Comissão: R$ <?= number_format($valorComissao, 2, ',', '.') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($f['ativo'])): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success mb-1">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger mb-1">Inativo</span>
                                <?php endif; ?>
                                
                                <div class="small text-secondary" style="font-size: 0.75rem;">
                                    <?= count($permissoesAtivas) ?> permissão(ões)
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= BASE_URL ?>/admin/transacoes.php?funcionario_id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-info py-1 px-2" title="Ver Extrato e Transações deste Profissional">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-gold py-1 px-2" title="Pagar Funcionário via Chave PIX" onclick='abrirModalPagarPix(<?= json_encode($f) ?>, <?= $valorComissao ?>)'>
                                        <i class="bi bi-send-check-fill me-1"></i> PIX
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Editar Dados e Permissões" onclick='editarFuncionario(<?= json_encode($f) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Botão Ativar / Inativar -->
                                    <form id="formStatusFunc_<?= $f['id'] ?>" action="<?= BASE_URL ?>/actions/funcionario_status.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="ativo" value="<?= empty($f['ativo']) ? 1 : 0 ?>">
                                        <button type="button" class="btn btn-sm <?= empty($f['ativo']) ? 'btn-outline-success' : 'btn-outline-warning' ?> py-1 px-2" title="<?= empty($f['ativo']) ? 'Ativar Colaborador' : 'Inativar Colaborador' ?>" onclick="confirmarStatusFuncionario('formStatusFunc_<?= $f['id'] ?>', '<?= empty($f['ativo']) ? 'ATIVAR' : 'INATIVAR' ?>', '<?= htmlspecialchars($f['nome'], ENT_QUOTES) ?>')">
                                            <i class="bi <?= empty($f['ativo']) ? 'bi-check-circle' : 'bi-pause-circle' ?>"></i>
                                        </button>
                                    </form>
                                    <!-- Botão Excluir -->
                                    <form id="formExcluirFunc_<?= $f['id'] ?>" action="<?= BASE_URL ?>/actions/funcionario_excluir.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" title="Excluir Colaborador" onclick="confirmarExclusaoFuncionario('formExcluirFunc_<?= $f['id'] ?>', '<?= htmlspecialchars($f['nome'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Histórico de Pagamentos PIX aos Funcionários -->
    <div class="barber-card p-0">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold"><i class="bi bi-cash-stack text-gold me-2"></i>Histórico de Repasses PIX Realizados</h5>
            <span class="text-secondary small">Últimos repasses</span>
        </div>

        <?php if (empty($historicoPagamentos)): ?>
            <div class="p-4 text-center">
                <p class="text-secondary small mb-0">Nenhum pagamento registrado ainda. Os repasses efetuados aparecerão aqui com recibo.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead style="background: #111319;">
                        <tr>
                            <th class="ps-3">Data/Hora</th>
                            <th>Colaborador</th>
                            <th>Valor Transferido</th>
                            <th>Chave PIX Utilizada</th>
                            <th>Comprovante / TXID</th>
                            <th>Referência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historicoPagamentos as $pag): ?>
                            <tr>
                                <td class="ps-3"><?= date('d/m/Y H:i', strtotime($pag['data_pagamento'])) ?></td>
                                <td class="fw-bold text-white"><?= htmlspecialchars($pag['funcionario_nome']) ?></td>
                                <td class="text-success fw-bold">R$ <?= number_format($pag['valor'], 2, ',', '.') ?></td>
                                <td class="font-monospace small text-gold"><?= htmlspecialchars($pag['chave_pix_utilizada']) ?> (<?= htmlspecialchars($pag['tipo_chave']) ?>)</td>
                                <td class="font-monospace small text-secondary"><?= htmlspecialchars($pag['comprovante_transacao']) ?></td>
                                <td class="small"><?= htmlspecialchars($pag['referencia_periodo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Cadastro / Edição de Funcionário -->
<div class="modal fade" id="modalFuncionario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-gold" id="modalFuncionarioTitulo">Cadastrar Colaborador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/funcionario_salvar.php" method="POST">
                <input type="hidden" name="id" id="funcId" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" id="funcNome" required placeholder="Ex: Ricardo Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Perfil de Acesso</label>
                            <select class="form-select" name="perfil" id="funcPerfil" required>
                                <option value="barbeiro">Barbeiro</option>
                                <option value="recepcao">Recepção</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail de Acesso</label>
                            <input type="email" class="form-control" name="email" id="funcEmail" required placeholder="colaborador@cortenanavalha.com.br">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CPF</label>
                            <input type="text" class="form-control mask-cpf" name="cpf" id="funcCpf" required placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone / WhatsApp</label>
                            <input type="text" class="form-control mask-phone" name="telefone" id="funcTelefone" required placeholder="(11) 98765-4321">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha de Acesso</label>
                            <input type="password" class="form-control" name="senha" id="funcSenha" placeholder="Deixe em branco para manter a atual">
                            <small class="text-muted" style="font-size: 0.72rem;">Padrão: 123456</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Especialidade / Função</label>
                            <input type="text" class="form-control" name="especialidade" id="funcEspecialidade" placeholder="Ex: Especialista em Degradê Navalhado e Barboterapia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Chave PIX</label>
                            <select class="form-select" name="tipo_chave_pix" id="funcTipoPix">
                                <option value="E-mail">E-mail</option>
                                <option value="CPF">CPF</option>
                                <option value="Telefone">Telefone</option>
                                <option value="Aleatória">Chave Aleatória</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Chave PIX do Funcionário</label>
                            <input type="text" class="form-control" name="chave_pix" id="funcChavePix" placeholder="Chave para transferir pagamentos">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Comissão (%)</label>
                            <input type="number" step="0.5" min="0" max="100" class="form-control" name="comissao_percentual" id="funcComissao" value="50.0">
                        </div>

                        <!-- Configuração de Catálogo Individual -->
                        <div class="col-12 mt-3 pt-3 border-top border-secondary border-opacity-25" id="blocoCatalogoIndividual">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permite_catalogo_proprio" id="funcPermiteCatalogo" value="1">
                                <label class="form-check-label text-white fw-bold" for="funcPermiteCatalogo">
                                    <i class="bi bi-tag-fill text-danger me-1"></i> Liberar Catálogo Individual para este Barbeiro
                                </label>
                            </div>
                            <small class="text-secondary d-block mt-1">
                                Quando ativado pelo dono, este barbeiro poderá definir serviços, tempos e preços específicos exclusivos dele.
                            </small>
                        </div>

                        <!-- Permissões de Acesso -->
                        <div class="col-12 mt-3 pt-3 border-top border-secondary border-opacity-25">
                            <label class="form-label text-gold fw-bold mb-2">
                                <i class="bi bi-shield-check me-1"></i> Permissões de Acesso:
                            </label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissoes[]" value="gerenciar_agendamentos" id="permAgendamentos">
                                        <label class="form-check-label text-secondary small" for="permAgendamentos">
                                            Gerenciar agendamentos
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissoes[]" value="operar_caixa" id="permCaixa">
                                        <label class="form-check-label text-secondary small" for="permCaixa">
                                            Operar caixa e recebimentos
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissoes[]" value="ver_financeiro" id="permFinanceiro">
                                        <label class="form-check-label text-secondary small" for="permFinanceiro">
                                            Visualizar relatórios financeiros
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissoes[]" value="gerenciar_servicos_proprios" id="permServicos">
                                        <label class="form-check-label text-secondary small" for="permServicos">
                                            Gerenciar serviços do próprio catálogo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status da Conta -->
                        <div class="col-12 mt-2 pt-2 border-top border-secondary border-opacity-25">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ativo" id="funcAtivo" value="1" checked>
                                <label class="form-check-label text-white" for="funcAtivo">
                                    Colaborador Ativo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-check2-circle me-1"></i> Salvar Colaborador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Realizar Pagamento PIX ao Funcionário -->
<div class="modal fade" id="modalPagarPix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content barber-card">
            <div class="modal-header">
                <h5 class="modal-title brand-font text-gold">
                    <i class="bi bi-qr-code text-gold me-2"></i>Pagar Funcionário via PIX
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/actions/funcionario_pagar.php" method="POST">
                <input type="hidden" name="funcionario_id" id="pagarFuncId" value="">
                <input type="hidden" name="tipo_chave" id="pagarTipoChave" value="">

                <div class="modal-body">
                    <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3 text-center">
                        <small class="text-secondary d-block">Beneficiário</small>
                        <h5 class="text-white fw-bold mb-1" id="pagarNomeFunc"></h5>
                        <div class="badge bg-gold text-dark font-monospace fs-6 px-3 py-2 mt-2" id="pagarChavePixDisplay"></div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarParaClipboard(document.getElementById('pagarChavePixInput').value)">
                                <i class="bi bi-clipboard me-1"></i> Copiar Chave PIX
                            </button>
                        </div>
                        <input type="hidden" name="chave_pix" id="pagarChavePixInput" value="">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor a Transferir (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-gold">R$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control fs-5 fw-bold" name="valor" id="pagarValor" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Referência / Descrição</label>
                        <input type="text" class="form-control" name="referencia_periodo" value="Comissão de Atendimentos Realizados" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações (Opcional)</label>
                        <textarea class="form-control" name="observacoes" rows="2" placeholder="Ex: Pago via app bancário da empresa"></textarea>
                    </div>

                    <div class="p-2 rounded bg-dark text-secondary small">
                        <i class="bi bi-shield-lock text-gold me-1"></i> Esta ação registrará o pagamento com comprovante e dará baixa no controle financeiro.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold">
                        <i class="bi bi-check-all me-1"></i> Confirmar Pagamento PIX
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filtrarAdminFuncionarios() {
    const termo = (document.getElementById('filtroAdminFuncionarios').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(tr => {
        const txt = tr.innerText.toLowerCase();
        tr.style.display = txt.includes(termo) ? '' : 'none';
    });
}

function abrirModalFuncionario() {
    document.getElementById('modalFuncionarioTitulo').innerText = 'Cadastrar Colaborador';
    document.getElementById('funcId').value = 0;
    document.getElementById('funcNome').value = '';
    document.getElementById('funcEmail').value = '';
    document.getElementById('funcCpf').value = '';
    document.getElementById('funcTelefone').value = '';
    document.getElementById('funcSenha').value = '';
    document.getElementById('funcEspecialidade').value = '';
    document.getElementById('funcChavePix').value = '';
    document.getElementById('funcComissao').value = '50.0';
    document.getElementById('funcPermiteCatalogo').checked = false;
    document.getElementById('funcAtivo').checked = true;
    
    document.querySelectorAll('.perm-check').forEach(chk => {
        chk.checked = false;
    });

    new bootstrap.Modal(document.getElementById('modalFuncionario')).show();
}

function editarFuncionario(f) {
    document.getElementById('modalFuncionarioTitulo').innerText = 'Editar Colaborador & Permissões';
    document.getElementById('funcId').value = f.id;
    document.getElementById('funcNome').value = f.nome;
    document.getElementById('funcPerfil').value = f.perfil;
    document.getElementById('funcEmail').value = f.email;
    document.getElementById('funcCpf').value = f.cpf;
    document.getElementById('funcTelefone').value = f.telefone;
    document.getElementById('funcSenha').value = '';
    document.getElementById('funcEspecialidade').value = f.especialidade || '';
    document.getElementById('funcTipoPix').value = f.tipo_chave_pix || 'E-mail';
    document.getElementById('funcChavePix').value = f.chave_pix || '';
    document.getElementById('funcComissao').value = f.comissao_percentual || '50.0';
    document.getElementById('funcPermiteCatalogo').checked = parseInt(f.permite_catalogo_proprio || 0) === 1;
    document.getElementById('funcAtivo').checked = parseInt(f.ativo ?? 1) === 1;

    let perms = [];
    try {
        if (f.permissoes) {
            perms = typeof f.permissoes === 'string' ? JSON.parse(f.permissoes) : f.permissoes;
        }
    } catch(e) { perms = []; }
    if (!Array.isArray(perms)) perms = [];

    document.querySelectorAll('.perm-check').forEach(chk => {
        chk.checked = perms.includes(chk.value);
    });

    new bootstrap.Modal(document.getElementById('modalFuncionario')).show();
}

function abrirModalPagarPix(f, valorEstimado) {
    if (!f.chave_pix) {
        abrirModalConfirmacao(
            'Chave PIX Ausente',
            `O colaborador <strong>${f.nome}</strong> ainda não possui uma chave PIX cadastrada. Deseja cadastrar agora?`,
            function() {
                editarFuncionario(f);
            },
            'Cadastrar Chave PIX',
            'btn-warning'
        );
        return;
    }

    document.getElementById('pagarFuncId').value = f.id;
    document.getElementById('pagarNomeFunc').innerText = f.nome;
    document.getElementById('pagarChavePixDisplay').innerText = f.chave_pix;
    document.getElementById('pagarChavePixInput').value = f.chave_pix;
    document.getElementById('pagarTipoChave').value = f.tipo_chave_pix || 'Chave PIX';
    document.getElementById('pagarValor').value = valorEstimado > 0 ? valorEstimado.toFixed(2) : '50.00';

    new bootstrap.Modal(document.getElementById('modalPagarPix')).show();
}

function confirmarStatusFuncionario(formId, acao, nome) {
    abrirModalConfirmacao(
        'Alterar Status de Colaborador',
        `Deseja realmente <strong>${acao}</strong> o colaborador <strong>${nome}</strong>?`,
        function() {
            document.getElementById(formId).submit();
        },
        acao,
        acao === 'ATIVAR' ? 'btn-success' : 'btn-warning'
    );
}

function confirmarExclusaoFuncionario(formId, nome) {
    abrirModalConfirmacao(
        'Confirmar Remoção de Colaborador',
        `ATENÇÃO: Deseja realmente remover o colaborador <strong>${nome}</strong> do sistema? Esta ação é permanente e irreversível.`,
        function() {
            document.getElementById(formId).submit();
        },
        'Sim, Remover',
        'btn-danger'
    );
}

// Se foi chamado com pagar_id via URL
document.addEventListener('DOMContentLoaded', function() {
    const pagarId = <?= (int)$pagarId ?>;
    if (pagarId > 0) {
        const funcs = <?= json_encode($funcionarios) ?>;
        const target = funcs.find(item => item.id == pagarId);
        if (target) {
            const perc = parseFloat(target.comissao_percentual);
            const val = (parseFloat(target.total_faturado) * perc) / 100;
            abrirModalPagarPix(target, val);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
