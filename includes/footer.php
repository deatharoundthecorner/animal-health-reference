<?php
require_once __DIR__ . '/functions.php';
$footerFeeding = isset($pdo) && $pdo instanceof PDO && feeding_available($pdo);
?>
<footer class="site-footer text-white py-5 mt-5">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-<?= $footerFeeding ? '2' : '3' ?>">
                <h2 class="h6 text-uppercase text-white-50">Explore</h2>
                <ul class="footer-links">
                    <li><a href="animals.php">Animals</a></li>
                    <li><a href="body-parts.php">Anatomy</a></li>
                    <li><a href="diseases.php">Diseases</a></li>
                    <li><a href="symptoms.php">Symptom lookup</a></li>
                    <li><a href="search.php">Search</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-<?= $footerFeeding ? '3' : '3' ?>">
                <h2 class="h6 text-uppercase text-white-50">Animals</h2>
                <ul class="footer-links">
                    <li><a href="animals.php?type=companion">Companion animals</a></li>
                    <li><a href="animals.php?type=livestock">Livestock</a></li>
                    <li><a href="animals.php?type=wildlife">Wildlife</a></li>
                    <li><a href="animals.php?threatened=1">Endangered animals</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="h6 text-uppercase text-white-50">Diseases</h2>
                <ul class="footer-links">
                    <li><a href="diseases.php?zoonotic=1">Zoonotic diseases</a></li>
                    <li><a href="diseases.php?woah=1">WOAH-listed diseases</a></li>
                    <li><a href="diseases.php?pathogen=virus">Viral diseases</a></li>
                    <li><a href="diseases.php?pathogen=bacteria">Bacterial diseases</a></li>
                    <li><a href="diseases.php?pathogen=parasite">Parasitic diseases</a></li>
                </ul>
            </div>
            <?php if ($footerFeeding): ?>
                <div class="col-6 col-md-2">
                    <h2 class="h6 text-uppercase text-white-50">Feeding</h2>
                    <ul class="footer-links">
                        <li><a href="feeding.php">Feeding calculator</a></li>
                        <li><a href="feeds.php">Feed types</a></li>
                        <li><a href="feeds.php?category=green_forage">Fodder and forage</a></li>
                        <li><a href="feeds.php?category=complete_feed">Manufactured feeds</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="col-6 col-md-2">
                <h2 class="h6 text-uppercase text-white-50">Project</h2>
                <ul class="footer-links">
                    <li><a href="about.php">About &amp; data quality</a></li>
                    <li><a href="about.php#sources">Sources &amp; licenses</a></li>
                    <li><a href="admin/login.php">Admin sign-in</a></li>
                </ul>
            </div>
        </div>

        <hr class="border-light opacity-25">
        <p class="small mb-2">
            <strong>Reference information only.</strong>
            This site is not a substitute for professional veterinary advice, diagnosis or treatment. Feeding amounts are general starting points, not a prescription.
            Entries marked “Not yet expert-reviewed” have not been checked by a qualified person. If an animal is unwell, contact a veterinarian.
        </p>
        <p class="small mb-0 text-white-50">
            Open-source project. Code under the MIT license; original data under CC BY 4.0 (third-party sources keep their own terms).
        </p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
