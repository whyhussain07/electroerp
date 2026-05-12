<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    header("Location: index.php?msg=deleted"); exit();
}

$customers = $pdo->query("SELECT c.*, COUNT(s.id) as total_sales, COALESCE(SUM(s.grand_total),0) as total_spent FROM customers c LEFT JOIN sales s ON c.id = s.customer_id GROUP BY c.id ORDER BY c.name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers | ElectroERP</title>
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
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-edit { background: #f59e0b; color: #0f172a; font-size: 12px; padding: 6px 12px; }
        .btn-view { background: #6366f1; color: white; font-size: 12px; padding: 6px 12px; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
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
    <a href="../returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <div class="nav-section">Masters</div>
    <a href="index.php" class="nav-item active"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Customers</h2></div>
    <div class="content">
        <div class="page-header">
            <h2>Customer List</h2>
            <a href="add.php" class="btn btn-primary">+ Add Customer</a>
        </div>
        <?php if(isset($_GET['msg'])): ?>
        <div class="success"><?php echo $_GET['msg']==='added'?'Customer added.':($_GET['msg']==='updated'?'Customer updated.':'Customer deleted.'); ?></div>
        <?php endif; ?>
        <table>
            <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>City</th><th>Total Sales</th><th>Total Spent</th><th>Balance</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($customers)): ?>
                <tr><td colspan="8" class="empty">No customers yet.</td></tr>
            <?php else: ?>
                <?php foreach($customers as $i => $c): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo htmlspecialchars($c['phone']); ?></td>
                    <td><?php echo htmlspecialchars($c['city']); ?></td>
                    <td><?php echo $c['total_sales']; ?></td>
                    <td>PKR <?php echo number_format($c['total_spent'], 0); ?></td>
                    <td style="color:<?php echo $c['balance']>0?'#ef4444':'#22c55e'; ?>">PKR <?php echo number_format($c['balance'], 0); ?></td>
                    <td>
                        <a href="view.php?id=<?php echo $c['id']; ?>" class="btn btn-view">View</a>
                        <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="index.php?delete=<?php echo $c['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>