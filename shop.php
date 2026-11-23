<?php
require __DIR__ . '/inc/bootstrap.php';
$q = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['cat'] ?? ''));
$sql = 'SELECT * FROM products WHERE active=1';
$args = [];
if ($cat !== '') { $sql .= ' AND category=?'; $args[] = $cat; }
if ($q !== '') { $sql .= ' AND (name LIKE ? OR category LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
$sql .= ' ORDER BY category, name';
$st = db()->prepare($sql);
$st->execute($args);
$products = $st->fetchAll();
$title = $cat ?: ($q ? "Search: $q" : 'Shop');
require __DIR__ . '/inc/header.php';
?>
<h1><?= h($cat ?: ($q ? 'Results for “' . $q . '”' : 'All products')) ?></h1>
<p class="muted"><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></p>
<?php if (!$products): ?>
  <p>No products found. <a href="shop.php">Browse everything</a>.</p>
<?php else: ?>
  <div class="grid"><?php foreach ($products as $p) echo product_card($p); ?></div>
<?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
