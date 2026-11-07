<?php
/**
 * Header Global - Barbearia Dinâmica (Nome e Paleta de Cores Personalizáveis)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

$empresa = getEmpresaConfig();
$nomeBarbeariaGlobal = $empresa['nome_barbearia'] ?? 'Corte na Navalha';
$paletaAtiva = getPaletaAtiva($empresa['cor_tema'] ?? 'crimson');
$paginaTitulo = $paginaTitulo ?? ($nomeBarbeariaGlobal . ' | Barbearia Premium');
$usuarioAtual = getUsuarioLogado();

$favPrimary = urlencode($paletaAtiva['primary']);
$favLight   = urlencode($paletaAtiva['light']);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($paginaTitulo) ?></title>
    
    <!-- Favicon Oficial Dinâmico -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='48' fill='%230d0e13'/><circle cx='50' cy='50' r='42' fill='none' stroke='<?= $favPrimary ?>' stroke-width='4'/><path d='M30 70 L70 30' stroke='<?= $favLight ?>' stroke-width='6' stroke-linecap='round'/><path d='M45 45 L75 25' stroke='%23ffffff' stroke-width='5' stroke-linecap='round'/></svg>">

    <!-- Prevenção imediata contra Flash de Fundo Branco (FOUC) -->
    <style>
        html, body {
            background-color: #0a0b0e !important;
            color: #f8fafc !important;
        }
    </style>

    <!-- Google Fonts (Carregamento assíncrono não-bloqueante) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@600;700;900&family=Oswald:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilo Oficial da Barbearia (Com versionamento automático anti-cache) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: '2.2' ?>">

    <!-- Paleta de Cores Personalizada da Barbearia -->
    <style id="barber-theme-vars">
        :root {
            --theme-rgb: <?= $paletaAtiva['rgb'] ?>;
            --red-primary: <?= $paletaAtiva['primary'] ?>;
            --red-dark: <?= $paletaAtiva['dark'] ?>;
            --red-darker: <?= $paletaAtiva['darker'] ?>;
            --red-light: <?= $paletaAtiva['light'] ?>;
            --red-glow: rgba(<?= $paletaAtiva['rgb'] ?>, 0.4);
            --red-subtle: rgba(<?= $paletaAtiva['rgb'] ?>, 0.12);
            --border-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.28);
            --border-active: <?= $paletaAtiva['light'] ?>;
            --shadow-card: 0 12px 30px -5px rgba(0, 0, 0, 0.6), 0 4px 12px rgba(<?= $paletaAtiva['rgb'] ?>, 0.1);
            --shadow-red: 0 0 25px rgba(<?= $paletaAtiva['rgb'] ?>, 0.35);
        }
        .text-danger, .text-gold, .text-red {
            color: var(--red-light) !important;
        }
        .border-danger {
            border-color: var(--red-primary) !important;
        }
        .bg-danger {
            background-color: var(--red-primary) !important;
        }
        .navbar-barber {
            border-bottom-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.35) !important;
        }
        .btn-red, .btn-gold, .btn-nav-cta, .btn-nav-cta-mobile {
            background: linear-gradient(135deg, var(--red-light) 0%, var(--red-dark) 100%) !important;
            border-color: var(--red-light) !important;
            box-shadow: 0 4px 16px rgba(<?= $paletaAtiva['rgb'] ?>, 0.38) !important;
        }
        .btn-red:hover, .btn-gold:hover, .btn-nav-cta:hover, .btn-nav-cta-mobile:hover {
            background: linear-gradient(135deg, <?= $paletaAtiva['lighter'] ?> 0%, var(--red-primary) 100%) !important;
            box-shadow: 0 6px 22px rgba(<?= $paletaAtiva['rgb'] ?>, 0.55) !important;
            color: #ffffff !important;
        }
        .btn-outline-red, .btn-outline-gold {
            color: var(--red-light) !important;
            border-color: var(--red-primary) !important;
        }
        .btn-outline-red:hover, .btn-outline-gold:hover {
            background: rgba(<?= $paletaAtiva['rgb'] ?>, 0.16) !important;
            border-color: var(--red-light) !important;
            box-shadow: 0 0 15px rgba(<?= $paletaAtiva['rgb'] ?>, 0.35) !important;
            color: #ffffff !important;
        }
        .navbar-toggler-barber {
            background: rgba(<?= $paletaAtiva['rgb'] ?>, 0.1) !important;
            border-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.4) !important;
            color: var(--red-light) !important;
        }
        .stat-icon, .nav-avatar-icon {
            background: rgba(<?= $paletaAtiva['rgb'] ?>, 0.15) !important;
            color: var(--red-light) !important;
            border-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.35) !important;
        }
        .hero-section {
            background: linear-gradient(180deg, rgba(10, 11, 14, 0.75) 0%, rgba(10, 11, 14, 0.98) 100%),
                        radial-gradient(ellipse at center, rgba(<?= $paletaAtiva['rgb'] ?>, 0.22) 0%, transparent 70%) !important;
        }
        .barber-card:hover {
            border-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.55) !important;
            box-shadow: 0 14px 34px -5px rgba(0, 0, 0, 0.7), 0 0 20px rgba(<?= $paletaAtiva['rgb'] ?>, 0.2) !important;
        }
        .nav-search-input, .btn-nav-user, .barber-dropdown-menu, .mobile-user-card {
            border-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.35) !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--red-light) !important;
            box-shadow: 0 0 0 0.25rem rgba(<?= $paletaAtiva['rgb'] ?>, 0.25) !important;
        }
        @media (max-width: 991.98px) {
            .barber-offcanvas {
                background-image: radial-gradient(circle at 100% 0%, rgba(<?= $paletaAtiva['rgb'] ?>, 0.18) 0%, transparent 60%) !important;
                border-left-color: rgba(<?= $paletaAtiva['rgb'] ?>, 0.35) !important;
            }
        }
    </style>
    
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
</head>
<body>
<?php require_once __DIR__ . '/toast.php'; ?>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="flex-grow-1">
