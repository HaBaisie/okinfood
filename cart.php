<?php
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $cart = cart();
    if ($action === 'add' && $id) {
        $exists = db()->prepare('SELECT 1 FROM products WHERE id=? AND active=1');
        $exists->execute([$id]);
        if ($exists->fetchColumn()) {
            $cart[$id] = min(99, ($cart[$id] ?? 0) + 1);
            flash('Added to your cart.');
        }
        $_SESSION['cart'] = $cart;
        $back = (string)($_POST['back'] ?? 'shop.php');
        // only allow local relative redirects
        redirect(preg_match('~^[a-zA-Z0-9_./?=&%+-]+$~', $back) && !str_contains($back, '//') ? ltrim($back, '/') : 'shop.php');
    }
    if ($action === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $pid => $qty) {
            $qty = max(0, min(99, (int)$qty));
            if ($qty === 0) unset($cart[(int)$pid]); else $cart[(int)$pid] = $qty;
        }
    }
    if ($action === 'remove') unset($cart[$id]);
    if ($action === 'clear') $cart = [];
    $_SESSION['cart'] = $cart;
    redirect('cart.php');
}

$title = 'Your cart';
$lines = cart_lines();
$subtotal = cart_subtotal($lines);
require __DIR__ . '/inc/header.php';
?>
<h1>Your cart</h1>
<?php if (!$lines): ?>
  <p>Your cart is empty. <a class="btn" href="shop.php">Start shopping</a></p>
<?php else: ?>
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="update">
  <div class="tablewrap"><table class="tbl">
    <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lines as $l): ?>
      <tr>
        <td><span class="emoji sm"><?= h($l['emoji']) ?></span> <b><?= h($l['name']) ?></b><br><small class="muted"><?= h($l['unit']) ?></small></td>
        <td><?= naira($l['price']) ?></td>
        <td><input class="qty" type="number" name="qty[<?= (int)$l['id'] ?>]" min="0" max="99" value="<?= (int)$l['qty'] ?>"></td>
        <td><?= naira($l['line']) ?></td>
        <td><button class="link" form="rm<?= (int)$l['id'] ?>">Remove</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="cartfoot">
    <button class="btn ghost">Update cart</button>
    <div class="sum">
      <p>Subtotal <b><?= naira($subtotal) ?></b></p>
      <small class="muted">Delivery calculated at checkout</small>
      <a class="btn" href="checkout.php">Checkout →</a>
    </div>
  </div>
</form>
<?php foreach ($lines as $l): ?>
  <form id="rm<?= (int)$l['id'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"></form>
<?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
