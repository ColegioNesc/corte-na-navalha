<?php
/**
 * Editor Rico de Modelos de E-mail (Estilo Word)
 * Barbearia Corte na Navalha (Conforme mods.md)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLogin(['dono']);

$pdo = getDBConnection();

// Buscar todos os templates
$templates = $pdo->query("SELECT * FROM templates_email ORDER BY id ASC")->fetchAll();

$slugAtual = trim($_GET['slug'] ?? 'comprovante_pagamento');
$stmtCur = $pdo->prepare("SELECT * FROM templates_email WHERE slug = :slug LIMIT 1");
$stmtCur->execute([':slug' => $slugAtual]);
$templateAtual = $stmtCur->fetch();

if (!$templateAtual && !empty($templates)) {
    $templateAtual = $templates[0];
    $slugAtual = $templateAtual['slug'];
}

$paginaTitulo = 'Editor de Mensagens & Templates | Gestão';
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
                        <i class="bi bi-file-earmark-richtext"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-1">Personalização de Mensagens (Editor Estilo Word)</h4>
                        <p class="text-secondary small mb-0">Formate os e-mails com negrito, cores, alinhamentos e tags dinâmicas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?= BASE_URL ?>/admin/email_config.php" class="btn btn-outline-secondary btn-sm py-2 px-3">
                    <i class="bi bi-gear me-1"></i> Servidor SMTP
                </a>
            </div>
        </div>
    </div>

    <!-- Seletor de Modelo de Mensagem -->
    <div class="mb-4">
        <label class="form-label small text-secondary fw-bold text-uppercase d-block mb-2">
            Selecione o Modelo para Editar:
        </label>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($templates as $t): ?>
                <a href="?slug=<?= urlencode($t['slug']) ?>" class="btn <?= ($t['slug'] === $slugAtual) ? 'btn-red fw-bold shadow' : 'btn-outline-secondary text-white' ?> py-2 px-3">
                    <i class="bi <?= ($t['slug'] === 'comprovante_pagamento') ? 'bi-receipt' : (($t['slug'] === 'agendamento_confirmado') ? 'bi-calendar-check' : 'bi-key') ?> me-1"></i>
                    <?= htmlspecialchars($t['titulo']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($templateAtual): ?>
        <form action="<?= BASE_URL ?>/actions/email_template_salvar.php" method="POST" id="formTemplateEmail">
            <input type="hidden" name="slug" value="<?= htmlspecialchars($templateAtual['slug']) ?>">
            <input type="hidden" name="titulo" value="<?= htmlspecialchars($templateAtual['titulo']) ?>">
            <input type="hidden" name="corpo_html" id="inputCorpoHtml">

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Configurações do Assunto -->
                    <div class="barber-card p-4 mb-3">
                        <label class="form-label fw-bold text-white">
                            <i class="bi bi-chat-left-dots text-danger me-1"></i> Assunto do E-mail
                        </label>
                        <input type="text" class="form-control form-control-lg" name="assunto" id="inputAssunto" value="<?= htmlspecialchars($templateAtual['assunto']) ?>" required>
                        <small class="text-secondary mt-1 d-block">
                            Você pode utilizar tags como <code>{NOME_BARBEARIA}</code> e <code>{CODIGO_RESERVA}</code> no assunto.
                        </small>
                    </div>

                    <!-- Barra de Ferramentas Estilo Word (Rich Text Editor) -->
                    <div class="barber-card p-0 mb-4 overflow-hidden border-danger">
                        <div class="word-toolbar p-2 d-flex flex-wrap align-items-center gap-1 border-bottom border-secondary border-opacity-25" style="background: #111319;">
                            <!-- Formatação de Texto -->
                            <div class="btn-group btn-group-sm me-1" role="group">
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('bold')" title="Negrito (Ctrl+B)">
                                    <i class="bi bi-type-bold"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('italic')" title="Itálico (Ctrl+I)">
                                    <i class="bi bi-type-italic"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('underline')" title="Sublinhado (Ctrl+U)">
                                    <i class="bi bi-type-underline"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('strikeThrough')" title="Tachado">
                                    <i class="bi bi-type-strikethrough"></i>
                                </button>
                            </div>

                            <!-- Títulos e Tamanhos -->
                            <div class="btn-group btn-group-sm me-1" role="group">
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('formatBlock', '<h2>')" title="Título 1">
                                    <strong style="font-size: 0.85rem;">H2</strong>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('formatBlock', '<h3>')" title="Título 2">
                                    <strong style="font-size: 0.85rem;">H3</strong>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('formatBlock', '<p>')" title="Parágrafo Normal">
                                    <i class="bi bi-paragraph"></i>
                                </button>
                            </div>

                            <!-- Alinhamento -->
                            <div class="btn-group btn-group-sm me-1" role="group">
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('justifyLeft')" title="Alinhar à Esquerda">
                                    <i class="bi bi-text-left"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('justifyCenter')" title="Centralizar">
                                    <i class="bi bi-text-center"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('justifyRight')" title="Alinhar à Direita">
                                    <i class="bi bi-text-right"></i>
                                </button>
                            </div>

                            <!-- Listas -->
                            <div class="btn-group btn-group-sm me-1" role="group">
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('insertUnorderedList')" title="Lista com Marcadores">
                                    <i class="bi bi-list-ul"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('insertOrderedList')" title="Lista Numerada">
                                    <i class="bi bi-list-ol"></i>
                                </button>
                            </div>

                            <!-- Cores Rápidas Barbershop -->
                            <div class="btn-group btn-group-sm me-1" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="execCmd('foreColor', '#dc2626')" title="Cor Vermelho Rubro">
                                    <span style="display:inline-block; width:12px; height:12px; background:#dc2626; border-radius:2px;"></span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="execCmd('foreColor', '#d97706')" title="Cor Ouro/Dourado">
                                    <span style="display:inline-block; width:12px; height:12px; background:#d97706; border-radius:2px;"></span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="execCmd('foreColor', '#22c55e')" title="Cor Verde Sucesso">
                                    <span style="display:inline-block; width:12px; height:12px; background:#22c55e; border-radius:2px;"></span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="execCmd('foreColor', '#f1f5f9')" title="Cor Branco Neve">
                                    <span style="display:inline-block; width:12px; height:12px; background:#f1f5f9; border-radius:2px;"></span>
                                </button>
                            </div>

                            <!-- Ações Extras -->
                            <div class="btn-group btn-group-sm ms-auto" role="group">
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="inserirLinhaHorizontal()" title="Inserir Linha Divisória">
                                    <i class="bi bi-hr"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary text-white" onclick="execCmd('removeFormat')" title="Limpar Formatação">
                                    <i class="bi bi-eraser"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Canvas Editável (Estilo Página do Word) -->
                        <div id="wordCanvas" contenteditable="true" class="p-4" style="min-height: 420px; background: #0b0c10; color: #f1f5f9; font-family: 'Plus Jakarta Sans', Arial, sans-serif; line-height: 1.6; outline: none;">
                            <?= $templateAtual['corpo_html'] ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold" onclick="prepararEnvioTemplate()">
                        <i class="bi bi-save me-1"></i> Salvar Este Modelo de Mensagem
                    </button>
                </div>

                <!-- Coluna Lateral: Tags Dinâmicas -->
                <div class="col-lg-4">
                    <div class="barber-card p-4 sticky-top" style="top: 100px;">
                        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                            <i class="bi bi-code-slash text-gold me-1"></i>Tags Dinâmicas
                        </h5>
                        <p class="text-secondary small mb-3">
                            Clique em qualquer tag abaixo para inseri-la automaticamente no ponto do texto onde você está digitando:
                        </p>

                        <div class="d-flex flex-column gap-2 mb-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{CLIENTE_NOME}')">
                                <strong class="text-gold font-monospace">{CLIENTE_NOME}</strong>
                                <small class="text-secondary d-block">Nome completo do cliente</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{CODIGO_RESERVA}')">
                                <strong class="text-gold font-monospace">{CODIGO_RESERVA}</strong>
                                <small class="text-secondary d-block">Código do agendamento (ex: CN-2026...)</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{VALOR_TOTAL}')">
                                <strong class="text-gold font-monospace">{VALOR_TOTAL}</strong>
                                <small class="text-secondary d-block">Valor total cobrado (ex: 85,00)</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{PROCEDIMENTOS}')">
                                <strong class="text-gold font-monospace">{PROCEDIMENTOS}</strong>
                                <small class="text-secondary d-block">Cortes e serviços contratados</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{BARBEIRO_NOME}')">
                                <strong class="text-gold font-monospace">{BARBEIRO_NOME}</strong>
                                <small class="text-secondary d-block">Nome do barbeiro responsável</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{DATA}')">
                                <strong class="text-gold font-monospace">{DATA}</strong>
                                <small class="text-secondary d-block">Data do atendimento (dd/mm/aaaa)</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{HORARIO}')">
                                <strong class="text-gold font-monospace">{HORARIO}</strong>
                                <small class="text-secondary d-block">Horário agendado (ex: 14:30)</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{METODO_PAGAMENTO}')">
                                <strong class="text-gold font-monospace">{METODO_PAGAMENTO}</strong>
                                <small class="text-secondary d-block">Forma de pagamento (PIX / Dinheiro)</small>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-secondary text-start text-white p-2" onclick="inserirTag('{NOME_BARBEARIA}')">
                                <strong class="text-gold font-monospace">{NOME_BARBEARIA}</strong>
                                <small class="text-secondary d-block">Nome oficial da barbearia</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function execCmd(command, value = null) {
    document.getElementById('wordCanvas').focus();
    document.execCommand(command, false, value);
}

function inserirLinhaHorizontal() {
    execCmd('insertHorizontalRule');
}

function inserirTag(tag) {
    const canvas = document.getElementById('wordCanvas');
    canvas.focus();
    const sel = window.getSelection();
    if (sel.getRangeAt && sel.rangeCount) {
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const textNode = document.createTextNode(tag);
        range.insertNode(textNode);
        range.setStartAfter(textNode);
        range.setEndAfter(textNode);
        sel.removeAllRanges();
        sel.addRange(range);
    } else {
        canvas.innerHTML += tag;
    }
}

function prepararEnvioTemplate() {
    const html = document.getElementById('wordCanvas').innerHTML;
    document.getElementById('inputCorpoHtml').value = html;
}

document.getElementById('formTemplateEmail')?.addEventListener('submit', function() {
    prepararEnvioTemplate();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

