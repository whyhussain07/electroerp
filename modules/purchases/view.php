<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$purchase = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.phone as supplier_phone, s.city as supplier_city FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ?");
$purchase->execute([$id]);
$p = $purchase->fetch(PDO::FETCH_ASSOC);

if(!$p) {
    header("Location: index.php");
    exit();
}

$items = $pdo->prepare("SELECT pi.*, pr.name as product_name, pr.brand, pr.model FROM purchase_items pi LEFT JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = ?");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Invoice | ElectroERP</title>
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
        .content { padding: 30px; max-width: 900px; }
        .invoice-box {
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 24px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-header h2 { font-size: 22px; color: #38bdf8; }
        .invoice-header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box h4 { font-size: 12px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        .info-box p { font-size: 14px; color: #f1f5f9; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead { background: #334155; }
        th { padding: 12px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .totals { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .totals div { display: flex; gap: 40px; font-size: 14px; }
        .totals div span:first-child { color: #94a3b8; }
        .totals div span:last-child { font-weight: 600; min-width: 120px; text-align: right; }
        .totals .grand { font-size: 16px; color: #38bdf8; }
        .totals .balance { color: #ef4444; }
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
        .btn-secondary { background: #334155; color: #f1f5f9; }
        .btn-print { background: #38bdf8; color: #0f172a; margin-left: 10px; }
        @media print {
            .topbar, .btn { display: none; }
            body { background: white; color: black; }
            .invoice-box { background: white; color: black; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Purchases</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <h2>⚡ ElectroERP</h2>
                <p>Purchase Invoice</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:18px; font-weight:700; color:#38bdf8;"><?php echo htmlspecialchars($p['invoice_no']) ?: 'N/A'; ?></p>
                <p style="color:#94a3b8; font-size:13px;"><?php echo $p['purchase_date']; ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h4>Supplier</h4>
                <p><?php echo htmlspecialchars($p['supplier_name']); ?></p>
                <p><?php echo htmlspecialchars($p['supplier_phone']); ?></p>
                <p><?php echo htmlspecialchars($p['supplier_city']); ?></p>
            </div>
            <div class="info-box">
                <h4>Payment Status</h4>
                <p>Total: PKR <?php echo number_format($p['total_amount'], 2); ?></p>
                <p>Paid: PKR <?php echo number_format($p['paid_amount'], 2); ?></p>
                <p style="color:#ef4444;">Balance: PKR <?php echo number_format($p['balance'], 2); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Purchase Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i => $item): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?> <?php echo htmlspecialchars($item['brand']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>PKR <?php echo number_format($item['purchase_price'], 2); ?></td>
                    <td>PKR <?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div><span>Total Amount</span><span>PKR <?php echo number_format($p['total_amount'], 2); ?></span></div>
            <div><span>Paid Amount</span><span>PKR <?php echo number_format($p['paid_amount'], 2); ?></span></div>
            <div class="balance"><span>Balance Due</span><span>PKR <?php echo number_format($p['balance'], 2); ?></span></div>
        </div>

        <?php if($p['notes']): ?>
            <p style="margin-top:20px; color:#94a3b8; font-size:13px;">Notes: <?php echo htmlspecialchars($p['notes']); ?></p>
        <?php endif; ?>
    </div>

    <a href="index.php" class="btn btn-secondary">← Back</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
</div>

</body>
</html>