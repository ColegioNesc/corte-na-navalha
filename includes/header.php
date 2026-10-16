<?php
/**
 * Header Global da Aplicação
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLogged = isset($_SESSION['user_id']);
$userName = $_SESSION['user_nome'] ?? 'Visitante';
$pageTitle = $pageTitle ?? 'Alarme a Distância - Não perca o seu ponto';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Meta tags Mobile e PWA -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="Aplicativo de alarme por proximidade GPS para você não perder o ponto durante a viagem.">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Estilos Customizados da Aplicação -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar Mobile First -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="index.php">
            <span class="brand-icon"><i class="bi bi-bell-fill"></i></span>
            <span>Alarme a Distância</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Menu de Navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active fw-bold' : '' ?>" href="index.php">
                        <i class="bi bi-geo-alt-fill me-1"></i> Início / Trajeto
                    </a>
                </li>
                <?php if ($isLogged): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'historico.php') ? 'active fw-bold' : '' ?>" href="historico.php">
                            <i class="bi bi-clock-history me-1"></i> Histórico
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'perfil.php') ? 'active fw-bold' : '' ?>" href="perfil.php">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($userName) ?>
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Sair
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active fw-bold' : '' ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-bold" href="registro.php">
                            <i class="bi bi-person-plus-fill me-1"></i> Criar Conta
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 py-3" style="max-width: 900px;">
