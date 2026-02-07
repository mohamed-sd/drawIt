    </main>

    <!-- Footer -->
    <footer class="footer bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="fw-bold"><i class="fas fa-palette"></i> DrawIt</h5>
                    <p>منصة مسابقات الرسم - حيث يلتقي الإبداع بالموهبة</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold">روابط سريعة</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/about.php" class="text-white-50 text-decoration-none">عن المسابقة</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/rules.php" class="text-white-50 text-decoration-none">القواعد والشروط</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/prizes.php" class="text-white-50 text-decoration-none">الجوائز</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/contact.php" class="text-white-50 text-decoration-none">اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold">معلومات التواصل</h6>
                    <ul class="list-unstyled text-white-50">
                        <li><i class="fas fa-envelope"></i> info@drawit.com</li>
                        <li><i class="fas fa-phone"></i> +966 50 123 4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> الرياض، المملكة العربية السعودية</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-white">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> DrawIt. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if(isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
