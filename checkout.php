<?php
require __DIR__ . '/inc/bootstrap.php';

$lines = cart_lines();
if (!$lines) { flash('Your cart is empty.'); redirect('cart.php'); }
$subtotal = cart_subtotal($lines);
$errors = [];
$f = ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'method' => 'delivery', 'payment' => 'cod', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim((string)($_POST[$k] ?? $f[$k]));
    if (!in_array($f['method'], ['delivery', 'pickup'], true)) $f['method'] = 'delivery';
    if (!in_array($f['payment'], ['cod', 'transfer'], true)) $f['payment'] = 'cod';

    if (mb_strlen($f['name']) < 2) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^[0-9+\s-]{7,20}$/', $f['phone'])) $errors[] = 'Please enter a valid phone number.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'That email address looks invalid.';
    if ($f['method'] === 'delivery' && mb_strlen($f['address']) < 6) $errors[] = 'Please enter your delivery address.';
    // honeypot
    if (!empty($_POST['website'])) $errors[] = 'Submission rejected.';

    if (!$errors) {
        $fee = delivery_fee($subtotal, $f['method']);
        $total = $subtotal + $fee;
        $ref = 'OKF-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $now = date('Y-m-d H:i');
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO orders(ref,name,phone,email,address,method,payment,note,subtotal,delivery_fee,total,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$ref, $f['name'], $f['phone'], $f['email'], $f['address'], $f['method'], $f['payment'], $f['note'], $subtotal, $fee, $total, $now]);
        $oid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO order_items(order_id,product_id,name,price,qty) VALUES(?,?,?,?,?)');
        $items = [];
        foreach ($lines as $l) {
            $ins->execute([$oid, $l['id'], $l['name'] . ($l['unit'] ? ' (' . $l['unit'] . ')' : ''), $l['price'], $l['qty']]);
            $items[] = ['name' => $l['name'] . ($l['unit'] ? ' (' . $l['unit'] . ')' : ''), 'price' => $l['price'], 'qty' => $l['qty']];
        }
        $pdo->commit();

        $order = ['ref' => $ref, 'name' => $f['name'], 'phone' => $f['phone'], 'email' => $f['email'], 'address' => $f['address'],
            'method' => $f['method'], 'payment' => $f['payment'], 'note' => $f['note'],
            'subtotal' => $subtotal, 'delivery_fee' => $fee, 'total' => $total, 'created_at' => $now];

        // 1) the store gets every order
        $sent = send_mail((string)cfg('orders_email'), "New order $ref · " . naira($total) . " · {$f['name']}",
            order_email_html($order, $items, true), $f['email'] ?: null);
        // 2) customer confirmation (if they gave an email)
        if ($f['email'] !== '') {
            send_mail($f['email'], 'Your order ' . $ref . ' - ' . cfg('store_name'),
                order_email_html($order, $items, false), (string)cfg('orders_email'));
        }
        if (!$sent) error_log("Order $ref saved but store email failed");

        $_SESSION['cart'] = [];
        $_SESSION['last_order'] = $ref;
        redirect('success.php');
    }
}

$fee = delivery_fee($subtotal, $f['method']);
$title = 'Checkout';
require __DIR__ . '/inc/header.php';
?>
<h1>Checkout</h1>
<?php foreach ($errors as $e): ?><div class="flash err"><?= h($e) ?></div><?php endforeach; ?>
<div class="checkout">
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <label>Full name<input name="name" required value="<?= h($f['name']) ?>" autocomplete="name"></label>
    <label>Phone number<input name="phone" type="tel" required value="<?= h($f['phone']) ?>" autocomplete="tel"></label>
    <label>Email (for your receipt)<input name="email" type="email" value="<?= h($f['email']) ?>" autocomplete="email"></label>

    <fieldset><legend>How do you want your order?</legend>
      <label class="opt"><input type="radio" name="method" value="delivery" <?= $f['method'] === 'delivery' ? 'checked' : '' ?>> Home delivery</label>
      <label class="opt"><input type="radio" name="method" value="pickup" <?= $f['method'] === 'pickup' ? 'checked' : '' ?>> Pick up in store (free)</label>
    </fieldset>
    <label>Delivery address<textarea name="address" rows="3" placeholder="House number, street, area, city"><?= h($f['address']) ?></textarea></label>

    <fieldset><legend>Payment</legend>
      <label class="opt"><input type="radio" name="payment" value="cod" <?= $f['payment'] === 'cod' ? 'checked' : '' ?>> Pay on delivery / pickup</label>
      <label class="opt"><input type="radio" name="payment" value="transfer" <?= $f['payment'] === 'transfer' ? 'checked' : '' ?>> Bank transfer</label>
    </fieldset>
    <label>Order note (optional)<textarea name="note" rows="2"><?= h($f['note']) ?></textarea></label>
    <button class="btn big">Place order</button>
  </form>

  <aside class="summary">
    <h3>Order summary</h3>
    <?php foreach ($lines as $l): ?>
      <p class="li"><span><?= (int)$l['qty'] ?> × <?= h($l['name']) ?></span><b><?= naira($l['line']) ?></b></p>
    <?php endforeach; ?>
    <hr>
    <p class="li"><span>Subtotal</span><b><?= naira($subtotal) ?></b></p>
    <p class="li"><span>Delivery</span><b><?= $fee ? naira($fee) : 'Free' ?></b></p>
    <p class="li total"><span>Total</span><b><?= naira($subtotal + $fee) ?></b></p>
    <small class="muted">Delivery fee shown for home delivery is recalculated when you submit.</small>
  </aside>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
