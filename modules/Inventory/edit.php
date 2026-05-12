<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$id]);
$p = $product->fetch(PDO::FETCH_ASSOC);

if(!$p) {
    header("Location: index.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $imei_serial = trim($_POST['imei_serial']);
    $purchase_price = (float)$_POST['purchase_price'];
    $mrp_price = (float)$_POST['mrp_price'];
    $sale_price = (float)$_POST['sale_price'];
    $stock = (int)$_POST['stock'];
    $low_stock_alert = (int)$_POST['low_stock_alert'];
    $description = trim($_POST['description']);

    $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, brand=?, model=?, imei_serial=?, purchase_price=?, mrp_price=?, sale_price=?, stock=?, low_stock_alert=?, description=? WHERE id=?");
    $stmt->execute([$name, $category, $brand, $model, $imei_serial, $purchase_price, $mrp_price, $sale_price, $stock, $low_stock_alert, $description, $id]);

    header("Location: index.php?msg=updated");
    exit();
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar {
            background: #1e293b;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 800px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #f1f5f9;
            font-size: 14px;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { resize: vertical; height: 80px; }
        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Inventory</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <h2>Edit Product</h2>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group full">
                <label>Product Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" value="<?php echo htmlspecialchars($p['brand']); ?>">
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" name="model" value="<?php echo htmlspecialchars($p['model']); ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">-- Select Category --</option>
                    <?php foreach($cats as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $p['category'] === $cat['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>IMEI / Serial Number</label>
                <input type="text" name="imei_serial" value="<?php echo htmlspecialchars($p['imei_serial']); ?>">
            </div>
            <div class="form-group">
                <label>Purchase Price (PKR)</label>
                <input type="number" name="purchase_price" value="<?php echo $p['purchase_price']; ?>" step="0.01">
            </div>
            <div class="form-group">
                <label>MRP Price (PKR)</label>
                <input type="number" name="mrp_price" value="<?php echo $p['mrp_price']; ?>" step="0.01">
            </div>
            <div class="form-group">
                <label>Wholesale / Sale Price (PKR)</label>
                <input type="number" name="sale_price" value="<?php echo $p['sale_price']; ?>" step="0.01">
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" value="<?php echo $p['stock']; ?>">
            </div>
            <div class="form-group">
                <label>Low Stock Alert At</label>
                <input type="number" name="low_stock_alert" value="<?php echo $p['low_stock_alert']; ?>">
            </div>
            <div class="form-group full">
                <label>Description</label>
                <textarea name="description"><?php echo htmlspecialchars($p['description']); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Product</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>