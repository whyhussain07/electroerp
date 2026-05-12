<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$c = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$c->execute([$id]);
$c = $c->fetch(PDO::FETCH_ASSOC);
if(!$c) { header("Location: index.php"); exit(); }

$sales = $pdo->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY sale_date DESC");
$sales->execute([$id]);
$sales = $sales->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 900px; }
        .info-box { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 24px; border: 1px solid #334155; }
        .info-box h2 { font-size: 20px; margin-bottom: 4px; }
        .info-box p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat { background: #1e293b; border-radius: 10px; padding: 18px; border: 1px solid #334155; }
        .stat h4 { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
        .stat p { font-size: 20px; font-weight: 700; color: #38bdf8; }
        .section-box { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .section-box h3 { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #334155; color: #94a3b8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; background: #0f172a; }
        td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #16a34a; }
        .badge-partial { background: #fef9c3; color: #ca8a04; }
        .badge-unpaid { background: #fee2e2; color: #dc2626; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #334155; color: #f1f5f9; }
        .empty { text-align: center; padding: 30px; color: #64748b; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Customers</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <div class="info-box">
        <h2><?php echo htmlspecialchars($c['name']); ?></h2>
        <p><?php echo htmlspecialchars($c['phone']); ?> <?php echo $c['city'] ? '| '.$c['city'] : ''; ?></p>
        <?php if($c['address']): ?><p style="margin-top:8px;"><?php echo htmlspecialchars($c['address']); ?></p><?php endif; ?>
    </div>
    <div class="stats">
        <div class="stat"><h4>Total Orders</h4><p><?php echo count($sales); ?></p></div>
        <div class="stat"><h4>Total Spent</h4><p>PKR <?php echo number_format(array_sum(array_column($sales, 'grand_total')), 0); ?></p></div>
        <div class="stat"><h4>Balance Due</h4><p style="color:<?php echo $c['balance']>0?'#ef4444':'#22c55e'; ?>">PKR <?php echo number_format($c['balance'], 0); ?></p></div>
    </div>
    <div class="section-box">
        <h3>Purchase History</h3>
        <table>
            <thead><tr><th>Invoice</th><th>Date</th><th>Grand Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            <?php if(empty($sales)): ?>
                <tr><td colspan="6" class="empty">No purchases yet.</td></tr>
            <?php else: ?>
                <?php foreach($sales as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['invoice_no']); ?></td>
                    <td><?php echo $s['sale_date']; ?></td>
                    <td>PKR <?php echo number_format($s['grand_total'], 0); ?></td>
                    <td>PKR <?php echo number_format($s['paid_amount'], 0); ?></td>
                    <td>PKR <?php echo number_format($s['balance'], 0); ?></td>
                    <td>
                        <?php if($s['balance']==0): ?><span class="badge badge-paid">Paid</span>
                        <?php elseif($s['paid_amount']>0): ?><span class="badge badge-partial">Partial</span>
                        <?php else: ?><span class="badge badge-unpaid">Unpaid</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <br>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
</body>
</html>