<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $type = $_POST['type'];
    $quantity = (int)$_POST['quantity'];
    $reason = trim($_POST['reason']);
    $date = $_POST['adjustment_date'];

    $pdo->prepare("INSERT INTO stock_adjustments (product_id, type, quantity, reason, adjustment_date, created_by) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$product_id, $type, $quantity, $reason, $date, $_SESSION['user_id']]);

    if($type === 'add') {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$quantity, $product_id]);
    } else {
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$quantity, $product_id]);
    }

    header("Location: index.php?msg=adjusted"); exit();
}

$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Adjustment | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 600px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { height: 80px; resize: vertical; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
        .current-stock { background: #1e293b; border-radius: 8px; padding: 12px 16px; margin-top: 8px; font-size: 13px; color: #94a3b8; display: none; }
        .current-stock span { color: #38bdf8; font-weight: 700; font-size: 16px; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Stock Adjustments</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>New Stock Adjustment</h2>
    <form method="POST">
        <div class="form-group">
            <label>Product *</label>
            <select name="product_id" required onchange="showStock(this)">
                <option value="">-- Select Product --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-stock="<?php echo $p['stock']; ?>"><?php echo htmlspecialchars($p['name']); ?> — Stock: <?php echo $p['stock']; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="current-stock" id="stock-display">Current Stock: <span id="stock-val">0</span></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Adjustment Type *</label>
                <select name="type" required>
                    <option value="add">➕ Add Stock</option>
                    <option value="subtract">➖ Remove Stock</option>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
        </div>
        <div class="form-group">
            <label>Reason</label>
            <textarea name="reason" placeholder="e.g. Damaged goods, Stock count correction, Opening stock"></textarea>
        </div>
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="adjustment_date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Adjustment</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<script>
function showStock(select) {
    const stock = select.options[select.selectedIndex].dataset.stock || 0;
    document.getElementById('stock-val').textContent = stock;
    document.getElementById('stock-display').style.display = 'block';
}
</script>
</body>
</html>