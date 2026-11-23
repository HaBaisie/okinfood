<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$GLOBALS['CFG'] = require ROOT . '/config.php';
date_default_timezone_set('Africa/Lagos');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function cfg(string $k) { return $GLOBALS['CFG'][$k] ?? null; }
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function naira($n): string { return '₦' . number_format((int)$n); }
function redirect(string $url): never { header('Location: ' . $url); exit; }

function base_url(): string {
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// ---------- CSRF + flash ----------
function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Session expired. Please go back and try again.');
    }
}
function flash(?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}

// ---------- Database (SQLite, auto-created) ----------
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(ROOT . '/data')) mkdir(ROOT . '/data', 0755, true);
    $pdo = new PDO('sqlite:' . ROOT . '/data/shop.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products(
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, category TEXT NOT NULL,
        price INTEGER NOT NULL, unit TEXT DEFAULT '', emoji TEXT DEFAULT '🛒', image TEXT DEFAULT '',
        featured INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE IF NOT EXISTS orders(
        id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, name TEXT, phone TEXT, email TEXT,
        address TEXT, method TEXT, payment TEXT, note TEXT,
        subtotal INTEGER, delivery_fee INTEGER, total INTEGER,
        status TEXT DEFAULT 'New', created_at TEXT);
    CREATE TABLE IF NOT EXISTS order_items(
        id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER,
        name TEXT, price INTEGER, qty INTEGER);
    ");
    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) seed($pdo);
    return $pdo;
}

function seed(PDO $pdo): void {
    $rows = [
        // name, category, price, unit, emoji, featured
        ['Premium Long Grain Rice', 'Rice & Grains', 78000, '50kg bag', '🍚', 1],
        ['Parboiled Rice', 'Rice & Grains', 4200, '5kg', '🍚', 0],
        ['Brown Beans (Oloyin)', 'Rice & Grains', 2800, '2kg', '🫘', 1],
        ['White Garri', 'Rice & Grains', 2500, '4kg', '🌾', 0],
        ['Semovita', 'Rice & Grains', 3600, '2kg', '🌾', 0],
        ['Spaghetti', 'Rice & Grains', 1100, '500g', '🍝', 0],
        ['Vegetable Oil', 'Cooking Oils', 9800, '5 litres', '🛢️', 1],
        ['Red Palm Oil', 'Cooking Oils', 3500, '1 litre', '🛢️', 0],
        ['Groundnut Oil', 'Cooking Oils', 4800, '2 litres', '🛢️', 0],
        ['Indomie Chicken Noodles', 'Noodles & Pasta', 9500, 'Carton (40)', '🍜', 1],
        ['Indomie Onion Chicken', 'Noodles & Pasta', 280, 'Single', '🍜', 0],
        ['Macaroni', 'Noodles & Pasta', 1000, '500g', '🍝', 0],
        ['Peak Milk Powder', 'Beverages', 5200, '900g tin', '🥛', 1],
        ['Milo Tin', 'Beverages', 4100, '800g', '☕', 0],
        ['Lipton Tea (25 bags)', 'Beverages', 1300, 'Pack', '🍵', 0],
        ['Bottled Water', 'Beverages', 2400, 'Pack of 12', '💧', 0],
        ['Coca-Cola', 'Beverages', 3600, 'Pack of 12', '🥤', 0],
        ['Sugar', 'Provisions', 2200, '1kg', '🧂', 0],
        ['Iodised Salt', 'Provisions', 500, '500g', '🧂', 0],
        ['Tomato Paste (Gino)', 'Provisions', 2100, 'Pack of 10', '🍅', 1],
        ['Seasoning Cubes (Maggi)', 'Provisions', 1800, 'Box of 50', '🧊', 0],
        ['Sliced Bread', 'Provisions', 1500, 'Large loaf', '🍞', 0],
        ['Fresh Tomatoes', 'Fresh Produce', 3000, 'Basket', '🍅', 1],
        ['Onions', 'Fresh Produce', 2500, '2kg', '🧅', 0],
        ['Plantain', 'Fresh Produce', 2000, 'Bunch', '🍌', 0],
        ['Yam Tubers', 'Fresh Produce', 4500, '3 medium', '🍠', 0],
        ['Frozen Chicken', 'Meat & Fish', 6500, '1.5kg', '🍗', 1],
        ['Titus Fish (Mackerel)', 'Meat & Fish', 7000, '2kg', '🐟', 0],
        ['Eggs', 'Meat & Fish', 3800, 'Crate (30)', '🥚', 0],
        ['Detergent Powder', 'Household', 1900, '1kg', '🧺', 0],
        ['Dishwashing Liquid', 'Household', 1400, '750ml', '🧴', 0],
        ['Toilet Roll', 'Household', 2600, 'Pack of 10', '🧻', 0],
        ['Bath Soap', 'Personal Care', 2400, 'Pack of 6', '🧼', 0],
        ['Toothpaste', 'Personal Care', 900, '120g', '🪥', 0],
    ];
    $st = $pdo->prepare('INSERT INTO products(name,category,price,unit,emoji,featured) VALUES(?,?,?,?,?,?)');
    $pdo->beginTransaction();
    foreach ($rows as $r) $st->execute($r);
    $pdo->commit();
}

function categories(): array {
    return db()->query("SELECT category, COUNT(*) n, MIN(emoji) emoji FROM products WHERE active=1 GROUP BY category ORDER BY MIN(id)")->fetchAll();
}

// ---------- Cart (session) ----------
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_count(): int { return array_sum(cart()); }
function cart_lines(): array {
    $cart = cart();
    if (!$cart) return [];
    $in = implode(',', array_fill(0, count($cart), '?'));
    $st = db()->prepare("SELECT * FROM products WHERE active=1 AND id IN ($in)");
    $st->execute(array_map('intval', array_keys($cart)));
    $lines = [];
    foreach ($st->fetchAll() as $p) {
        $p['qty'] = $cart[$p['id']];
        $p['line'] = $p['qty'] * $p['price'];
        $lines[] = $p;
    }
    return $lines;
}
function cart_subtotal(array $lines): int { return (int)array_sum(array_column($lines, 'line')); }
function delivery_fee(int $subtotal, string $method): int {
    if ($method === 'pickup') return 0;
    return $subtotal >= (int)cfg('free_delivery_over') ? 0 : (int)cfg('delivery_fee');
}

// ---------- Product card ----------
function product_card(array $p): string {
    $img = $p['image']
        ? '<img src="' . h($p['image']) . '" alt="' . h($p['name']) . '" loading="lazy">'
        : '<span class="emoji">' . h($p['emoji']) . '</span>';
    return '<article class="card"><div class="thumb">' . $img . '</div>'
        . '<h3>' . h($p['name']) . '</h3><p class="unit">' . h($p['unit']) . '</p>'
        . '<div class="row"><strong class="price">' . naira($p['price']) . '</strong>'
        . '<form method="post" action="cart.php">' . csrf_field()
        . '<input type="hidden" name="action" value="add"><input type="hidden" name="id" value="' . (int)$p['id'] . '">'
        . '<input type="hidden" name="back" value="' . h($_SERVER['REQUEST_URI'] ?? 'shop.php') . '">'
        . '<button class="btn small">Add</button></form></div></article>';
}

// ---------- Email ----------
function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool {
    $from = (string)cfg('from_email');
    $name = (string)cfg('store_name');
    $smtp = cfg('smtp');
    $headers = [
        'From: ' . mb_encode_mimeheader($name) . " <$from>",
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];
    if ($replyTo) $headers[] = "Reply-To: $replyTo";
    $encSubject = mb_encode_mimeheader($subject, 'UTF-8');

    if (!empty($smtp['host'])) {
        return smtp_send($smtp, $from, $to, $encSubject, $headers, $html);
    }
    return @mail($to, $encSubject, $html, implode("\r\n", $headers), '-f' . $from);
}

function smtp_send(array $s, string $from, string $to, string $subject, array $headers, string $body): bool {
    $prefix = $s['secure'] === 'ssl' ? 'ssl://' : '';
    $fp = @stream_socket_client($prefix . $s['host'] . ':' . $s['port'], $en, $es, 15);
    if (!$fp) { error_log("SMTP connect failed: $es"); return false; }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) { $out .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
        return $out;
    };
    $cmd = function (string $c, string $expect) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        return str_starts_with($r, $expect) ? true : (error_log("SMTP: $c -> $r") ?: false);
    };
    $read();
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!$cmd("EHLO $host", '250')) return false;
    if ($s['secure'] === 'tls') {
        if (!$cmd('STARTTLS', '220')) return false;
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$cmd("EHLO $host", '250')) return false;
    }
    if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode($s['user']), '334') || !$cmd(base64_encode($s['pass']), '235')) return false;
    if (!$cmd("MAIL FROM:<$from>", '250') || !$cmd("RCPT TO:<$to>", '250') || !$cmd('DATA', '354')) return false;
    $msg = "To: $to\r\nSubject: $subject\r\nDate: " . date('r') . "\r\n" . implode("\r\n", $headers) . "\r\n\r\n";
    $msg .= preg_replace('/^\./m', '..', str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body)));
    fwrite($fp, $msg . "\r\n.\r\n");
    $ok = str_starts_with($read(), '250');
    fwrite($fp, "QUIT\r\n"); fclose($fp);
    return $ok;
}

function order_email_html(array $order, array $items, bool $forStore): string {
    $rows = '';
    foreach ($items as $i) {
        $rows .= '<tr><td style="padding:6px;border-bottom:1px solid #eee">' . h($i['name']) . '</td>'
            . '<td style="padding:6px;border-bottom:1px solid #eee;text-align:center">' . (int)$i['qty'] . '</td>'
            . '<td style="padding:6px;border-bottom:1px solid #eee;text-align:right">' . naira($i['price'] * $i['qty']) . '</td></tr>';
    }
    $title = $forStore ? 'New order ' . h($order['ref']) : 'Thank you for your order, ' . h($order['name']);
    $method = $order['method'] === 'pickup' ? 'Store pickup' : 'Home delivery';
    $pay = $order['payment'] === 'transfer' ? 'Bank transfer' : 'Pay on delivery / pickup';
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#222">'
        . '<h2 style="color:#1f7a3a">' . $title . '</h2>'
        . '<p><b>Order ref:</b> ' . h($order['ref']) . '<br><b>Placed:</b> ' . h($order['created_at']) . '</p>'
        . '<table width="100%" cellspacing="0" style="border-collapse:collapse"><tr style="background:#f3f7f4"><th align="left" style="padding:6px">Item</th><th style="padding:6px">Qty</th><th align="right" style="padding:6px">Amount</th></tr>'
        . $rows . '</table>'
        . '<p style="text-align:right">Subtotal: ' . naira($order['subtotal']) . '<br>Delivery: ' . naira($order['delivery_fee'])
        . '<br><b style="font-size:18px">Total: ' . naira($order['total']) . '</b></p><hr>'
        . '<p><b>Customer:</b> ' . h($order['name']) . '<br><b>Phone:</b> ' . h($order['phone'])
        . '<br><b>Email:</b> ' . h($order['email'] ?: '-') . '<br><b>Method:</b> ' . $method
        . '<br><b>Address:</b> ' . nl2br(h($order['address'] ?: '-')) . '<br><b>Payment:</b> ' . $pay
        . '<br><b>Note:</b> ' . nl2br(h($order['note'] ?: '-')) . '</p>';
    if (!$forStore && $order['payment'] === 'transfer' && cfg('bank')['account']) {
        $b = cfg('bank');
        $html .= '<p style="background:#fff8e1;padding:10px"><b>Pay by transfer to:</b><br>' . h($b['name']) . ' · ' . h($b['account']) . ' · ' . h($b['holder']) . '<br>Use <b>' . h($order['ref']) . '</b> as the reference.</p>';
    }
    return $html . '</div>';
}

function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void { if (!is_admin()) redirect('index.php'); }
