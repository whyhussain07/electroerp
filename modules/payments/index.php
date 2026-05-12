<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$type = $_GET['type'] ?? 'in';
$payments = $pdo->prepare("SELECT * FROM payments WHERE type = ? ORDER BY payment_date DESC");
$payments->execute([$type]);
$payments = $payments->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE type = ?");
$total->execute([$type]);
$total = $total->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments | ElectroERP</title>
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
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .tab { padding: 8px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; background: #1e293b; color: #94a3b8; border: 1px solid #334155; }
        .tab.active { background: #38bdf8; color: #0f172a; border-color: #38bdf8; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .summary-card { background: #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 24px; border: 1px solid #334155; display: inline-block; min-width: 200px; }
        .summary-card h4 { font-size: 13px; color: #94a3b8; margin-bottom: 8px; }
        .summary-card p { font-size: 24px; font-weight: 700; color: #22c55e; }
        .summary-card.out p { color: #ef4444; }
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
    <a href="index.php?type=in" class="nav-item active"><span class="icon">💰</span> Payment In</a>
    <a href="index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
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
    <div class="topbar"><h2><?php echo $type === 'in' ? 'Payment In' : 'Payment Out'; ?></h2></div>
    <div class="content">
        <div class="page-header">
            <div class="tabs">
                <a href="index.php?type=in" class="tab <?php echo $type==='in'?'active':''; ?>">💰 Payment In</a>
                <a href="index.php?type=out" class="tab <?php echo $type==='out'?'active':''; ?>">💸 Payment Out</a>
            </div>
            <a href="add.php?type=<?php echo $type; ?>" class="btn btn-primary">+ New Payment</a>
        </div>

        <div class="summary-card <?php echo $type==='out'?'out':''; ?>">
            <h4>Total <?php echo $type==='in'?'Received':'Paid Out'; ?></h4>
            <p>PKR <?php echo number_format($total, 0); ?></p>
        </div>

        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Party</th><th>Reference</th><th>Method</th><th>Note</th><th>Amount</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if(empty($payments)): ?>
                <tr><td colspan="8" class="empty">No payments yet.</td></tr>
            <?php else: ?>
                <?php foreach($payments as $i => $p): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo $p['payment_date']; ?></td>
                    <td><?php echo htmlspecialchars($p['party_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['reference_no']); ?></td>
                    <td><span class="badge badge-<?php echo $p['payment_method']; ?>"><?php echo ucfirst($p['payment_method']); ?></span></td>
                    <td><?php echo htmlspecialchars($p['note']); ?></td>
                    <td style="color:<?php echo $type==='in'?'#22c55e':'#ef4444'; ?>; font-weight:600;">PKR <?php echo number_format($p['amount'], 0); ?></td>
                    <td><a href="index.php?type=<?php echo $type; ?>&delete=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>