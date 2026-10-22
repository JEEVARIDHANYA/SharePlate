<?php $base_prefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : ''; ?>
</main>
<footer class="site-footer"><div class="container footer-grid">
  <div><a class="brand footer-brand" href="<?= e($base_prefix) ?>index.php"><span class="brand-mark">✳</span> SharePlate</a><p>Good food deserves a good destination. Connect surplus meals with people who need them.</p></div>
  <div><h3>Explore</h3><a href="<?= e($base_prefix) ?>available-food.php">Find food</a><a href="<?= e($base_prefix) ?>add-food.php">Donate food</a><a href="<?= e($base_prefix) ?>register.php">Create an account</a></div>
  <div><h3>About</h3><p>Building stronger communities by making food sharing simple, safe, and local.</p><a href="mailto:hello@shareplate.local">hello@shareplate.local</a></div>
</div><div class="container footer-bottom">© <?= date('Y') ?> SharePlate. Share more. Waste less.</div></footer>
</body></html>
