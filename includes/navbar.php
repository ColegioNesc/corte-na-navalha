<?php
/**
 * Navbar - Barbearia Corte na Navalha
 * Inclui: Logotipo Oficial com Navalha, Campo de Busca Global, Identificação de Perfis e Ações Rápidas
 */
$usuario = getUsuarioLogado();
$perfilAtual = $usuario['perfil'] ?? null;
$ehProfissional = isProfissional($usuario);
?>
<nav class="navbar navbar-expand-lg navbar-barber sticky-top">
    <div class="container">
        <!-- Logo Oficial com Navalha & Tipografia Vintage -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
            <?php 
                $logoTamanho = 'normal';
                $mostrarTexto = true;
                require __DIR__ . '/logo.php'; 
            ?>
        </a>

        <!-- Botão Mobile Customizado -->
        <button class="navbar-toggler navbar-toggler-barber" type="button" data-bs-toggle="collapse" data-bs-target="#navBarberContent" aria-controls="navBarberContent" aria-expanded="false" aria-label="Menu de Navegação">
            <i class="bi bi-list fs-3"></i>
        </button>

        <!-- Links, Pesquisa e Identificação -->
        <div class="collapse navbar-collapse" id="navBarberContent">
            <!-- Menu de Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-house-door me-1 opacity-75"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#servicos">
                        <i class="bi bi-scissors me-1 opacity-75"></i> Procedimentos & Preços
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#sobre">
                        <i class="bi bi-info-circle me-1 opacity-75"></i> A Barbearia
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-nav-cta my-1 my-lg-0 ms-lg-2" href="<?= BASE_URL ?>/cliente/agendar.php">
                        <i class="bi bi-calendar-check-fill me-1"></i> Agendar Atendimento
                    </a>
                </li>
            </ul>

            <!-- Campo de Pesquisa Geral do Sistema (Desktop) -->
            <div class="nav-search-wrap d-none d-lg-block me-3">
                <i class="bi bi-search nav-search-icon"></i>
                <input type="search" id="navbarSearch" class="form-control nav-search-input" placeholder="Buscar corte ou barbeiro..." aria-label="Pesquisar procedimentos da barbearia" onkeydown="if(event.key==='Enter') executarBuscaGlobal(this)">
            </div>

            <!-- Campo de Pesquisa Geral do Sistema (Mobile) -->
            <div class="nav-search-wrap d-lg-none w-100 my-2">
                <i class="bi bi-search nav-search-icon"></i>
                <input type="search" id="navbarSearchMobile" class="form-control nav-search-input" placeholder="Buscar corte ou barbeiro..." aria-label="Pesquisar procedimentos da barbearia" onkeydown="if(event.key==='Enter') executarBuscaGlobal(this)">
            </div>

            <!-- Área de Usuário: Compreensão Clara Cliente vs Profissional -->
            <div class="d-flex align-items-center gap-2 nav-user-area">
                <?php if ($usuario): ?>
                    <!-- Dropdown Usuário Logado -->
                    <div class="dropdown w-100 w-lg-auto">
                        <button class="btn btn-nav-user dropdown-toggle d-flex align-items-center gap-2 w-100 justify-content-between justify-content-lg-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center gap-2">
                                <div class="nav-avatar-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="fw-semibold text-white"><?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                            </div>
                            
                            <!-- Badges com Paleta Oficial da Barbearia -->
                            <?php if ($usuario['perfil'] === 'dono'): ?>
                                <span class="badge badge-role-dono ms-1">
                                    <i class="bi bi-award-fill me-1"></i> GESTOR
                                </span>
                            <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                <span class="badge badge-role-profissional ms-1">
                                    <i class="bi bi-scissors me-1"></i> BARBEIRO
                                </span>
                            <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                <span class="badge badge-role-recepcao ms-1">
                                    <i class="bi bi-receipt me-1"></i> RECEPÇÃO
                                </span>
                            <?php else: ?>
                                <span class="badge badge-role-cliente ms-1">
                                    <i class="bi bi-shield-check me-1"></i> CLIENTE
                                </span>
                            <?php endif; ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end barber-dropdown-menu shadow">
                            <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1 bg-black bg-opacity-25 rounded-top">
                                <small class="text-secondary d-block text-uppercase" style="font-size: 0.68rem; letter-spacing: 1px;">Conta Conectada</small>
                                <strong class="text-white d-block text-truncate" style="max-width: 220px; font-size: 0.95rem;"><?= htmlspecialchars($usuario['nome']) ?></strong>
                                <?php if ($usuario['perfil'] === 'dono'): ?>
                                    <span class="badge badge-role-dono mt-1" style="font-size: 0.68rem;">Proprietário da Barbearia</span>
                                <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                    <span class="badge badge-role-profissional mt-1" style="font-size: 0.68rem;">Barbeiro Especialista</span>
                                <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                    <span class="badge badge-role-recepcao mt-1" style="font-size: 0.68rem;">Recepção & Atendimento</span>
                                <?php else: ?>
                                    <span class="badge badge-role-cliente mt-1" style="font-size: 0.68rem;">Cliente Fidelidade</span>
                                <?php endif; ?>
                            </li>

                            <?php if ($usuario['perfil'] === 'cliente'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/dashboard.php"><i class="bi bi-calendar-event me-2 text-danger-custom"></i>Meus Agendamentos</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/agendar.php"><i class="bi bi-plus-circle me-2 text-danger-custom"></i>Novo Agendamento</a></li>
                            <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/barbeiro/dashboard.php"><i class="bi bi-scissors me-2 text-danger-custom"></i>Painel do Barbeiro</a></li>
                            <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/recepcao/dashboard.php"><i class="bi bi-receipt me-2 text-danger-custom"></i>Painel da Recepção</a></li>
                            <?php elseif ($usuario['perfil'] === 'dono'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-danger-custom"></i>Painel de Gestão</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/funcionarios.php"><i class="bi bi-people me-2 text-danger-custom"></i>Equipe & Pagamentos</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/servicos.php"><i class="bi bi-tag me-2 text-danger-custom"></i>Tabela de Preços</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/empresa.php"><i class="bi bi-gear me-2 text-danger-custom"></i>Dados da Barbearia</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/perfil.php"><i class="bi bi-person-gear me-2 text-secondary"></i>Meu Perfil & Segurança</a></li>

                            <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>
                            <li><a class="dropdown-item text-danger-custom py-2" href="<?= BASE_URL ?>/actions/auth_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair do Sistema</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Botões de Acesso (Login / Cadastro) -->
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline-red btn-nav">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </a>
                    <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-sm btn-red btn-nav">
                        <i class="bi bi-person-plus me-1"></i> Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
