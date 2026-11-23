<?php
require __DIR__ . '/../inc/bootstrap.php';
require_admin();

function save_upload(): ?string {
    if (empty($_FILES['image']['tmp_name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES['image']['size'] > 3 * 1024 * 1024) return null;
    $info = @getimagesize($_FILES['image']['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
    if (!$ext) return null;
    $dir = ROOT . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], "$dir/$name");
    return 'uploads/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['action'] ?? '';
    $data = [
        trim((string)($_POST['name'] ?? '')), trim((string)($_POST['category'] ?? '')),
        max(0, (int)($_POST['price'] ?? 0)), trim((string)($_POST['unit'] ?? '')),
        trim((string)($_POST['emoji'] ?? '')) ?: '🛒', isset($_POST['featured']) ? 1 : 0, isset($_POST['active']) ? 1 : 0,
    ];
    if ($a === 'add' && $data[0] && $data[1]) {
        $img = save_upload() ?? '';
        db()->prepare('INSERT INTO products(name,category,price,unit,emoji,featured,active,image) VALUES(?,?,?,?,?,?,?,?)')->execute([...$data, $img]);
        flash('Product added.');
    } elseif ($a === 'save' && $data[0] && $data[1]) {
        $id = (int)$_POST['id'];
        db()->prepare('UPDATE products SET name=?,category=?,price=?,unit=?,emoji=?,featured=?,active=? WHERE id=?')->execute([...$data, $id]);
        if ($img = save_upload()) db()->prepare('UPDATE products SET image=? WHERE id=?')->execute([$img, $id]);
        flash('Saved.');
    } elseif ($a === 'delete') {
        db()->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_POST['id']]);
        flash('Deleted.');
    }
    redirect('products.php');
}
$flash = flash();
$products = db()->query('SELECT * FROM products ORDER BY category, name')->fetchAll();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Products · Admin</title><link rel="stylesheet" href="../assets/style.css"><meta name="robots" content="noindex"></head>
<body><main class="wrap" style="padding-top:24px">
<div class="adminbar"><h1>Products</h1><nav><a href="index.php">Orders</a> <a href="products.php">Products</a> <a href="../index.php" target="_blank">View site</a></nav></div>
<?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>

<h2>Add product</h2>
<form method="post" enctype="multipart/form-data" class="prodrow">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <input name="name" placeholder="Name" required><input name="category" placeholder="Category" list="cats" required>
  <input name="price" type="number" placeholder="Price ₦" required><input name="unit" placeholder="Unit (e.g. 5kg)">
  <input name="emoji" placeholder="Emoji" size="3"><input type="file" name="image" accept="image/*">
  <label><input type="checkbox" name="featured"> Featured</label><input type="hidden" name="active" value="1">
  <button class="btn small">Add</button>
</form>
<datalist id="cats"><?php foreach (categories() as $c): ?><option value="<?= h($c['category']) ?>"><?php endforeach; ?></datalist>

<h2>All products (<?= count($products) ?>)</h2>
<?php foreach ($products as $p): ?>
  <form method="post" enctype="multipart/form-data" class="prodrow">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <input name="name" value="<?= h($p['name']) ?>" required><input name="category" value="<?= h($p['category']) ?>" list="cats" required>
    <input name="price" type="number" value="<?= (int)$p['price'] ?>"><input name="unit" value="<?= h($p['unit']) ?>">
    <input name="emoji" value="<?= h($p['emoji']) ?>" size="3"><input type="file" name="image" accept="image/*">
    <label><input type="checkbox" name="featured" <?= $p['featured'] ? 'checked' : '' ?>> Featured</label>
    <label><input type="checkbox" name="active" <?= $p['active'] ? 'checked' : '' ?>> Visible</label>
    <button class="btn small" name="action" value="save">Save</button>
    <button class="link" name="action" value="delete" onclick="return confirm('Delete this product?')">Delete</button>
  </form>
<?php endforeach; ?>
</main></body></html>
