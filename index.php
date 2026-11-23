<?php
require __DIR__ . '/inc/bootstrap.php';
$title = 'Home';
$featured = db()->query('SELECT * FROM products WHERE active=1 AND featured=1 ORDER BY id LIMIT 12')->fetchAll();
require __DIR__ . '/inc/header.php';
?>
<section class="hero">
  <div>
    <h1>Fresh groceries,<br>delivered to your door.</h1>
    <p>Rice, oil, provisions, fresh produce and more. Order online and we'll bring it to you, or pick it up in store.</p>
    <a class="btn" href="shop.php">Start shopping</a>
    <a class="btn ghost" href="#featured">Today's picks</a>
  </div>
  <div class="hero-art" aria-hidden="true">🥬🍅🍚🥛🍞</div>
</section>

<section class="perks">
  <div>🚚 <b>Home delivery</b><span>Free over <?= naira(cfg('free_delivery_over')) ?></span></div>
  <div>💵 <b>Pay on delivery</b><span>Or by bank transfer</span></div>
  <div>🥇 <b>Quality guaranteed</b><span>Fresh, genuine products</span></div>
</section>

<h2>Shop by category</h2>
<div class="catgrid">
  <?php foreach (categories() as $c): ?>
    <a class="cat" href="shop.php?cat=<?= urlencode($c['category']) ?>">
      <span class="emoji"><?= h($c['emoji']) ?></span><b><?= h($c['category']) ?></b><small><?= (int)$c['n'] ?> items</small>
    </a>
  <?php endforeach; ?>
</div>

<h2 id="featured">Featured products</h2>
<div class="grid">
  <?php foreach ($featured as $p) echo product_card($p); ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
