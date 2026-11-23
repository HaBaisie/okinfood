<?php
// expects $title (optional)
$pageTitle = ($title ?? 'Home') . ' · ' . cfg('store_name');
$flash = flash();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h(cfg('store_name')) ?> - order groceries online and get them delivered.">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div class="wrap bar">
    <a class="logo" href="index.php">🛒 <span>Okin<b>Food</b></span></a>
    <form class="search" action="shop.php" method="get">
      <input type="search" name="q" placeholder="Search rice, oil, milk…" value="<?= h($_GET['q'] ?? '') ?>">
      <button aria-label="Search">Search</button>
    </form>
    <a class="cartlink" href="cart.php">Cart <span class="badge"><?= cart_count() ?></span></a>
  </div>
  <nav class="wrap cats">
    <a href="shop.php">All</a>
    <?php foreach (categories() as $c): ?>
      <a href="shop.php?cat=<?= urlencode($c['category']) ?>"><?= h($c['category']) ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<?php if ($flash): ?><div class="wrap"><div class="flash"><?= h($flash) ?></div></div><?php endif; ?>
<main class="wrap">
