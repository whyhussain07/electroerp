<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Restore stock
    $items = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
    $items->execute([$id]);
    foreach($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
    }
    $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

$sales = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales | ElectroERP</title>
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
        .topbar a:hover { color: #f1f5f9; }
        .content { padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header h2 { font-size: 20px; }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-success { background: #22c55e; color: white; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-view { background: #6366f1; color: white; font-size: 12px; padding: 6px 12px; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #16a34a; }
        .badge-partial { background: #fef9c3; color: #ca8a04; }
        .badge-unpaid { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../inventory/index.php">Inventory</a>
        <a href="../purchases/index.php">Purchases</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <div class="page-header">
        <h2>Sales Invoices</h2>
        <div>
            <a href="pos.php" class="btn btn-success" style="margin-right:10px;">🖥 Open POS</a>
            <a href="add.php" class="btn btn-primary">+ New Sale Invoice</a>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="success">
            <?php
                if($_GET['msg'] === 'added') echo 'Sale recorded. Stock updated.';
                if($_GET['msg'] === 'deleted') echo 'Sale deleted. Stock restored.';
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice No</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total (PKR)</th>
                <th>Discount</th>
                <th>Grand Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($sales)): ?>
            <tr><td colspan="11" class="empty">No sales yet. Make your first sale.</td></tr>
        <?php else: ?>
            <?php foreach($sales as $i => $s): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($s['invoice_no']); ?></td>
                <td><?php echo htmlspecialchars($s['customer_name']); ?></td>
                <td><?php echo $s['sale_date']; ?></td>
                <td>PKR <?php echo number_format($s['total_amount'], 2); ?></td>
                <td>PKR <?php echo number_format($s['discount'], 2); ?></td>
                <td>PKR <?php echo number_format($s['grand_total'], 2); ?></td>
                <td>PKR <?php echo number_format($s['paid_amount'], 2); ?></td>
                <td>PKR <?php echo number_format($s['balance'], 2); ?></td>
                <td>
                    <?php if($s['balance'] == 0): ?>
                        <span class="badge badge-paid">Paid</span>
                    <?php elseif($s['paid_amount'] > 0): ?>
                        <span class="badge badge-partial">Partial</span>
                    <?php else: ?>
                        <span class="badge badge-unpaid">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="view.php?id=<?php echo $s['id']; ?>" class="btn btn-view">View</a>
                    <a href="index.php?delete=<?php echo $s['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this sale? Stock will be restored.')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>