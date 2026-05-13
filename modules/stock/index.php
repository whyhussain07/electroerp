<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if(isset($_GET['delete']) && isAdmin()) {
    $adj = $pdo->prepare("SELECT * FROM stock_adjustments WHERE id = ?");
    $adj->execute([(int)$_GET['delete']]);
    $adj = $adj->fetch(PDO::FETCH_ASSOC);
    if($adj) {
        // Reverse the stock adjustment
        if($adj['type'] === 'add') {
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$adj['quantity'], $adj['product_id']]);
        } else {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$adj['quantity'], $adj['product_id']]);
        }
        $pdo->prepare("DELETE FROM stock_adjustments WHERE id = ?")->execute([(int)$_GET['delete']]);
    }
    header("Location: index.php?msg=deleted"); exit();
}

$adjustments = $pdo->query("SELECT sa.*, p.name as product_name FROM stock_adjustments sa LEFT JOIN products p ON sa.product_id = p.id ORDER BY sa.adjustment_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Adjustments | ElectroERP</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .deleted { background: #fee2e2; color: #dc2626; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-add { background: #dcfce7; color: #16a34a; }
        .badge-subtract { background: #fee2e2; color: #dc2626; }
        .admin-badge { background: #dbeafe; color: #2563eb; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
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
    <a href="../inventory/bulk_price.php" class="nav-item"><span class="icon">💲</span> Bulk Price Change</a>
    <a href="index.php" class="nav-item active"><span class="icon">🔧</span> Stock Adjustment</a>
    <div class="nav-section">Transactions</div>
    <a href="../sales/pos.php" class="nav-item"><span class="icon">🖥️</span> POS</a>
    <a href="../sales/index.php" class="nav-item"><span class="icon">🧾</span> Sales</a>
    <a href="../purchases/index.php" class="nav-item"><span class="icon">📥</span> Purchases</a>
    <a href="../returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <a href="../quotations/index.php" class="nav-item"><span class="icon">📋</span> Quotations</a>
    <div class="nav-section">Banking</div>
    <a href="../payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="../payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="../expenses/index.php" class="nav-item"><span class="icon">🧾</span> Expenses</a>
    <a href="../cash/index.php" class="nav-item"><span class="icon">🏦</span> Cash Counter</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">HR</div>
    <a href="../hr/index.php" class="nav-item"><span class="icon">👨‍💼</span> Employees</a>
    <a href="../hr/attendance.php" class="nav-item"><span class="icon">📅</span> Attendance</a>
    <a href="../hr/salary.php" class="nav-item"><span class="icon">💵</span> Salary</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="nav-section">Settings</div>
    <a href="../users/index.php" class="nav-item"><span class="icon">👤</span> User Management</a>
    <a href="../settings/index.php" class="nav-item"><span class="icon">⚙️</span> Company Settings</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h2>Stock Adjustments</h2>
        <?php if(isAdmin()): ?><span class="admin-badge">👑 Admin Mode</span><?php endif; ?>
    </div>
    <div class="content">
        <div class="page-header">
            <h2>Stock Adjustment History</h2>
            <a href="adjust.php" class="btn btn-primary">+ New Adjustment</a>
        </div>
        <?php if(isset($_GET['msg'])): ?>
        <div class="<?php echo $_GET['msg']==='adjusted'?'success':'deleted'; ?>">
            <?php echo $_GET['msg']==='adjusted'?'✅ Stock adjusted successfully.':'🗑️ Adjustment deleted and stock reversed.'; ?>
        </div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <?php if(isAdmin()): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($adjustments)): ?>
                <tr><td colspan="7" class="empty">No adjustments yet.</td></tr>
            <?php else: ?>
                <?php foreach($adjustments as $i => $a): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($a['product_name']); ?></td>
                    <td><span class="badge badge-<?php echo $a['type']; ?>"><?php echo $a['type']==='add'?'➕ Add':'➖ Subtract'; ?></span></td>
                    <td><?php echo $a['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($a['reason']); ?></td>
                    <td><?php echo $a['adjustment_date']; ?></td>
                    <?php if(isAdmin()): ?>
                    <td>
                        <a href="index.php?delete=<?php echo $a['id']; ?>" class="btn-danger" onclick="return confirm('Delete this adjustment? Stock will be reversed.')">Delete</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>