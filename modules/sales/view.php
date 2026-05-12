<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$sale = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$sale->execute([$id]);
$s = $sale->fetch(PDO::FETCH_ASSOC);

if(!$s) {
    header("Location: index.php");
    exit();
}

$items = $pdo->prepare("SELECT si.*, p.name as product_name, p.brand, p.model FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale Invoice | ElectroERP</title>
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
            padding-bottom: 20px;
            border-bottom: 1px solid #334155;
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
        .info-box p { font-size: 14px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead { background: #334155; }
        th { padding: 12px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .totals { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .totals div { display: flex; gap: 60px; font-size: 14px; }
        .totals div span:first-child { color: #94a3b8; }
        .totals div span:last-child { font-weight: 600; min-width: 140px; text-align: right; }
        .totals .grand { font-size: 18px; color: #38bdf8; padding-top: 8px; border-top: 1px solid #334155; }
        .totals .balance { color: #ef4444; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #334155; color: #f1f5f9; }
        .btn-print { background: #38bdf8; color: #0f172a; margin-left: 10px; }
        .btn-new { background: #22c55e; color: white; margin-left: 10px; }
        @media print {
            .topbar, .no-print { display: none; }
            body { background: white; color: black; }
            .invoice-box { background: white; color: black; box-shadow: none; }
            th { background: #eee; color: black; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Sales</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <?php if(isset($_GET['new'])): ?>
        <div style="background:#dcfce7; color:#16a34a; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px;">
            ✅ Sale saved successfully! Stock has been updated.
        </div>
    <?php endif; ?>

    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <h2>⚡ ElectroERP</h2>
                <p>Sale Invoice</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:18px; font-weight:700; color:#38bdf8;"><?php echo htmlspecialchars($s['invoice_no']); ?></p>
                <p style="color:#94a3b8; font-size:13px;"><?php echo $s['sale_date']; ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h4>Customer</h4>
                <p><?php echo htmlspecialchars($s['customer_name']); ?></p>
                <?php if($s['customer_phone']): ?>
                    <p style="color:#94a3b8;"><?php echo htmlspecialchars($s['customer_phone']); ?></p>
                <?php endif; ?>
            </div>
            <div class="info-box">
                <h4>Payment</h4>
                <p>Total: PKR <?php echo number_format($s['total_amount'], 2); ?></p>
                <?php if($s['discount'] > 0): ?>
                    <p>Discount: PKR <?php echo number_format($s['discount'], 2); ?></p>
                <?php endif; ?>
                <p>Paid: PKR <?php echo number_format($s['paid_amount'], 2); ?></p>
                <?php if($s['balance'] > 0): ?>
                    <p style="color:#ef4444;">Balance: PKR <?php echo number_format($s['balance'], 2); ?></p>
                <?php else: ?>
                    <p style="color:#22c55e;">✅ Fully Paid</p>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Sale Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i => $item): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>PKR <?php echo number_format($item['sale_price'], 2); ?></td>
                    <td>PKR <?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div><span>Subtotal</span><span>PKR <?php echo number_format($s['total_amount'], 2); ?></span></div>
            <?php if($s['discount'] > 0): ?>
            <div><span>Discount</span><span>- PKR <?php echo number_format($s['discount'], 2); ?></span></div>
            <?php endif; ?>
            <div class="grand"><span>Grand Total</span><span>PKR <?php echo number_format($s['grand_total'], 2); ?></span></div>
            <div><span>Paid</span><span>PKR <?php echo number_format($s['paid_amount'], 2); ?></span></div>
            <?php if($s['balance'] > 0): ?>
            <div class="balance"><span>Balance Due</span><span>PKR <?php echo number_format($s['balance'], 2); ?></span></div>
            <?php endif; ?>
        </div>

        <?php if($s['notes']): ?>
            <p style="margin-top:20px; color:#94a3b8; font-size:13px;">Notes: <?php echo htmlspecialchars($s['notes']); ?></p>
        <?php endif; ?>
    </div>

    <div class="no-print">
        <a href="index.php" class="btn btn-secondary">← Back</a>
        <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
        <a href="pos.php" class="btn btn-new">+ New Sale</a>
    </div>
</div>

</body>
</html>