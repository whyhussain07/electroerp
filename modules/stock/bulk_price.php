<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_ids = $_POST['product_id'];
    $purchase_prices = $_POST['purchase_price'];
    $mrp_prices = $_POST['mrp_price'];
    $sale_prices = $_POST['sale_price'];

    foreach($product_ids as $i => $id) {
        $pdo->prepare("UPDATE products SET purchase_price=?, mrp_price=?, sale_price=? WHERE id=?")
            ->execute([(float)$purchase_prices[$i], (float)$mrp_prices[$i], (float)$sale_prices[$i], (int)$id]);
    }
    header("Location: bulk_price.php?msg=updated"); exit();
}

$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Price Change | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; display: flex; }
        .sidebar { width: 240px; height: 100vh; background: #1e293b; border-right: 1px solid #334155; display: flex; flex-direction: column; position: fixed; left: 0; top: 0; overflow-y: auto; }
        .sidebar-logo { padding: 20px 24px; font-size: 20px; font-weight: 700; color: #38bdf8; border-bottom: 1px solid #334155; }
        .sidebar-logo span { font-size: 13px; color: #64748b; display: block; font-weight: 400; margin-top: 2px; }
        .nav-section { padding: 16px 12px 8px; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 1px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #94a3b8; text-decoration: none; font-size: 14px; border-radius: 8px; margin: 2px 8px; }
        .nav-item:hover, .nav-item.active { background: #0f172a; color: #38bdf8; }
        .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding: 16px; border-top: 1px solid #334155; }
        .sidebar-footer a { display: block; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 14px; border-radius: 8px; }
        .main { margin-left: 240px; flex: 1; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h2 { font-size: 18px; }
        .content { padding: 30px; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        input[type="number"] { padding: 7px 10px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; width: 120px; }
        input[type="number"]:focus { outline: none; border-color: #38bdf8; }
        .btn { padding: 12px 30px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">⚡ ElectroERP<span>Mobile & Electronics</span></div>
    <div class="nav-section">Main</div>
    <a href="../../dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
    <div class="nav-section">Inventory</div>
    <a href="index.php" class="nav-item"><span class="icon">📦</span> Products</a>
    <a href="add.php" class="nav-item"><span class="icon">➕</span> Add Product</a>
    <a href="categories.php" class="nav-item"><span class="icon">🏷️</span> Categories</a>
    <a href="bulk_price.php" class="nav-item active"><span class="icon">💲</span> Bulk Price Change</a>
    <a href="../stock/index.php" class="nav-item"><span class="icon">🔧</span> Stock Adjustment</a>
    <div class="nav-section">Transactions</div>
    <a href="../sales/pos.php" class="nav-item"><span class="icon">🖥️</span> POS</a>
    <a href="../sales/index.php" class="nav-item"><span class="icon">🧾</span> Sales</a>
    <a href="../purchases/index.php" class="nav-item"><span class="icon">📥</span> Purchases</a>
    <a href="../returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <div class="nav-section">Banking</div>
    <a href="../payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="../payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="../expenses/index.php" class="nav-item"><span class="icon">🧾</span> Expenses</a>
    <a href="../cash/index.php" class="nav-item"><span class="icon">🏦</span> Cash Counter</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Bulk Price Change</h2></div>
    <div class="content">
        <?php if(isset($_GET['msg'])): ?>
        <div class="success">✅ All prices updated successfully.</div>
        <?php endif; ?>

        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Current Stock</th>
                        <th>Purchase Price</th>
                        <th>MRP Price</th>
                        <th>Wholesale Price</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($p['name']); ?>
                        <input type="hidden" name="product_id[]" value="<?php echo $p['id']; ?>">
                    </td>
                    <td><?php echo htmlspecialchars($p['brand']); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td><input type="number" name="purchase_price[]" value="<?php echo $p['purchase_price']; ?>" step="0.01"></td>
                    <td><input type="number" name="mrp_price[]" value="<?php echo $p['mrp_price']; ?>" step="0.01"></td>
                    <td><input type="number" name="sale_price[]" value="<?php echo $p['sale_price']; ?>" step="0.01"></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">💾 Save All Price Changes</button>
        </form>
    </div>
</div>
</body>
</html>