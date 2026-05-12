<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $sale_id = (int)$_POST['sale_id'];
    $quantity = (int)$_POST['quantity'];
    $return_price = (float)$_POST['return_price'];
    $total = $quantity * $return_price;
    $reason = trim($_POST['reason']);
    $return_date = $_POST['return_date'];

    $pdo->prepare("INSERT INTO sale_returns (sale_id, product_id, quantity, return_price, total, reason, return_date) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$sale_id, $product_id, $quantity, $return_price, $total, $reason, $return_date]);

    // Restore stock
    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$quantity, $product_id]);

    header("Location: index.php?msg=added"); exit();
}

$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sales = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Return | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 700px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { height: 80px; resize: vertical; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Returns</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>New Sale Return</h2>
    <form method="POST">
        <div class="form-group">
            <label>Original Sale Invoice</label>
            <select name="sale_id">
                <option value="0">-- Select Sale (Optional) --</option>
                <?php foreach($sales as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['invoice_no']); ?> — <?php echo htmlspecialchars($s['customer_name']); ?> (<?php echo $s['sale_date']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Product *</label>
            <select name="product_id" required onchange="fillPrice(this)">
                <option value="">-- Select Product --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['sale_price']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantity *</label>
            <input type="number" name="quantity" min="1" value="1" required>
        </div>
        <div class="form-group">
            <label>Return Price (PKR) *</label>
            <input type="number" name="return_price" id="return_price" step="0.01" value="0" required>
        </div>
        <div class="form-group">
            <label>Return Date</label>
            <input type="date" name="return_date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label>Reason</label>
            <textarea name="reason" placeholder="e.g. Defective, Wrong item, Customer changed mind"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Return</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<script>
function fillPrice(select) {
    const price = select.options[select.selectedIndex].dataset.price || 0;
    document.getElementById('return_price').value = price;
}
</script>
</body>
</html>