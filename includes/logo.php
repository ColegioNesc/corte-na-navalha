<?php
/**
 * Logotipo Oficial Vetorial - Barbearia Corte na Navalha
 * Design Exclusivo: Navalha Afiada e Tesoura de Precisão com Grafismo Vermelho
 */
$logoTamanho = $logoTamanho ?? 'normal'; // small, normal, large
$mostrarTexto = $mostrarTexto ?? true;

$dimensoes = [
    'small'  => ['largura' => 34, 'altura' => 34, 'titulo' => '1.15rem', 'sub' => '0.55rem'],
    'normal' => ['largura' => 44, 'altura' => 44, 'titulo' => '1.35rem', 'sub' => '0.65rem'],
    'large'  => ['largura' => 64, 'altura' => 64, 'titulo' => '1.9rem',  'sub' => '0.8rem'],
];
$d = $dimensoes[$logoTamanho] ?? $dimensoes['normal'];
?>
<div class="barber-brand-wrap d-inline-flex align-items-center gap-2 text-decoration-none">
    <!-- Emblema Vetorial Exclusivo Corte na Navalha -->
    <div class="barber-logo-emblem" style="width: <?= $d['largura'] ?>px; height: <?= $d['altura'] ?>px; flex-shrink: 0;">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; filter: drop-shadow(0 2px 8px rgba(220, 38, 38, 0.4));">
            <!-- Círculo de Fundo com Borda Vermelha -->
            <circle cx="50" cy="50" r="46" fill="#120406" stroke="#dc2626" stroke-width="3"/>
            <circle cx="50" cy="50" r="41" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="3 3"/>
            
            <!-- Tesoura de Barbeiro Cruzada -->
            <g stroke="#94a3b8" stroke-width="3" stroke-linecap="round">
                <!-- Lâmina Esquerda -->
                <line x1="30" y1="70" x2="68" y2="30"/>
                <!-- Lâmina Direita -->
                <line x1="70" y1="70" x2="32" y2="30"/>
                <!-- Olhais da Tesoura -->
                <circle cx="27" cy="74" r="7" stroke="#dc2626" stroke-width="3" fill="none"/>
                <circle cx="73" cy="74" r="7" stroke="#dc2626" stroke-width="3" fill="none"/>
                <!-- Parafuso Central de Articulação -->
                <circle cx="50" cy="50" r="3.5" fill="#ef4444" stroke="#ffffff" stroke-width="1.5"/>
            </g>

            <!-- Navalha Tradicional em Destaque Central -->
            <g id="navalha">
                <!-- Cabo da Navalha (Vermelho Rubro Texturizado) -->
                <path d="M50 48 L28 20 C26 18 22 20 23 24 L45 52 Z" fill="#b91c1c" stroke="#ef4444" stroke-width="1.5"/>
                <!-- Lâmina de Aço Inox Navalhete (Prata Polido) -->
                <path d="M50 48 L76 22 C79 19 83 23 80 26 L56 52 Z" fill="url(#metalGradient)" stroke="#ffffff" stroke-width="1.5"/>
                <!-- Fio de Corte Afiado -->
                <line x1="56" y1="52" x2="80" y2="26" stroke="#fca5a5" stroke-width="2"/>
            </g>

            <!-- Gradiente da Lâmina -->
            <defs>
                <linearGradient id="metalGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff"/>
                    <stop offset="45%" stop-color="#cbd5e1"/>
                    <stop offset="100%" stop-color="#64748b"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <?php if ($mostrarTexto): ?>
        <div class="d-flex flex-column text-start lh-1">
            <span class="brand-title fw-bold text-white" style="font-size: <?= $d['titulo'] ?>; letter-spacing: 1.5px; font-family: 'Oswald', 'Bebas Neue', sans-serif;">
                CORTE NA <span class="text-danger" style="color: #ef4444 !important;">NAVALHA</span>
            </span>
            <span class="brand-subtitle text-uppercase d-none d-sm-block" style="font-size: <?= $d['sub'] ?>; letter-spacing: 2.5px; color: #94a3b8; font-weight: 600;">
                Barbearia Tradicional
            </span>
        </div>
    <?php endif; ?>
</div>
