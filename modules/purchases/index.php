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
    // Restore stock before deleting
    $items = $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
    $items->execute([$id]);
    foreach($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
    }
    $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM purchases WHERE id = ?")->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

$purchases = $pdo->query("
    SELECT p.*, s.name as supplier_name 
    FROM purchases p 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    ORDER BY p.purchase_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchases | ElectroERP</title>
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
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
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
        <a href="../suppliers/index.php">Suppliers</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <div class="page-header">
        <h2>Purchase Invoices</h2>
        <a href="add.php" class="btn btn-primary">+ New Purchase</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="success">
            <?php
                if($_GET['msg'] === 'added') echo 'Purchase invoice added. Stock updated.';
                if($_GET['msg'] === 'deleted') echo 'Purchase deleted. Stock reversed.';
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice No</th>
                <th>Supplier</th>
                <th>Date</th>
                <th>Total (PKR)</th>
                <th>Paid (PKR)</th>
                <th>Balance (PKR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($purchases)): ?>
            <tr><td colspan="9" class="empty">No purchases yet. Create your first purchase invoice.</td></tr>
        <?php else: ?>
            <?php foreach($purchases as $i => $p): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($p['invoice_no']); ?></td>
                <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                <td><?php echo $p['purchase_date']; ?></td>
                <td>PKR <?php echo number_format($p['total_amount'], 2); ?></td>
                <td>PKR <?php echo number_format($p['paid_amount'], 2); ?></td>
                <td>PKR <?php echo number_format($p['balance'], 2); ?></td>
                <td>
                    <?php if($p['balance'] == 0): ?>
                        <span class="badge badge-paid">Paid</span>
                    <?php elseif($p['paid_amount'] > 0): ?>
                        <span class="badge badge-partial">Partial</span>
                    <?php else: ?>
                        <span class="badge badge-unpaid">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="view.php?id=<?php echo $p['id']; ?>" class="btn btn-view">View</a>
                    <a href="index.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this purchase? Stock will be reversed.')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>