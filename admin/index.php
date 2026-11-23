<?php
require __DIR__ . '/../inc/bootstrap.php';

$statuses = ['New', 'Confirmed', 'Out for delivery', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'login') {
        usleep(400000);
        if (hash_equals((string)cfg('admin_password'), (string)($_POST['password'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
        } else {
            flash('Wrong password.');
        }
        redirect('index.php');
    }
    require_admin();
    if (($_POST['action'] ?? '') === 'status' && in_array($_POST['status'] ?? '', $statuses, true)) {
        db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$_POST['status'], (int)$_POST['id']]);
        redirect('index.php?view=' . (int)$_POST['id']);
    }
    if (($_POST['action'] ?? '') === 'logout') { unset($_SESSION['admin']); redirect('index.php'); }
}

$title = 'Admin';
$flash = flash();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · <?= h(cfg('store_name')) ?></title><link rel="stylesheet" href="../assets/style.css"><meta name="robots" content="noindex"></head>
<body><main class="wrap" style="padding-top:24px">
<?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
<?php if (!is_admin()): ?>
  <form method="post" class="form" style="max-width:360px;margin:60px auto">
    <h1>Admin login</h1><?= csrf_field() ?><input type="hidden" name="action" value="login">
    <label>Password<input type="password" name="password" required autofocus></label>
    <button class="btn big">Sign in</button>
  </form>
<?php else:
  $view = (int)($_GET['view'] ?? 0);
  ?>
  <div class="adminbar">
    <h1><?= h(cfg('store_name')) ?> · Admin</h1>
    <nav><a href="index.php">Orders</a> <a href="products.php">Products</a> <a href="../index.php" target="_blank">View site</a>
      <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="link">Logout</button></form></nav>
  </div>
  <?php if ($view):
    $st = db()->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$view]); $o = $st->fetch();
    $it = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $it->execute([$view]); $items = $it->fetchAll();
    if (!$o) redirect('index.php'); ?>
    <p><a href="index.php">← All orders</a></p>
    <h2>Order <?= h($o['ref']) ?> <span class="pill"><?= h($o['status']) ?></span></h2>
    <div class="checkout">
      <div>
        <div class="tablewrap"><table class="tbl"><thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead><tbody>
          <?php foreach ($items as $i): ?><tr><td><?= h($i['name']) ?></td><td><?= (int)$i['qty'] ?></td><td><?= naira($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <p>Subtotal <?= naira($o['subtotal']) ?> · Delivery <?= naira($o['delivery_fee']) ?> · <b>Total <?= naira($o['total']) ?></b></p>
      </div>
      <aside class="summary">
        <p><b><?= h($o['name']) ?></b><br><a href="tel:<?= h($o['phone']) ?>"><?= h($o['phone']) ?></a><br><?= h($o['email'] ?: '-') ?></p>
        <p><b><?= $o['method'] === 'pickup' ? 'Store pickup' : 'Delivery' ?></b><br><?= nl2br(h($o['address'] ?: '-')) ?></p>
        <p><b>Payment:</b> <?= $o['payment'] === 'transfer' ? 'Bank transfer' : 'Pay on delivery' ?></p>
        <p><b>Note:</b> <?= nl2br(h($o['note'] ?: '-')) ?></p>
        <p class="muted"><?= h($o['created_at']) ?></p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
          <select name="status"><?php foreach ($statuses as $s): ?><option <?= $s === $o['status'] ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select>
          <button class="btn small">Update</button></form>
      </aside>
    </div>
  <?php else:
    $orders = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 200')->fetchAll(); ?>
    <h2>Orders (<?= count($orders) ?>)</h2>
    <div class="tablewrap"><table class="tbl"><thead><tr><th>Ref</th><th>Date</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($orders as $o): ?>
        <tr><td><?= h($o['ref']) ?></td><td><?= h($o['created_at']) ?></td><td><?= h($o['name']) ?><br><small class="muted"><?= h($o['phone']) ?></small></td>
          <td><?= naira($o['total']) ?></td><td><span class="pill"><?= h($o['status']) ?></span></td><td><a href="?view=<?= (int)$o['id'] ?>">Open</a></td></tr>
      <?php endforeach; if (!$orders): ?><tr><td colspan="6">No orders yet.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?php endif; ?>
<?php endif; ?>
</main></body></html>
