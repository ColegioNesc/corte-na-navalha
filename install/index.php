<?php
/**
 * Assistente Interativo de Instalação e Migração (/install)
 * Barbearia Corte na Navalha
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=UTF-8');

// Diagnóstico de Requisitos do Servidor (Passo 1)
$phpVersionOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$pdoOk        = extension_loaded('pdo');
$pdoMysqlOk   = extension_loaded('pdo_mysql');
$mbstringOk   = extension_loaded('mbstring');
$jsonOk       = extension_loaded('json');
$configDir    = realpath(__DIR__ . '/../config') ?: (__DIR__ . '/../config');
$writableOk   = is_writable($configDir);

$todosRequisitosOk = ($phpVersionOk && $pdoOk && $pdoMysqlOk && $jsonOk && $writableOk);

// Pré-carregar valores atuais caso config/db_credentials.php já exista
$defaultHost = 'localhost';
$defaultPort = '3306';
$defaultName = 'corte_na_navalha';
$defaultUser = 'root';
$defaultPass = '';

if (file_exists($configDir . '/db_credentials.php')) {
    include_once $configDir . '/db_credentials.php';
    if (defined('DB_HOST')) $defaultHost = DB_HOST;
    if (defined('DB_PORT')) $defaultPort = DB_PORT;
    if (defined('DB_NAME')) $defaultName = DB_NAME;
    if (defined('DB_USER')) $defaultUser = DB_USER;
    if (defined('DB_PASS')) $defaultPass = DB_PASS;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador do Sistema | Corte na Navalha Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0c10;
            --card-dark: #12141c;
            --red-primary: #dc2626;
            --red-hover: #b91c1c;
            --gold-accent: #ef4444;
            --border-subtle: rgba(255, 255, 255, 0.1);
        }
        body {
            background: radial-gradient(circle at top, #1a080a 0%, #0b0c10 60%);
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .brand-font {
            font-family: 'Oswald', sans-serif;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .installer-card {
            background: var(--card-dark);
            border: 1px solid rgba(220, 38, 38, 0.35);
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }
        .step-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: #1e2230;
            color: #94a3b8;
            border: 2px solid #334155;
            transition: all 0.3s ease;
        }
        .step-item.active .step-badge {
            background: var(--red-primary);
            color: #fff;
            border-color: #f87171;
            box-shadow: 0 0 15px rgba(220, 38, 38, 0.5);
        }
        .step-item.completed .step-badge {
            background: #16a34a;
            color: #fff;
            border-color: #4ade80;
        }
        .form-control, .form-select {
            background-color: #090a0f !important;
            border: 1px solid #334155 !important;
            color: #f8fafc !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--red-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25) !important;
        }
        .btn-red {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #fff;
            border: none;
        }
        .btn-red:hover:not(:disabled) {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: #fff;
        }
        .check-row {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 12px 16px;
        }
        .wizard-step {
            display: none;
        }
        .wizard-step.active {
            display: block;
            animation: fadeIn 0.25s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <!-- Cabeçalho da Instalação -->
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 64px; height: 64px; background: rgba(220,38,38,0.15); border: 2px solid #dc2626;">
                    <i class="bi bi-scissors fs-2 text-danger"></i>
                </div>
                <h2 class="brand-font text-white fw-bold mb-1">Corte na <span class="text-danger">Navalha</span></h2>
                <p class="text-secondary small mb-0">Assistente de Configuração de Hospedagem & Migração de Banco de Dados</p>
            </div>

            <!-- Indicador de Passos (Stepper) -->
            <div class="d-flex justify-content-between align-items-center mb-4 px-2 px-md-4">
                <div class="text-center step-item active" id="ind-step-1">
                    <div class="step-badge mb-1">1</div>
                    <div class="small fw-semibold text-white" style="font-size: 0.75rem;">Ambiente</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-opacity-25 mx-2"></div>
                <div class="text-center step-item" id="ind-step-2">
                    <div class="step-badge mb-1">2</div>
                    <div class="small fw-semibold text-secondary" style="font-size: 0.75rem;">Banco do Host</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-opacity-25 mx-2"></div>
                <div class="text-center step-item" id="ind-step-3">
                    <div class="step-badge mb-1">3</div>
                    <div class="small fw-semibold text-secondary" style="font-size: 0.75rem;">Migração SQL</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-opacity-25 mx-2"></div>
                <div class="text-center step-item" id="ind-step-4">
                    <div class="step-badge mb-1">4</div>
                    <div class="small fw-semibold text-secondary" style="font-size: 0.75rem;">Conclusão</div>
                </div>
            </div>

            <!-- Card Principal -->
            <div class="installer-card p-4 p-md-5">

                <!-- =========================================================
                     PASSO 1: VERIFICAÇÃO DO AMBIENTE DO HOST
                     ========================================================= -->
                <div class="wizard-step active" id="step-1">
                    <h4 class="brand-font text-white mb-1"><i class="bi bi-cpu text-danger me-2"></i>1. Diagnóstico do Servidor</h4>
                    <p class="text-secondary small mb-4">Verificando se a hospedagem atende a todos os pré-requisitos para rodar o sistema.</p>

                    <div class="d-flex flex-column gap-2 mb-4">
                        <div class="check-row d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-white small">Versão do PHP (Mínimo 8.0+)</strong>
                                <span class="text-secondary" style="font-size: 0.78rem;">Versão detectada: PHP <?= PHP_VERSION ?></span>
                            </div>
                            <?php if ($phpVersionOk): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Aprovado</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Incompatível</span>
                            <?php endif; ?>
                        </div>

                        <div class="check-row d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-white small">Extensão PDO & Driver MySQL (pdo_mysql)</strong>
                                <span class="text-secondary" style="font-size: 0.78rem;">Necessário para conexão segura com o banco de dados</span>
                            </div>
                            <?php if ($pdoOk && $pdoMysqlOk): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Ausente</span>
                            <?php endif; ?>
                        </div>

                        <div class="check-row d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-white small">Extensões JSON & Multibyte String (UTF-8)</strong>
                                <span class="text-secondary" style="font-size: 0.78rem;">Necessário para API PIX, QR Code e acentuação</span>
                            </div>
                            <?php if ($jsonOk && $mbstringOk): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Parcial</span>
                            <?php endif; ?>
                        </div>

                        <div class="check-row d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-white small">Permissão de Escrita em <code>/config</code></strong>
                                <span class="text-secondary" style="font-size: 0.78rem;">Necessário para salvar o arquivo <code>db_credentials.php</code></span>
                            </div>
                            <?php if ($writableOk): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Liberado</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Sem Permissão</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($todosRequisitosOk): ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-success text-success small py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Todos os requisitos do servidor foram validados! Você já pode configurar o banco de dados.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger text-danger small py-2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Corrija os itens pendentes no servidor antes de prosseguir com a instalação.
                        </div>
                    <?php endif; ?>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-red px-4 py-2 fw-bold" onclick="irParaPasso(2)" <?= $todosRequisitosOk ? '' : 'disabled' ?>>
                            Próximo Passo: Banco de Dados <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- =========================================================
                     PASSO 2: CREDENCIAIS DO BANCO DE DADOS & TESTE DE CONEXÃO
                     ========================================================= -->
                <div class="wizard-step" id="step-2">
                    <h4 class="brand-font text-white mb-1"><i class="bi bi-database-gear text-danger me-2"></i>2. Conexão com o Banco de Dados</h4>
                    <p class="text-secondary small mb-3">Informe os dados do banco MySQL criado no painel da sua hospedagem (cPanel, Hostinger, InfinityFree ou XAMPP).</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small text-secondary">Host do Banco (Servidor MySQL) <span class="text-danger">*</span></label>
                            <input type="text" id="db_host" class="form-control" value="<?= htmlspecialchars($defaultHost) ?>" placeholder="Ex: localhost ou sql123.main-hosting.eu" oninput="invalidarTesteConexao()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Porta <span class="text-danger">*</span></label>
                            <input type="text" id="db_port" class="form-control" value="<?= htmlspecialchars($defaultPort) ?>" placeholder="3306" oninput="invalidarTesteConexao()">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small text-secondary">Nome do Banco de Dados (Database) <span class="text-danger">*</span></label>
                            <input type="text" id="db_name" class="form-control" value="<?= htmlspecialchars($defaultName) ?>" placeholder="Ex: u123456789_navalha ou corte_na_navalha" oninput="invalidarTesteConexao()">
                            <small class="text-secondary" style="font-size: 0.72rem;">Tip: em hospedagens compartilhadas, crie o banco no painel do host e cole o nome completo aqui.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Usuário do Banco <span class="text-danger">*</span></label>
                            <input type="text" id="db_user" class="form-control" value="<?= htmlspecialchars($defaultUser) ?>" placeholder="Ex: u123456789_admin ou root" oninput="invalidarTesteConexao()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Senha do Banco</label>
                            <input type="password" id="db_pass" class="form-control" value="<?= htmlspecialchars($defaultPass) ?>" placeholder="Senha do usuário MySQL (vazio no XAMPP)" oninput="invalidarTesteConexao()">
                        </div>
                    </div>

                    <!-- Dados iniciais opcionais da barbearia -->
                    <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-subtle);">
                        <span class="small fw-bold text-danger text-uppercase d-block mb-2"><i class="bi bi-shop me-1"></i> Identificação Inicial da Barbearia</span>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Nome da Barbearia</label>
                                <input type="text" id="nome_barbearia" class="form-control form-control-sm" value="Corte na Navalha Barbearia">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Chave PIX Principal</label>
                                <input type="text" id="chave_pix" class="form-control form-control-sm" value="pix@cortenanavalha.com.br">
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="modo_producao" onchange="alternarModoProducao(this.checked)">
                                    <label class="form-check-label small text-white fw-semibold" for="modo_producao">
                                        Ativar Modo Produção (Ocultar atalhos de teste e cadastrar o Proprietário oficial)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bloco Obrigatório de Cadastro do Proprietário quando Modo Produção estiver ativo -->
                        <div id="blocoCadastroDonoProducao" class="mt-3 pt-3 border-top border-danger border-opacity-50 d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-warning text-uppercase">
                                    <i class="bi bi-person-badge-fill me-1"></i> Cadastro Obrigatório do Proprietário (Dono)
                                </span>
                                <span class="badge bg-danger">Obrigatório em Produção</span>
                            </div>
                            <p class="text-secondary mb-3" style="font-size: 0.76rem;">
                                Como o Modo Produção foi ativado, defina agora os dados reais de acesso do administrador principal da barbearia:
                            </p>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">Nome Completo do Dono <span class="text-danger">*</span></label>
                                    <input type="text" id="dono_nome" class="form-control form-control-sm" placeholder="Ex: Fabio Silva">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">E-mail de Login <span class="text-danger">*</span></label>
                                    <input type="email" id="dono_email" class="form-control form-control-sm" placeholder="Ex: admin@suabarbearia.com.br">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">CPF do Proprietário <span class="text-danger">*</span></label>
                                    <input type="text" id="dono_cpf" class="form-control form-control-sm" placeholder="000.000.000-00" maxlength="14" oninput="mascaraCpf(this)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">Telefone / WhatsApp <span class="text-danger">*</span></label>
                                    <input type="text" id="dono_telefone" class="form-control form-control-sm" placeholder="(11) 99999-9999" maxlength="15" oninput="mascaraTelefone(this)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">Senha de Acesso (mín. 6 dígitos) <span class="text-danger">*</span></label>
                                    <input type="password" id="dono_senha" class="form-control form-control-sm" placeholder="••••••••">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary mb-1">Confirmar Senha <span class="text-danger">*</span></label>
                                    <input type="password" id="dono_senha_confirma" class="form-control form-control-sm" placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Box de Resultado do Teste -->
                    <div id="boxResultadoTeste" class="alert d-none small py-2 mb-3"></div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-2" onclick="irParaPasso(1)">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" id="btnTestarConexao" class="btn btn-outline-light px-3 py-2 fw-semibold" onclick="testarConexaoBanco()">
                                <i class="bi bi-plug-fill me-1 text-warning"></i> 1º Testar Conexão
                            </button>
                            <button type="button" id="btnAvancarMigracao" class="btn btn-red px-4 py-2 fw-bold" onclick="irParaPasso(3)" disabled>
                                2º Avançar para Migração <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- =========================================================
                     PASSO 3: MIGRAÇÃO DAS TABELAS E SEMENTE DE DADOS
                     ========================================================= -->
                <div class="wizard-step" id="step-3">
                    <h4 class="brand-font text-white mb-1"><i class="bi bi-cloud-arrow-up-fill text-danger me-2"></i>3. Migração da Base de Dados</h4>
                    <p class="text-secondary small mb-3">A conexão foi validada! Clique no botão abaixo para criar todas as 11 tabelas, aplicar as regras de negócio e importar os cadastros iniciais.</p>

                    <div class="p-3 rounded mb-3" style="background: #08090d; border: 1px solid #334155;">
                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Resumo da Conexão Validada:</span>
                            <span class="badge bg-success">Pronto para Migrar</span>
                        </div>
                        <div class="small text-white font-monospace" id="resumoConexaoTexto"></div>
                    </div>

                    <!-- Log de execução -->
                    <div id="logMigracaoContainer" class="p-3 rounded mb-3 font-monospace small d-none" style="background: #050608; border: 1px solid #334155; max-height: 220px; overflow-y: auto;">
                        <div id="listaLogsMigracao" class="d-flex flex-column gap-1"></div>
                    </div>

                    <div id="erroMigracaoAlert" class="alert alert-danger d-none small py-2"></div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" id="btnVoltarStep2" class="btn btn-outline-secondary btn-sm px-3 py-2" onclick="irParaPasso(2)">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </button>
                        <button type="button" id="btnExecutarMigracao" class="btn btn-red px-4 py-2 fw-bold" onclick="executarMigracaoCompleta()">
                            <i class="bi bi-rocket-takeoff-fill me-1"></i> Instalar & Migrar Base de Dados Agora
                        </button>
                    </div>
                </div>

                <!-- =========================================================
                     PASSO 4: FINALIZAÇÃO E AUTO-REMOÇÃO DA PASTA /install
                     ========================================================= -->
                <div class="wizard-step" id="step-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-25 text-success mb-3" style="width: 70px; height: 70px; border: 2px solid #22c55e;">
                            <i class="bi bi-check-lg display-5"></i>
                        </div>
                        <h3 class="brand-font text-white fw-bold mb-1">Sistema Instalado com Sucesso!</h3>
                        <p class="text-secondary small mb-0">Todas as tabelas, migrações e usuários iniciais foram configurados no seu servidor.</p>
                    </div>

                    <!-- Tabela de Contas Criadas -->
                    <div class="p-3 rounded mb-4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle);">
                        <h6 class="text-danger fw-bold small text-uppercase mb-2" id="tituloCredenciaisFinais">
                            <i class="bi bi-key-fill me-1"></i> Credenciais Iniciais (Senha padrão: <code>123456</code>)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm table-borderless small mb-0">
                                <thead>
                                    <tr class="text-secondary border-bottom border-secondary border-opacity-25">
                                        <th>Perfil</th>
                                        <th>E-mail / CPF de Login</th>
                                        <th>Painel</th>
                                    </tr>
                                </thead>
                                <tbody id="corpoTabelaCredenciais">
                                    <tr>
                                        <td><span class="badge bg-danger">Dono / Admin</span></td>
                                        <td class="font-monospace">dono@cortenanavalha.com.br</td>
                                        <td>Gestão Completa</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark">Recepção</span></td>
                                        <td class="font-monospace">recepcao@cortenanavalha.com.br</td>
                                        <td>Balcão & Caixa</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info text-dark">Barbeiro</span></td>
                                        <td class="font-monospace">lucas@cortenanavalha.com.br</td>
                                        <td>Agenda & Comissões</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-secondary">Cliente</span></td>
                                        <td class="font-monospace">joao.cliente@gmail.com</td>
                                        <td>Catálogo & Reserva</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-warning small py-2 mb-4">
                        <i class="bi bi-shield-lock-fill me-1"></i> <strong>Segurança de Produção:</strong> Ao clicar no botão abaixo, a pasta <code>/install</code> será automaticamente excluída do servidor e você será direcionado para a página inicial da barbearia.
                    </div>

                    <button type="button" id="btnFinalizarERemover" class="btn btn-success w-100 py-3 fw-bold fs-6" onclick="finalizarERemoverInstall()">
                        <i class="bi bi-trash3-fill me-1"></i> Remover Pasta /install e Iniciar o Sistema
                    </button>
                </div>

            </div>

            <div class="text-center mt-3 text-secondary small" style="font-size: 0.75rem;">
                Corte na Navalha Barbearia &bull; Setup Wizard v1.0
            </div>

        </div>
    </div>
</div>

<script>
let conexaoValidada = false;

function alternarModoProducao(ativo) {
    const bloco = document.getElementById('blocoCadastroDonoProducao');
    if (ativo) {
        bloco.classList.remove('d-none');
    } else {
        bloco.classList.add('d-none');
    }
}

function mascaraCpf(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 9) {
        v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
        v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (v.length > 3) {
        v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }
    input.value = v;
}

function mascaraTelefone(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 10) {
        v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (v.length > 6) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else if (v.length > 2) {
        v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    }
    input.value = v;
}

function validarCadastroDonoProducao() {
    const modoProducao = document.getElementById('modo_producao').checked;
    if (!modoProducao) return { ok: true };

    const nome = document.getElementById('dono_nome').value.trim();
    const email = document.getElementById('dono_email').value.trim();
    const cpf = document.getElementById('dono_cpf').value.trim();
    const tel = document.getElementById('dono_telefone').value.trim();
    const senha = document.getElementById('dono_senha').value;
    const confirma = document.getElementById('dono_senha_confirma').value;

    if (!nome || !email || !cpf || !tel || !senha || !confirma) {
        return {
            ok: false,
            msg: 'Com o Modo Produção ativo, é obrigatório preencher todos os dados de cadastro do Proprietário (Nome, E-mail, CPF, Telefone e Senha).'
        };
    }

    if (!email.includes('@') || !email.includes('.')) {
        return {
            ok: false,
            msg: 'Informe um endereço de e-mail válido para o Proprietário.'
        };
    }

    if (cpf.replace(/\D/g, '').length !== 11) {
        return {
            ok: false,
            msg: 'O CPF do Proprietário deve conter os 11 dígitos.'
        };
    }

    if (senha.length < 6) {
        return {
            ok: false,
            msg: 'A senha do Proprietário deve ter pelo menos 6 caracteres.'
        };
    }

    if (senha !== confirma) {
        return {
            ok: false,
            msg: 'A confirmação de senha do Proprietário não confere com a senha digitada.'
        };
    }

    return { ok: true };
}

function irParaPasso(numero) {
    if (numero === 3) {
        if (!conexaoValidada) {
            alert('Por favor, clique em "1º Testar Conexão" e valide o banco de dados antes de avançar.');
            return;
        }
        const validacaoDono = validarCadastroDonoProducao();
        if (!validacaoDono.ok) {
            const box = document.getElementById('boxResultadoTeste');
            box.className = 'alert alert-warning bg-warning bg-opacity-10 border-warning text-warning small py-2 mb-3';
            box.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${validacaoDono.msg}`;
            return;
        }
    }

    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + numero).classList.add('active');

    for (let i = 1; i <= 4; i++) {
        const ind = document.getElementById('ind-step-' + i);
        ind.classList.remove('active', 'completed');
        if (i < numero) {
            ind.classList.add('completed');
            ind.querySelector('.step-badge').innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (i === numero) {
            ind.classList.add('active');
            ind.querySelector('.step-badge').innerText = i;
        } else {
            ind.querySelector('.step-badge').innerText = i;
        }
    }

    if (numero === 3) {
        const host = document.getElementById('db_host').value.trim();
        const port = document.getElementById('db_port').value.trim();
        const name = document.getElementById('db_name').value.trim();
        const user = document.getElementById('db_user').value.trim();
        const modoProd = document.getElementById('modo_producao').checked;
        const donoEmail = document.getElementById('dono_email').value.trim();
        const infoExtra = modoProd ? ` | Modo Produção (Dono: ${donoEmail})` : ' | Modo Demonstração';
        document.getElementById('resumoConexaoTexto').innerText =
            `Servidor: ${host}:${port} | Database: ${name} | Usuário: ${user}${infoExtra}`;
    }
}

function invalidarTesteConexao() {
    conexaoValidada = false;
    document.getElementById('btnAvancarMigracao').disabled = true;
    const box = document.getElementById('boxResultadoTeste');
    box.className = 'alert d-none small py-2 mb-3';
}

async function testarConexaoBanco() {
    const btn = document.getElementById('btnTestarConexao');
    const box = document.getElementById('boxResultadoTeste');
    const payload = {
        acao: 'testar_conexao',
        db_host: document.getElementById('db_host').value.trim(),
        db_port: document.getElementById('db_port').value.trim(),
        db_name: document.getElementById('db_name').value.trim(),
        db_user: document.getElementById('db_user').value.trim(),
        db_pass: document.getElementById('db_pass').value
    };

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testando...';
    box.className = 'alert alert-secondary small py-2 mb-3';
    box.innerText = 'Verificando comunicação com o servidor MySQL...';

    try {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();

        if (res.success) {
            conexaoValidada = true;
            document.getElementById('btnAvancarMigracao').disabled = false;
            box.className = 'alert alert-success bg-success bg-opacity-10 border-success text-success small py-2 mb-3';
            box.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> <strong>Sucesso!</strong> ${res.message} (MySQL ${res.mysql_version})`;
        } else {
            conexaoValidada = false;
            document.getElementById('btnAvancarMigracao').disabled = true;
            box.className = 'alert alert-danger bg-danger bg-opacity-10 border-danger text-danger small py-2 mb-3';
            box.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> ${res.message}`;
        }
    } catch (err) {
        conexaoValidada = false;
        box.className = 'alert alert-danger small py-2 mb-3';
        box.innerText = 'Erro de rede ao tentar comunicar com install/api.php.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug-fill me-1 text-warning"></i> 1º Testar Conexão';
    }
}

async function executarMigracaoCompleta() {
    const validacaoDono = validarCadastroDonoProducao();
    if (!validacaoDono.ok) {
        irParaPasso(2);
        return;
    }

    const btn = document.getElementById('btnExecutarMigracao');
    const btnVoltar = document.getElementById('btnVoltarStep2');
    const logContainer = document.getElementById('logMigracaoContainer');
    const listaLogs = document.getElementById('listaLogsMigracao');
    const erroAlert = document.getElementById('erroMigracaoAlert');

    btn.disabled = true;
    btnVoltar.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Migrando Tabelas e Dados...';
    erroAlert.classList.add('d-none');
    logContainer.classList.remove('d-none');
    listaLogs.innerHTML = '<div class="text-secondary"><i class="bi bi-hourglass-split me-1"></i> Iniciando criação das tabelas e configuração do host...</div>';

    const modoProducaoAtivo = document.getElementById('modo_producao').checked;
    const payload = {
        acao: 'executar_instalacao',
        db_host: document.getElementById('db_host').value.trim(),
        db_port: document.getElementById('db_port').value.trim(),
        db_name: document.getElementById('db_name').value.trim(),
        db_user: document.getElementById('db_user').value.trim(),
        db_pass: document.getElementById('db_pass').value,
        nome_barbearia: document.getElementById('nome_barbearia').value.trim(),
        chave_pix: document.getElementById('chave_pix').value.trim(),
        modo_producao: modoProducaoAtivo,
        dono_nome: document.getElementById('dono_nome').value.trim(),
        dono_email: document.getElementById('dono_email').value.trim(),
        dono_cpf: document.getElementById('dono_cpf').value.trim(),
        dono_telefone: document.getElementById('dono_telefone').value.trim(),
        dono_senha: document.getElementById('dono_senha').value
    };

    try {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();

        listaLogs.innerHTML = '';
        if (Array.isArray(res.logs)) {
            res.logs.forEach(item => {
                const div = document.createElement('div');
                div.className = 'text-success';
                div.innerHTML = `<i class="bi bi-check2-circle me-1"></i> ${item}`;
                listaLogs.appendChild(div);
            });
        }

        if (res.success) {
            if (modoProducaoAtivo) {
                document.getElementById('tituloCredenciaisFinais').innerHTML =
                    '<i class="bi bi-shield-check me-1"></i> Conta Oficial do Proprietário Configurada (Modo Produção)';
                document.getElementById('corpoTabelaCredenciais').innerHTML = `
                    <tr>
                        <td><span class="badge bg-danger">Proprietário (Dono)</span></td>
                        <td class="font-monospace">${payload.dono_email} <span class="text-secondary">(${payload.dono_cpf})</span></td>
                        <td>Gestão Completa (Senha definida por você)</td>
                    </tr>
                `;
            }
            setTimeout(() => {
                irParaPasso(4);
            }, 900);
        } else {
            erroAlert.classList.remove('d-none');
            erroAlert.innerHTML = `<i class="bi bi-exclamation-octagon-fill me-1"></i> ${res.message}`;
            btn.disabled = false;
            btnVoltar.disabled = false;
            btn.innerHTML = '<i class="bi bi-rocket-takeoff-fill me-1"></i> Tentar Novamente';
        }
    } catch (err) {
        erroAlert.classList.remove('d-none');
        erroAlert.innerText = 'Falha inesperada durante a execução da migração.';
        btn.disabled = false;
        btnVoltar.disabled = false;
        btn.innerHTML = '<i class="bi bi-rocket-takeoff-fill me-1"></i> Tentar Novamente';
    }
}

async function finalizarERemoverInstall() {
    const btn = document.getElementById('btnFinalizarERemover');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Removendo pasta /install e iniciando...';

    try {
        await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ acao: 'remover_install' })
        });
    } catch (e) {
        // Mesmo se a resposta fechar após auto-deleção, redireciona para a home
    }

    window.location.href = '../index.php';
}
</script>

</body>
</html>

