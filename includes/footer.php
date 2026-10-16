</main>

<!-- Footer da Barbearia -->
<footer class="mt-auto py-5" style="background: #090a0d; border-top: 1px solid rgba(197, 160, 89, 0.2);">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Coluna 1: Sobre -->
            <div class="col-lg-4 col-md-6">
                <h5 class="brand-font text-gold mb-3">
                    <span class="fs-4">✂️</span> CORTE NA NAVALHA
                </h5>
                <p class="text-secondary small">
                    <?= htmlspecialchars($empresa['slogan'] ?? 'Tradição, estilo e precisão em cada detalhe.') ?>
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-gold fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-gold fs-5" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="text-gold fs-5" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            <!-- Coluna 2: Horários & Contato -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Atendimento</h6>
                <ul class="list-unstyled text-secondary small mb-0">
                    <li class="mb-2"><i class="bi bi-clock me-2 text-gold"></i> Terça a Sábado: 08:00 às 20:00</li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-gold"></i> <?= htmlspecialchars($empresa['endereco'] ?? 'São Paulo - SP') ?></li>
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-gold"></i> <?= htmlspecialchars($empresa['telefone'] ?? '(11) 98765-4321') ?></li>
                    <li><i class="bi bi-envelope me-2 text-gold"></i> <?= htmlspecialchars($empresa['email'] ?? 'contato@cortenanavalha.com.br') ?></li>
                </ul>
            </div>

            <!-- Coluna 3: Portais de Acesso -->
            <div class="col-lg-4 col-md-12">
                <h6 class="text-white text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Portais de Acesso</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/login.php?perfil=cliente" class="btn btn-sm btn-outline-gold">Área do Cliente</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=barbeiro" class="btn btn-sm btn-outline-gold">Portal do Barbeiro</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=recepcao" class="btn btn-sm btn-outline-gold">Recepção</a>
                    <a href="<?= BASE_URL ?>/login.php?perfil=dono" class="btn btn-sm btn-outline-gold">Gestão da Barbearia</a>
                </div>
                <div class="mt-3">
                    <span class="badge bg-dark border border-secondary text-secondary p-2">
                        <i class="bi bi-qr-code text-gold me-1"></i> Pagamentos instantâneos via PIX ou Presencial
                    </span>
                </div>
            </div>
        </div>

        <div class="border-top border-secondary pt-3 text-center text-secondary small">
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
