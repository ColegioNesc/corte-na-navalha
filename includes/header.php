<?php
/**
 * Header Global - Corte na Navalha Barbearia
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

$empresa = getEmpresaConfig();
$paginaTitulo = $paginaTitulo ?? 'Corte na Navalha | Barbearia Premium';
$usuarioAtual = getUsuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($paginaTitulo) ?></title>
    
    <!-- Favicon Oficial Corte na Navalha -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='48' fill='%23120406'/><circle cx='50' cy='50' r='42' fill='none' stroke='%23dc2626' stroke-width='4'/><path d='M30 70 L70 30' stroke='%23ef4444' stroke-width='6' stroke-linecap='round'/><path d='M45 45 L75 25' stroke='%23ffffff' stroke-width='5' stroke-linecap='round'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilo Oficial da Barbearia -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/toast.php'; ?>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="flex-grow-1">
