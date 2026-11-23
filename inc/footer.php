</main>
<footer class="foot">
  <div class="wrap">
    <div>
      <strong><?= h(cfg('store_name')) ?></strong>
      <p><?= h(cfg('tagline')) ?></p>
    </div>
    <div>
      <p>📧 <a href="mailto:<?= h(cfg('orders_email')) ?>"><?= h(cfg('orders_email')) ?></a></p>
      <?php if (cfg('phone')): ?><p>📞 <a href="https://wa.me/<?= h(cfg('phone')) ?>">WhatsApp us</a></p><?php endif; ?>
      <?php if (cfg('address')): ?><p>📍 <?= h(cfg('address')) ?></p><?php endif; ?>
    </div>
  </div>
  <p class="copy">© <?= date('Y') ?> <?= h(cfg('store_name')) ?></p>
</footer>
</body>
</html>
