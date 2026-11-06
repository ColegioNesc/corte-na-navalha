</main>

<!-- Modal Global de Pagamento PIX e QR Code Dinâmico com Escuta -->
<?php require_once __DIR__ . '/modal_qrcode.php'; ?>

<!-- Footer da Barbearia -->
<footer class="mt-auto py-5" style="background: #07080b; border-top: 2px solid rgba(220, 38, 38, 0.3);">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Coluna 1: Sobre & Logo -->
            <div class="col-lg-4 col-md-6">
                <div class="mb-3">
                    <?php 
                        $logoTamanho = 'normal';
                        $mostrarTexto = true;
                        require __DIR__ . '/logo.php'; 
                    ?>
                </div>
                <p class="text-secondary small">
                    <?= htmlspecialchars($empresa['slogan'] ?? 'Tradição clássica, navalha afiada e corte moderno.') ?>
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-danger fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-danger fs-5" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="text-danger fs-5" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            <!-- Coluna 2: Horários & Contato -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">
                    <i class="bi bi-clock-history text-danger me-1"></i> Atendimento
                </h6>
                <ul class="list-unstyled text-secondary small mb-0">
                    <li class="mb-2"><i class="bi bi-clock me-2 text-danger"></i> Terça a Sábado: 08:00 às 20:00</li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-danger"></i> <?= htmlspecialchars($empresa['endereco'] ?? 'São Paulo - SP') ?></li>
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-danger"></i> <?= htmlspecialchars($empresa['telefone'] ?? '(11) 98765-4321') ?></li>
                    <li><i class="bi bi-envelope me-2 text-danger"></i> <?= htmlspecialchars($empresa['email'] ?? 'contato@cortenanavalha.com.br') ?></li>
                </ul>
            </div>

            <!-- Coluna 3: Portais de Acesso -->
            <div class="col-lg-4 col-md-12">
                <h6 class="text-white text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">
                    <i class="bi bi-shield-lock text-danger me-1"></i> Acessos da Barbearia
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/login.php?perfil=cliente" class="btn btn-sm btn-outline-red">Área do Cliente</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=barbeiro" class="btn btn-sm btn-outline-red">Portal do Barbeiro</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=recepcao" class="btn btn-sm btn-outline-red">Recepção</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=dono" class="btn btn-sm btn-outline-red">Gestão da Barbearia</a>
                </div>
                <div class="mt-3">
                    <span class="badge bg-dark border border-danger border-opacity-50 text-light p-2">
                        <i class="bi bi-qr-code text-danger me-1"></i> Pagamentos instantâneos via PIX ou Presencial
                    </span>
                </div>
            </div>
        </div>

        <div class="border-top border-secondary border-opacity-25 pt-3 text-center text-secondary small">
            <p class="mb-0">&copy; <?= date('Y') ?> Corte na Navalha Barbearia. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Script Oficial -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
