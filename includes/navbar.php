<?php
/**
 * Navbar - Barbearia Corte na Navalha
 * UX/UI Aprimorado:
 * - Desktop: Layout espaçoso, sem encavalamento, busca compacta expansível e CTA destacado.
 * - Mobile (< 992px): Barra compacta com botão de agendamento rápido + Gaveta Offcanvas premium.
 */
$usuario = getUsuarioLogado();
$perfilAtual = $usuario['perfil'] ?? null;
$ehProfissional = isProfissional($usuario);
?>
<nav class="navbar navbar-expand-lg navbar-barber sticky-top">
    <div class="container">
        <!-- Logotipo Oficial da Barbearia -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
            <?php 
                $logoTamanho = 'normal';
                $mostrarTexto = true;
                require __DIR__ . '/logo.php'; 
            ?>
        </a>

        <!-- Controles Mobile (Visíveis apenas em telas < 992px) -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-sm btn-nav-cta-mobile" title="Agendar Horário">
                <i class="bi bi-calendar-check-fill me-1"></i> Agendar
            </a>
            <button class="navbar-toggler navbar-toggler-barber" type="button" data-bs-toggle="offcanvas" data-bs-target="#navBarberOffcanvas" aria-controls="navBarberOffcanvas" aria-label="Abrir Menu">
                <i class="bi bi-list fs-3"></i>
            </button>
        </div>

        <!-- Gaveta Offcanvas no Mobile / Barra Horizontal no Desktop -->
        <div class="offcanvas-lg offcanvas-end barber-offcanvas" tabindex="-1" id="navBarberOffcanvas" aria-labelledby="navBarberOffcanvasLabel">
            <!-- Cabeçalho do Offcanvas (Apenas Mobile) -->
            <div class="offcanvas-header d-lg-none border-bottom border-secondary border-opacity-25 py-3 px-4">
                <div class="d-flex align-items-center">
                    <?php 
                        $logoTamanho = 'small';
                        $mostrarTexto = true;
                        require __DIR__ . '/logo.php'; 
                    ?>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#navBarberOffcanvas" aria-label="Fechar Menu"></button>
            </div>

            <!-- Corpo do Menu: Links e Ações -->
            <div class="offcanvas-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between p-4 p-lg-0">
                <!-- Barra de Pesquisa Rápida (Mobile no topo da gaveta) -->
                <div class="d-lg-none mb-4">
                    <div class="nav-search-wrap-mobile">
                        <i class="bi bi-search nav-search-icon"></i>
                        <input type="search" id="navbarSearchMobile" class="form-control nav-search-input" placeholder="Buscar cortes, procedimentos..." aria-label="Pesquisar procedimentos da barbearia" onkeydown="if(event.key==='Enter') { executarBuscaGlobal(this); fecharOffcanvasMobile(); }">
                    </div>
                </div>

                <!-- Links de Navegação Institucional -->
                <ul class="navbar-nav mb-3 mb-lg-0 ms-lg-3 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php">
                            <i class="bi bi-house-door d-lg-none me-2 text-danger"></i>
                            Início
                            <i class="bi bi-chevron-right d-lg-none ms-auto text-secondary opacity-50"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php#servicos">
                            <i class="bi bi-scissors d-lg-none me-2 text-danger"></i>
                            Procedimentos & Preços
                            <i class="bi bi-chevron-right d-lg-none ms-auto text-secondary opacity-50"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php#sobre">
                            <i class="bi bi-info-circle d-lg-none me-2 text-danger"></i>
                            A Barbearia
                            <i class="bi bi-chevron-right d-lg-none ms-auto text-secondary opacity-50"></i>
                        </a>
                    </li>
                </ul>

                <!-- Área da Direita: Busca Desktop + CTA Agendar + Perfil/Login -->
                <div class="navbar-actions-group d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 mt-auto mt-lg-0 pt-3 pt-lg-0">
                    <!-- Campo de Busca no Desktop (Compacto e Expansível) -->
                    <div class="nav-search-wrap d-none d-lg-block">
                        <i class="bi bi-search nav-search-icon"></i>
                        <input type="search" id="navbarSearch" class="form-control nav-search-input" placeholder="Buscar..." aria-label="Pesquisar procedimentos da barbearia" onkeydown="if(event.key==='Enter') executarBuscaGlobal(this)">
                    </div>

                    <!-- Botão CTA Principal de Agendamento (Desktop) -->
                    <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-sm btn-nav-cta d-none d-lg-inline-flex">
                        <i class="bi bi-calendar-check-fill me-1"></i> Agendar
                    </a>

                    <!-- Usuário Autenticado vs Visitante -->
                    <?php if ($usuario): ?>
                        <!-- Dropdown no Desktop -->
                        <div class="dropdown d-none d-lg-block">
                            <button class="btn btn-nav-user dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="nav-avatar-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="fw-semibold text-white"><?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end barber-dropdown-menu shadow">
                                <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1 bg-black bg-opacity-30 rounded-top">
                                    <small class="text-secondary d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Conta Conectada</small>
                                    <strong class="text-white d-block text-truncate" style="max-width: 220px; font-size: 0.95rem;"><?= htmlspecialchars($usuario['nome']) ?></strong>
                                    <?php if ($usuario['perfil'] === 'dono'): ?>
                                        <span class="badge badge-role-dono mt-1">Proprietário</span>
                                    <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                        <span class="badge badge-role-profissional mt-1">Barbeiro Especialista</span>
                                    <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                        <span class="badge badge-role-recepcao mt-1">Recepção</span>
                                    <?php else: ?>
                                        <span class="badge badge-role-cliente mt-1">Cliente</span>
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

                        <!-- Card de Perfil Mobile (Dentro da Gaveta Offcanvas) -->
                        <div class="mobile-user-card d-lg-none p-3 rounded mb-3">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="nav-avatar-icon" style="width: 38px; height: 38px; font-size: 1.15rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <strong class="text-white d-block text-truncate"><?= htmlspecialchars($usuario['nome']) ?></strong>
                                    <?php if ($usuario['perfil'] === 'dono'): ?>
                                        <span class="badge badge-role-dono">GESTOR</span>
                                    <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                        <span class="badge badge-role-profissional">BARBEIRO</span>
                                    <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                        <span class="badge badge-role-recepcao">RECEPÇÃO</span>
                                    <?php else: ?>
                                        <span class="badge badge-role-cliente">CLIENTE</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mobile-user-links d-flex flex-column gap-1 mt-2 pt-2 border-top border-secondary border-opacity-25">
                                <?php if ($usuario['perfil'] === 'cliente'): ?>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/cliente/dashboard.php"><i class="bi bi-calendar-event me-2 text-danger"></i>Meus Agendamentos</a>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/cliente/agendar.php"><i class="bi bi-plus-circle me-2 text-danger"></i>Novo Agendamento</a>
                                <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/barbeiro/dashboard.php"><i class="bi bi-scissors me-2 text-danger"></i>Painel do Barbeiro</a>
                                <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/recepcao/dashboard.php"><i class="bi bi-receipt me-2 text-danger"></i>Painel da Recepção</a>
                                <?php elseif ($usuario['perfil'] === 'dono'): ?>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-danger"></i>Painel de Gestão</a>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/admin/funcionarios.php"><i class="bi bi-people me-2 text-danger"></i>Equipe</a>
                                    <a class="mobile-nav-link" href="<?= BASE_URL ?>/admin/servicos.php"><i class="bi bi-tag me-2 text-danger"></i>Preços</a>
                                <?php endif; ?>
                                <a class="mobile-nav-link" href="<?= BASE_URL ?>/perfil.php"><i class="bi bi-person-gear me-2 text-secondary"></i>Meu Perfil</a>
                                <a class="mobile-nav-link text-danger" href="<?= BASE_URL ?>/actions/auth_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair da Conta</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Botão Entrar no Desktop -->
                        <div class="d-none d-lg-flex align-items-center">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline-red btn-nav">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                            </a>
                        </div>

                        <!-- Botões Mobile de Acesso (Dentro da Gaveta Offcanvas) -->
                        <div class="d-lg-none d-flex flex-column gap-2 mb-3">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-red w-100 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Acessar Minha Conta
                            </a>
                            <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-dark border-secondary border-opacity-50 text-secondary w-100 py-2 small">
                                Não tem cadastro? Criar Conta
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Botão CTA Principal no Mobile (Dentro da Gaveta Offcanvas) -->
                    <div class="d-lg-none mt-2">
                        <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-red w-100 py-3 fw-bold fs-6 shadow">
                            <i class="bi bi-calendar-check-fill me-2"></i> Agendar Atendimento
                        </a>
                    </div>

                    <!-- Rodapé Informativo Rápido (Apenas Mobile) -->
                    <div class="d-lg-none mt-4 pt-3 border-top border-secondary border-opacity-25 text-center text-secondary small">
                        <p class="mb-1"><i class="bi bi-clock me-1 text-danger"></i> Terça a Sábado: 08:00 às 20:00</p>
                        <p class="mb-0"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($empresa['nome_barbearia'] ?? 'Corte na Navalha') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
