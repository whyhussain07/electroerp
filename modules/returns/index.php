<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$returns = $pdo->query("SELECT r.*, p.name as product_name FROM sale_returns r LEFT JOIN products p ON r.product_id = p.id ORDER BY r.return_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returns | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; display: flex; }
        .sidebar { width: 240px; height: 100vh;overflow-y: auto; background: #1e293b; border-right: 1px solid #334155; display: flex; flex-direction: column; position: fixed; left: 0; top: 0; }
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">⚡ ElectroERP<span>Mobile & Electronics</span></div>
    <div class="nav-section">Main</div>
    <a href="../../dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
    <div class="nav-section">Inventory</div>
    <a href="../inventory/index.php" class="nav-item"><span class="icon">📦</span> Products</a>
    <a href="../inventory/add.php" class="nav-item"><span class="icon">➕</span> Add Product</a>
    <a href="../inventory/categories.php" class="nav-item"><span class="icon">🏷️</span> Categories</a>
    <div class="nav-section">Transactions</div>
    <a href="../sales/pos.php" class="nav-item"><span class="icon">🖥️</span> POS</a>
    <a href="../sales/index.php" class="nav-item"><span class="icon">🧾</span> Sales</a>
    <a href="../purchases/index.php" class="nav-item"><span class="icon">📥</span> Purchases</a>
    <a href="index.php" class="nav-item active"><span class="icon">↩️</span> Returns</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Sale Returns</h2></div>
    <div class="content">
        <div class="page-header">
            <h2>Returns List</h2>
            <a href="add.php" class="btn btn-primary">+ New Return</a>
        </div>
        <?php if(isset($_GET['msg'])): ?>
        <div class="success">Return recorded. Stock restored.</div>
        <?php endif; ?>
        <table>
            <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Return Price</th><th>Total</th><th>Reason</th><th>Date</th></tr></thead>
            <tbody>
            <?php if(empty($returns)): ?>
                <tr><td colspan="7" class="empty">No returns yet.</td></tr>
            <?php else: ?>
                <?php foreach($returns as $i => $r): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                    <td><?php echo $r['quantity']; ?></td>
                    <td>PKR <?php echo number_format($r['return_price'], 2); ?></td>
                    <td>PKR <?php echo number_format($r['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($r['reason']); ?></td>
                    <td><?php echo $r['return_date']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>