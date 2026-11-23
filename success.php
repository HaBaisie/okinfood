<?php
require __DIR__ . '/inc/bootstrap.php';
$ref = $_SESSION['last_order'] ?? null;
if (!$ref) redirect('index.php');
$st = db()->prepare('SELECT * FROM orders WHERE ref=?');
$st->execute([$ref]);
$o = $st->fetch();
$title = 'Order placed';
require __DIR__ . '/inc/header.php';
?>
<div class="done">
  <div class="tick">✅</div>
  <h1>Thank you, <?= h($o['name'] ?? '') ?>!</h1>
  <p>Your order <b><?= h($ref) ?></b> has been received. We'll contact you on <b><?= h($o['phone'] ?? '') ?></b> to confirm.</p>
  <?php if (($o['payment'] ?? '') === 'transfer'): $b = cfg('bank'); ?>
    <div class="flash">Please transfer <b><?= naira($o['total']) ?></b>
      <?php if ($b['account']): ?>to <b><?= h($b['name']) ?> · <?= h($b['account']) ?></b> (<?= h($b['holder']) ?>)<?php endif; ?>
      using <b><?= h($ref) ?></b> as the reference.</div>
  <?php endif; ?>
  <a class="btn" href="shop.php">Continue shopping</a>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
