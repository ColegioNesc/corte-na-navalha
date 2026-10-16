<?php
/**
 * Navbar - Barbearia Corte na Navalha
 */
$usuario = getUsuarioLogado();
$perfilAtual = $usuario['perfil'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-barber sticky-top">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="fs-4">✂️</span>
            <span>CORTE NA</span> NAVALHA
        </a>

        <!-- Botão Mobile -->
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navBarberContent" aria-controls="navBarberContent" aria-expanded="false" aria-label="Menu">
            <i class="bi bi-list fs-2 text-gold"></i>
        </button>

        <!-- Links e Ações -->
        <div class="collapse navbar-collapse" id="navBarberContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#servicos">Tabela de Serviços</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#sobre">A Barbearia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-gold" href="<?= BASE_URL ?>/cliente/agendar.php">
                        <i class="bi bi-calendar-check me-1"></i> Agendar Atendimento
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($usuario): ?>
                    <!-- Dropdown Usuário Logado -->
                    <div class="dropdown">
                        <button class="btn btn-outline-gold dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-sm-inline"><?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                            <span class="badge bg-gold text-dark ms-1 text-uppercase" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($usuario['perfil']) ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end barber-card p-2">
                            <?php if ($usuario['perfil'] === 'cliente'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/dashboard.php"><i class="bi bi-calendar-event me-2 text-gold"></i>Meus Agendamentos</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/cliente/agendar.php"><i class="bi bi-plus-circle me-2 text-gold"></i>Novo Agendamento</a></li>
                            <?php elseif ($usuario['perfil'] === 'barbeiro'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/barbeiro/dashboard.php"><i class="bi bi-scissors me-2 text-gold"></i>Painel de Atendimentos</a></li>
                            <?php elseif ($usuario['perfil'] === 'recepcao'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/recepcao/dashboard.php"><i class="bi bi-receipt me-2 text-gold"></i>Painel da Recepção</a></li>
                            <?php elseif ($usuario['perfil'] === 'dono'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-gold"></i>Painel de Gestão</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/funcionarios.php"><i class="bi bi-people me-2 text-gold"></i>Equipe & Pagamento PIX</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/servicos.php"><i class="bi bi-tag me-2 text-gold"></i>Tabela de Preços</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/empresa.php"><i class="bi bi-gear me-2 text-gold"></i>Dados da Barbearia</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item text-danger py-2" href="<?= BASE_URL ?>/actions/auth_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair do Sistema</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Botões de Acesso -->
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-gold">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </a>
                    <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-gold">
                        <i class="bi bi-person-plus"></i> Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
