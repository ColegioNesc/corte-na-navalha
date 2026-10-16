<?php
/**
 * Página Inicial da Barbearia Corte na Navalha
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$paginaTitulo = 'Corte na Navalha | Barbearia Tradicional & Moderna';
$pdo = getDBConnection();
$empresa = getEmpresaConfig();

// Buscar Serviços Ativos
$stmtServicos = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY destaque DESC, id ASC");
$servicos = $stmtServicos->fetchAll();

// Buscar Barbeiros Ativos e suas médias de avaliações
$stmtBarbeiros = $pdo->query("
    SELECT u.*, 
           COALESCE(AVG(a.estrelas), 5.0) as media_estrelas,
           COUNT(a.id) as total_avaliacoes
    FROM usuarios u
    LEFT JOIN avaliacoes a ON a.barbeiro_id = u.id
    WHERE u.perfil = 'barbeiro' AND u.ativo = 1
    GROUP BY u.id
    ORDER BY u.id ASC
");
$barbeiros = $stmtBarbeiros->fetchAll();

// Buscar Avaliações Recentes de Clientes
$stmtAvaliacoes = $pdo->query("
    SELECT a.*, c.nome as cliente_nome, b.nome as barbeiro_nome, s.nome as servico_nome
    FROM avaliacoes a
    JOIN usuarios c ON a.cliente_id = c.id
    JOIN usuarios b ON a.barbeiro_id = b.id
    JOIN agendamentos ag ON a.agendamento_id = ag.id
    JOIN agendamento_itens ai ON ag.id = ai.agendamento_id
    JOIN servicos s ON ai.servico_id = s.id
    ORDER BY a.id DESC
    LIMIT 3
");
$avaliacoes = $stmtAvaliacoes->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <span class="badge border border-warning text-gold px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold" style="letter-spacing: 2px;">
                    <i class="bi bi-award me-1"></i> A Melhor Experiência de Barbearia
                </span>
                <h1 class="hero-title text-white">
                    PRECISÃO NA NAVALHA, <br><span class="text-gold">ESTILO QUE MARCA.</span>
                </h1>
                <p class="lead text-secondary mb-4 mx-auto" style="max-width: 650px;">
                    <?= htmlspecialchars($empresa['slogan'] ?? 'Tradição clássica, navalha afiada e corte moderno. Reserve seu horário em segundos com confirmação imediata.') ?>
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold btn-lg px-4 py-3">
                        <i class="bi bi-calendar-check fs-5"></i> Agendar Atendimento
                    </a>
                    <a href="#servicos" class="btn btn-outline-gold btn-lg px-4 py-3">
                        <i class="bi bi-card-list fs-5"></i> Ver Tabela de Preços
                    </a>
                </div>

                <!-- Vantagens Rápidas -->
                <div class="row g-3 mt-5 pt-3 border-top border-secondary border-opacity-25 text-start">
                    <div class="col-md-4 col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon"><i class="bi bi-qr-code"></i></div>
                            <div>
                                <h6 class="text-white mb-0 fw-bold">PIX Instantâneo</h6>
                                <small class="text-secondary">QR Code dinâmico com recibo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                            <div>
                                <h6 class="text-white mb-0 fw-bold">Pague no Balcão</h6>
                                <small class="text-secondary">Opção de pagar para a recepção</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
                            <div>
                                <h6 class="text-white mb-0 fw-bold">Comprovante Digital</h6>
                                <small class="text-secondary">Salvo no seu app para download</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Portais de Acesso -->
<section class="py-5" style="background: #111319;">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-gold fw-bold text-uppercase" style="letter-spacing: 1px;">Acessos do Estabelecimento</span>
            <h2 class="text-white mt-1">Ambientes do Sistema</h2>
            <p class="text-secondary">Acesse o portal dedicado para o seu perfil</p>
        </div>

        <div class="row g-4">
            <!-- Card Cliente -->
            <div class="col-lg-3 col-md-6">
                <div class="barber-card h-100 d-flex flex-column text-center p-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 65px; height: 65px;">
                        <i class="bi bi-person-fill fs-2"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Cliente</h5>
                    <p class="text-secondary small flex-grow-1">
                        Escolha seu corte, agende horários com seu barbeiro favorito e acesse seus comprovantes de pagamento.
                    </p>
                    <a href="<?= BASE_URL ?>/login.php?perfil=cliente" class="btn btn-outline-gold w-100 mt-2">
                        Acessar Portal <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Card Barbeiro -->
            <div class="col-lg-3 col-md-6">
                <div class="barber-card h-100 d-flex flex-column text-center p-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 65px; height: 65px;">
                        <i class="bi bi-scissors fs-2"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Barbeiro</h5>
                    <p class="text-secondary small flex-grow-1">
                        Visualize os agendamentos atribuídos, cortes escolhidos, gerencie status e solicite avaliações dos clientes.
                    </p>
                    <a href="<?= BASE_URL ?>/login.php?perfil=barbeiro" class="btn btn-outline-gold w-100 mt-2">
                        Painel do Barbeiro <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Card Recepção -->
            <div class="col-lg-3 col-md-6">
                <div class="barber-card h-100 d-flex flex-column text-center p-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 65px; height: 65px;">
                        <i class="bi bi-receipt fs-2"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Recepção</h5>
                    <p class="text-secondary small flex-grow-1">
                        Controle o fluxo de chegada dos clientes, receba pagamentos no balcão e gerencie a agenda do dia.
                    </p>
                    <a href="<?= BASE_URL ?>/login.php?perfil=recepcao" class="btn btn-outline-gold w-100 mt-2">
                        Acessar Recepção <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Card Dono -->
            <div class="col-lg-3 col-md-6">
                <div class="barber-card h-100 d-flex flex-column text-center p-4">
                    <div class="stat-icon mx-auto mb-3" style="width: 65px; height: 65px;">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Administração</h5>
                    <p class="text-secondary small flex-grow-1">
                        Gestão da empresa, cadastro de equipe, comissões, tabela de preços e pagamento dos barbeiros via PIX.
                    </p>
                    <a href="<?= BASE_URL ?>/login.php?perfil=dono" class="btn btn-outline-gold w-100 mt-2">
                        Painel de Gestão <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Seção Tabela de Serviços e Preços -->
<section id="servicos" class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-gold fw-bold text-uppercase" style="letter-spacing: 1px;">Cardápio de Cuidados</span>
            <h2 class="text-white mt-1">Tabela de Procedimentos & Preços</h2>
            <p class="text-secondary">Escolha o serviço ideal para o seu estilo</p>
        </div>

        <div class="row g-4">
            <?php foreach ($servicos as $servico): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="barber-card h-100 d-flex flex-column justify-content-between p-4 position-relative">
                        <?php if ($servico['destaque']): ?>
                            <span class="position-absolute top-0 end-0 bg-gold text-dark fw-bold px-3 py-1 rounded-bl small" style="border-top-right-radius: 12px; border-bottom-left-radius: 12px; font-size: 0.75rem;">
                                MAIS PEDIDO
                            </span>
                        <?php endif; ?>

                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="stat-icon">
                                    <i class="bi <?= htmlspecialchars($servico['icone'] ?? 'bi-scissors') ?>"></i>
                                </div>
                                <div>
                                    <h5 class="text-white mb-0 fw-bold"><?= htmlspecialchars($servico['nome']) ?></h5>
                                    <small class="text-muted"><i class="bi bi-stopwatch me-1"></i> Aprox. <?= (int)$servico['tempo_minutos'] ?> min</small>
                                </div>
                            </div>
                            <p class="text-secondary small mb-4">
                                <?= htmlspecialchars($servico['descricao']) ?>
                            </p>
                        </div>

                        <div class="pt-3 border-top border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small d-block">Valor do serviço</span>
                                <span class="fs-4 fw-bold text-gold">R$ <?= number_format($servico['preco'], 2, ',', '.') ?></span>
                            </div>
                            <a href="<?= BASE_URL ?>/cliente/agendar.php?servico_id=<?= $servico['id'] ?>" class="btn btn-gold btn-sm px-3">
                                Agendar <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Equipe de Barbeiros -->
<section class="py-5" style="background: #111319;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-gold fw-bold text-uppercase" style="letter-spacing: 1px;">Mestres da Lâmina</span>
            <h2 class="text-white mt-1">Nossos Barbeiros Profissionais</h2>
            <p class="text-secondary">Especialistas prontos para elevar sua autoestima e visual</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($barbeiros as $b): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="barber-card text-center p-4">
                        <div class="position-relative d-inline-block mb-3">
                            <div class="rounded-circle border border-warning p-1 d-inline-block" style="border-color: var(--gold) !important;">
                                <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center" style="width: 90px; height: 90px; font-size: 2.5rem; color: var(--gold);">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                        </div>
                        <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($b['nome']) ?></h5>
                        <p class="text-gold small mb-2"><?= htmlspecialchars($b['especialidade'] ?? 'Barbeiro Especialista') ?></p>
                        
                        <div class="text-warning small mb-3">
                            <?php 
                                $media = round((float)$b['media_estrelas']);
                                for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="bi <?= ($i <= $media) ? 'bi-star-fill text-warning' : 'bi-star text-secondary' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-secondary ms-1">(<?= number_format($b['media_estrelas'], 1) ?>)</span>
                        </div>

                        <a href="<?= BASE_URL ?>/cliente/agendar.php?barbeiro_id=<?= $b['id'] ?>" class="btn btn-outline-gold btn-sm w-100">
                            Agendar com <?= htmlspecialchars(explode(' ', $b['nome'])[0]) ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Avaliações dos Clientes -->
<?php if (!empty($avaliacoes)): ?>
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-gold fw-bold text-uppercase" style="letter-spacing: 1px;">Satisfação Garantida</span>
            <h2 class="text-white mt-1">O que nossos clientes dizem</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($avaliacoes as $av): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="barber-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="text-warning mb-2">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= ($s <= $av['estrelas']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-light fst-italic mb-3">
                                "<?= htmlspecialchars($av['comentario']) ?>"
                            </p>
                        </div>
                        <div class="border-top border-secondary border-opacity-25 pt-2">
                            <strong class="text-white d-block"><?= htmlspecialchars($av['cliente_nome']) ?></strong>
                            <small class="text-gold">Atendido por <?= htmlspecialchars($av['barbeiro_nome']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sobre & Localização -->
<section id="sobre" class="py-5" style="background: #0d0f14; border-top: 1px solid rgba(197, 160, 89, 0.15);">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-gold fw-bold text-uppercase" style="letter-spacing: 1px;">Sobre Nós</span>
                <h2 class="text-white mt-1 mb-4">A Essência do Corte na Navalha</h2>
                <p class="text-secondary leading-relaxed">
                    Nossa barbearia une a tradição dos clássicos barbeiros de outrora com a inovação, conforto e pontualidade que o homem moderno exige. Desde o atendimento acolhedor com café expresso até o acabamento perfeito na navalha e toalha quente, cada minuto aqui é dedicado ao seu bem-estar.
                </p>
                <div class="row g-3 mt-2">
                    <div class="col-6">
                        <div class="border border-secondary border-opacity-25 p-3 rounded">
                            <h4 class="text-gold fw-bold mb-0">100%</h4>
                            <small class="text-secondary">Pontualidade nos agendamentos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border border-secondary border-opacity-25 p-3 rounded">
                            <h4 class="text-gold fw-bold mb-0">Navalha</h4>
                            <small class="text-secondary">Lâminas descartáveis & higiene</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="barber-card p-4">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-geo-alt-fill text-gold me-2"></i>Venha nos Visitar</h5>
                    <p class="text-secondary mb-3"><?= htmlspecialchars($empresa['endereco'] ?? 'São Paulo - SP') ?></p>
                    <p class="text-secondary mb-3"><i class="bi bi-telephone text-gold me-2"></i><?= htmlspecialchars($empresa['telefone'] ?? '(11) 98765-4321') ?></p>
                    <p class="text-secondary mb-4"><i class="bi bi-envelope text-gold me-2"></i><?= htmlspecialchars($empresa['email'] ?? 'contato@cortenanavalha.com.br') ?></p>
                    
                    <a href="<?= BASE_URL ?>/cliente/agendar.php" class="btn btn-gold w-100 py-3">
                        <i class="bi bi-calendar2-check-fill me-2"></i> Agende seu Horário Agora
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
