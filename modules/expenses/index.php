<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: index.php?msg=deleted"); exit();
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$expenses = $pdo->prepare("SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC");
$expenses->execute([$from, $to]);
$expenses = $expenses->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?");
$total->execute([$from, $to]);
$total = $total->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenses | ElectroERP</title>
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
        .filter-bar { background: #1e293b; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; border: 1px solid #334155; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 12px; color: #64748b; }
        input[type="date"] { padding: 9px 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .summary-card { background: #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 24px; border: 1px solid #334155; display: inline-block; min-width: 200px; }
        .summary-card h4 { font-size: 13px; color: #94a3b8; margin-bottom: 8px; }
        .summary-card p { font-size: 24px; font-weight: 700; color: #ef4444; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-cash { background: #dcfce7; color: #16a34a; }
        .badge-bank { background: #dbeafe; color: #2563eb; }
        .badge-cheque { background: #fef9c3; color: #ca8a04; }
        .badge-online { background: #f3e8ff; color: #9333ea; }
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
    <div class="nav-section">Banking</div>
    <a href="../payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="../payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="index.php" class="nav-item active"><span class="icon">🧾</span> Expenses</a>
    <a href="../cash/index.php" class="nav-item"><span class="icon">🏦</span> Cash Counter</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Expenses</h2></div>
    <div class="content">
        <form method="GET" class="filter-bar">
            <div class="filter-group"><label>From</label><input type="date" name="from" value="<?php echo $from; ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="to" value="<?php echo $to; ?>"></div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <div class="page-header">
            <div class="summary-card"><h4>Total Expenses</h4><p>PKR <?php echo number_format($total, 0); ?></p></div>
            <a href="add.php" class="btn btn-primary">+ Add Expense</a>
        </div>

        <table>
            <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Method</th><th>Note</th><th>Amount</th><th>Action</th></tr></thead>
            <tbody>
            <?php if(empty($expenses)): ?>
                <tr><td colspan="8" class="empty">No expenses in this period.</td></tr>
            <?php else: ?>
                <?php foreach($expenses as $i => $e): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($e['title']); ?></td>
                    <td><?php echo htmlspecialchars($e['category']); ?></td>
                    <td><?php echo $e['expense_date']; ?></td>
                    <td><span class="badge badge-<?php echo $e['payment_method']; ?>"><?php echo ucfirst($e['payment_method']); ?></span></td>
                    <td><?php echo htmlspecialchars($e['note']); ?></td>
                    <td style="color:#ef4444; font-weight:600;">PKR <?php echo number_format($e['amount'], 0); ?></td>
                    <td><a href="index.php?delete=<?php echo $e['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>