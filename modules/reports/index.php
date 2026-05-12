<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

// Filters
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$report = $_GET['report'] ?? 'sales';

// Sales Report
$sales_data = $pdo->prepare("SELECT s.*, COUNT(si.id) as item_count FROM sales s LEFT JOIN sale_items si ON s.id = si.sale_id WHERE s.sale_date BETWEEN ? AND ? GROUP BY s.id ORDER BY s.sale_date DESC");
$sales_data->execute([$from, $to]);
$sales_data = $sales_data->fetchAll(PDO::FETCH_ASSOC);

$sales_summary = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(balance),0) as balance, COALESCE(SUM(discount),0) as discount FROM sales WHERE sale_date BETWEEN ? AND ?");
$sales_summary->execute([$from, $to]);
$sales_summary = $sales_summary->fetch(PDO::FETCH_ASSOC);

// Stock Report
$stock_data = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category = c.name ORDER BY p.stock ASC")->fetchAll(PDO::FETCH_ASSOC);

// Profit/Loss
$purchase_total = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as total FROM purchases WHERE purchase_date BETWEEN ? AND ?");
$purchase_total->execute([$from, $to]);
$purchase_total = $purchase_total->fetchColumn();

$sale_total = $sales_summary['total'];
$profit = $sale_total - $purchase_total;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; display: flex; }
        .sidebar {
            width: 240px;
            height: 100vh;
            overflow-y: auto;
            background: #1e293b;
            border-right: 1px solid #334155;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0;
        }
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
        .filter-bar { background: #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; border: 1px solid #334155; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 12px; color: #64748b; }
        input[type="date"], select {
            padding: 9px 12px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #f1f5f9;
            font-size: 13px;
        }
        .btn { padding: 9px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-print { background: #334155; color: #f1f5f9; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .s-card { background: #1e293b; border-radius: 10px; padding: 18px; border: 1px solid #334155; }
        .s-card h4 { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
        .s-card p { font-size: 20px; font-weight: 700; color: #38bdf8; }
        .s-card.green p { color: #22c55e; }
        .s-card.red p { color: #ef4444; }
        .s-card.yellow p { color: #f59e0b; }
        .report-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .tab { padding: 8px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; background: #1e293b; color: #94a3b8; border: 1px solid #334155; }
        .tab.active { background: #38bdf8; color: #0f172a; border-color: #38bdf8; }
        .section-box { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .section-box h3 { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #334155; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #0f172a; }
        th { padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; }
        td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .empty { text-align: center; padding: 30px; color: #64748b; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #16a34a; }
        .badge-partial { background: #fef9c3; color: #ca8a04; }
        .badge-unpaid { background: #fee2e2; color: #dc2626; }
        .badge-low { background: #fee2e2; color: #dc2626; }
        .badge-ok { background: #dcfce7; color: #16a34a; }
        .profit-box { background: #1e293b; border-radius: 12px; padding: 30px; border: 1px solid #334155; }
        .profit-row { display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #334155; font-size: 15px; }
        .profit-row:last-child { border-bottom: none; font-size: 18px; font-weight: 700; }
        .profit-row.positive { color: #22c55e; }
        .profit-row.negative { color: #ef4444; }
        @media print {
            .sidebar, .filter-bar, .report-tabs, .topbar, .no-print { display: none !important; }
            .main { margin-left: 0; }
            body { background: white; color: black; }
            .section-box, .profit-box, .s-card { background: white; border: 1px solid #ccc; }
        }
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
    <div class="nav-section">Masters</div>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="index.php" class="nav-item active"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer">
        <a href="../../logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h2>Reports</h2>
        <button onclick="window.print()" class="btn btn-print no-print">🖨 Print Report</button>
    </div>

    <div class="content">

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar no-print">
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="from" value="<?php echo $from; ?>">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="to" value="<?php echo $to; ?>">
            </div>
            <div class="filter-group">
                <label>Report Type</label>
                <select name="report">
                    <option value="sales" <?php echo $report==='sales'?'selected':''; ?>>Sales Report</option>
                    <option value="stock" <?php echo $report==='stock'?'selected':''; ?>>Stock Report</option>
                    <option value="profit" <?php echo $report==='profit'?'selected':''; ?>>Profit / Loss</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>

        <!-- Report Tabs -->
        <div class="report-tabs no-print">
            <a href="?report=sales&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab <?php echo $report==='sales'?'active':''; ?>">📋 Sales</a>
            <a href="?report=stock&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab <?php echo $report==='stock'?'active':''; ?>">📦 Stock</a>
            <a href="?report=profit&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab <?php echo $report==='profit'?'active':''; ?>">💰 Profit/Loss</a>
        </div>

        <?php if($report === 'sales'): ?>
        <!-- SALES REPORT -->
        <div class="summary-cards">
            <div class="s-card green"><h4>Total Sales</h4><p>PKR <?php echo number_format($sales_summary['total'], 0); ?></p></div>
            <div class="s-card"><h4>Total Collected</h4><p>PKR <?php echo number_format($sales_summary['paid'], 0); ?></p></div>
            <div class="s-card red"><h4>Total Balance Due</h4><p>PKR <?php echo number_format($sales_summary['balance'], 0); ?></p></div>
            <div class="s-card yellow"><h4>Total Discount Given</h4><p>PKR <?php echo number_format($sales_summary['discount'], 0); ?></p></div>
        </div>

        <div class="section-box">
            <h3>Sales — <?php echo $from; ?> to <?php echo $to; ?></h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Discount</th><th>Grand Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if(empty($sales_data)): ?>
                    <tr><td colspan="10" class="empty">No sales in this period.</td></tr>
                <?php else: ?>
                    <?php foreach($sales_data as $i => $s): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo htmlspecialchars($s['invoice_no']); ?></td>
                        <td><?php echo htmlspecialchars($s['customer_name']); ?></td>
                        <td><?php echo $s['sale_date']; ?></td>
                        <td>PKR <?php echo number_format($s['total_amount'], 0); ?></td>
                        <td>PKR <?php echo number_format($s['discount'], 0); ?></td>
                        <td>PKR <?php echo number_format($s['grand_total'], 0); ?></td>
                        <td>PKR <?php echo number_format($s['paid_amount'], 0); ?></td>
                        <td>PKR <?php echo number_format($s['balance'], 0); ?></td>
                        <td>
                            <?php if($s['balance'] == 0): ?>
                                <span class="badge badge-paid">Paid</span>
                            <?php elseif($s['paid_amount'] > 0): ?>
                                <span class="badge badge-partial">Partial</span>
                            <?php else: ?>
                                <span class="badge badge-unpaid">Unpaid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($report === 'stock'): ?>
        <!-- STOCK REPORT -->
        <div class="summary-cards">
            <div class="s-card"><h4>Total Products</h4><p><?php echo count($stock_data); ?></p></div>
            <div class="s-card red"><h4>Low Stock Items</h4><p><?php echo count(array_filter($stock_data, fn($p) => $p['stock'] <= $p['low_stock_alert'])); ?></p></div>
            <div class="s-card green"><h4>Total Stock Value</h4><p>PKR <?php echo number_format(array_sum(array_map(fn($p) => $p['purchase_price'] * $p['stock'], $stock_data)), 0); ?></p></div>
        </div>

        <div class="section-box">
            <h3>Stock Report</h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Product</th><th>Brand</th><th>Category</th><th>Purchase Price</th><th>Sale Price</th><th>Stock</th><th>Stock Value</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach($stock_data as $i => $p): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['brand']); ?></td>
                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                    <td>PKR <?php echo number_format($p['purchase_price'], 0); ?></td>
                    <td>PKR <?php echo number_format($p['sale_price'], 0); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td>PKR <?php echo number_format($p['purchase_price'] * $p['stock'], 0); ?></td>
                    <td>
                        <?php if($p['stock'] <= $p['low_stock_alert']): ?>
                            <span class="badge badge-low">Low Stock</span>
                        <?php else: ?>
                            <span class="badge badge-ok">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($report === 'profit'): ?>
        <!-- PROFIT/LOSS REPORT -->
        <div class="profit-box">
            <h3 style="margin-bottom:20px; font-size:16px;">Profit / Loss — <?php echo $from; ?> to <?php echo $to; ?></h3>
            <div class="profit-row">
                <span>Total Sales Revenue</span>
                <span>PKR <?php echo number_format($sale_total, 2); ?></span>
            </div>
            <div class="profit-row">
                <span>Total Purchase Cost</span>
                <span>PKR <?php echo number_format($purchase_total, 2); ?></span>
            </div>
            <div class="profit-row">
                <span>Total Discount Given</span>
                <span>- PKR <?php echo number_format($sales_summary['discount'], 2); ?></span>
            </div>
            <div class="profit-row <?php echo $profit >= 0 ? 'positive' : 'negative'; ?>">
                <span><?php echo $profit >= 0 ? '✅ Net Profit' : '❌ Net Loss'; ?></span>
                <span>PKR <?php echo number_format(abs($profit), 2); ?></span>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>