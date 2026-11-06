<?php
/**
 * Navbar - Barbearia Corte na Navalha
 * Inclui: Logotipo Oficial com Navalha, Campo de Busca Global, Identificação de Cliente vs Profissional
 */
$usuario = getUsuarioLogado();
$perfilAtual = $usuario['perfil'] ?? null;
$ehProfissional = isProfissional($usuario);
?>
<nav class="navbar navbar-expand-lg navbar-barber sticky-top">
    <div class="container">
        <!-- Logo Oficial com Navalha & Tipografia Vintage -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <?php 
                $logoTamanho = 'normal';
                $mostrarTexto = true;
                require __DIR__ . '/logo.php'; 
            ?>
        </a>

        <!-- Botão Mobile -->
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navBarberContent" aria-controls="navBarberContent" aria-expanded="false" aria-label="Menu">
            <i class="bi bi-list fs-2 text-danger"></i>
        </button>

        <!-- Links, Pesquisa e Identificação -->
        <div class="collapse navbar-collapse" id="navBarberContent">
            <!-- Menu de Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#servicos">Procedimentos & Preços</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#sobre">A Barbearia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold" href="<?= BASE_URL ?>/cliente/agendar.php">
                        <i class="bi bi-calendar-check me-1"></i> Agendar Atendimento
                    </a>
                </li>
            </ul>

            <!-- Campo de Pesquisa Geral do Sistema -->
            <div class="nav-search-wrap d-none d-lg-block me-3">
                <i class="bi bi-search nav-search-icon"></i>
                <input type="search" id="navbarSearch" class="form-control nav-search-input" placeholder="Pesquisar serviços, cortes ou barbeiros..." aria-label="Pesquisar procedimentos da barbearia" onkeydown="if(event.key==='Enter') executarBuscaGlobal()">
            </div>

            <!-- Área de Usuário: Compreensão Clara Cliente vs Profissional -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($usuario): ?>
                    <!-- Dropdown Usuário Logado -->
                    <div class="dropdown">
                        <button class="btn btn-outline-danger dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="d-none d-sm-inline fw-semibold"><?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                            
                            <!-- Visualização Clara: Cliente vs Profissional -->
                            <?php if ($ehProfissional): ?>
                                <span class="badge badge-role-profissional ms-1">
                                    <i class="bi bi-scissors me-1"></i> PROFISSIONAL • <?= strtoupper($usuario['perfil']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-role-cliente ms-1">
                                    <i class="bi bi-person-fill me-1"></i> CLIENTE
                                </span>
                            <?php endif; ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end barber-card p-2 border-danger shadow">
                            <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1">
                                <small class="text-secondary d-block text-uppercase" style="font-size: 0.7rem;">Conta Conectada</small>
                                <strong class="text-white d-block text-truncate" style="max-width: 220px;"><?= htmlspecialchars($usuario['nome']) ?></strong>
                                <span class="badge <?= $ehProfissional ? 'bg-danger text-white' : 'bg-primary text-white' ?> mt-1 text-uppercase" style="font-size: 0.68rem;">
                                    <?= getTipoUsuarioFormatado($usuario['perfil']) ?>
                                </span>
                            </li>

                            <?php if ($usuario['perfil'] === 'cliente'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/dashboard.php"><i class="bi bi-calendar-event me-2 text-danger"></i>Meus Agendamentos</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/agendar.php"><i class="bi bi-plus-circle me-2 text-danger"></i>Novo Agendamento</a></li>
                            <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/barbeiro/dashboard.php"><i class="bi bi-scissors me-2 text-danger"></i>Painel do Barbeiro</a></li>
                            <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/recepcao/dashboard.php"><i class="bi bi-receipt me-2 text-danger"></i>Painel da Recepção</a></li>
                            <?php elseif ($usuario['perfil'] === 'dono'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-danger"></i>Painel de Gestão</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/funcionarios.php"><i class="bi bi-people me-2 text-danger"></i>Equipe & Pagamento PIX</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/servicos.php"><i class="bi bi-tag me-2 text-danger"></i>Tabela de Preços</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/empresa.php"><i class="bi bi-gear me-2 text-danger"></i>Dados da Barbearia</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/perfil.php"><i class="bi bi-person-gear me-2 text-secondary"></i>Meu Perfil & Segurança</a></li>

                            <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                            <li><a class="dropdown-item text-danger py-2" href="<?= BASE_URL ?>/actions/auth_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair do Sistema</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Botões de Acesso (Login / Cadastro) -->
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-red">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </a>
                    <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-red">
                        <i class="bi bi-person-plus"></i> Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
