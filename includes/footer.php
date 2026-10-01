</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <a class="navbar-brand" href="<?= e(app_url()) ?>"><span class="brand-mark"><i class="fa-solid fa-location-dot"></i></span><span>AR <strong>TOURISM</strong></span></a>
        <?php if (($activePage ?? '') === 'home'): ?><div class="footer-qr"><div class="footer-qr-code" data-ar-qr data-ar-path="<?= e(app_url('ar.php')) ?>" aria-label="QR code to open the AR experience"></div><span class="footer-qr-label">SCAN TO START AR EXPERIENCE<span class="footer-qr-url"><?= e(app_url('ar.php')) ?></span></span></div><?php endif; ?>
        <p>Discover places through the stories that make them matter.</p>
        <span class="footer-copy">&copy; <?= date('Y') ?> AR Tourism Explorer</span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script src="<?= e(app_url('assets/js/qr.js')) ?>" defer></script>
</body>
</html>
