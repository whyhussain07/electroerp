<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db.php';

// Live stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= low_stock_alert")->fetchColumn();
$total_suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$today = date('Y-m-d');
$today_sales = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE sale_date = '$today'")->fetchColumn();
$today_purchases = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE purchase_date = '$today'")->fetchColumn();
$total_stock_value = $pdo->query("SELECT COALESCE(SUM(purchase_price * stock), 0) FROM products")->fetchColumn();
$recent_sales = $pdo->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent_purchases = $pdo->query("SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; display: flex; }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #1e293b;
            border-right: 1px solid #94a3b8;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-logo {
            padding: 20px 24px;
            font-size: 20px;
            font-weight: 700;
            color: #38bdf8;
            border-bottom: 1px solid #94a3b8;
        }
        .sidebar-logo span { font-size: 13px; color: #64748b; display: block; font-weight: 400; margin-top: 2px; }
        .nav-section { padding: 16px 12px 8px; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 1px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: #0f172a; color: #38bdf8; }
        .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid #334155;
        }
        .sidebar-footer a {
            display: block;
            padding: 10px 16px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px;
        }
        .sidebar-footer a:hover { background: #fee2e2; }

        /* MAIN */
        .main {
            margin-left: 240px;
            flex: 1;
            min-height: 100vh;
        }
        .topbar {
            background: #1e293b;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar h2 { font-size: 18px; font-weight: 600; }
        .topbar span { font-size: 13px; color: #94a3b8; }
        .content { padding: 30px; }

        /* STAT CARDS */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #334155;
        }
        .card h3 { font-size: 13px; color: #94a3b8; margin-bottom: 10px; }
        .card p { font-size: 26px; font-weight: 700; color: #38bdf8; }
        .card.red p { color: #ef4444; }
        .card.green p { color: #22c55e; }
        .card.yellow p { color: #f59e0b; }

        /* TABLES */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .section-box {
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #334155;
        }
        .section-box h3 {
            padding: 16px 20px;
            font-size: 14px;
            border-bottom: 1px solid #334155;
            color: #94a3b8;
        }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 10px 16px; text-align: left; font-size: 12px; color: #64748b; background: #0f172a; }
        td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #16a34a; }
        .badge-partial { background: #fef9c3; color: #ca8a04; }
        .badge-unpaid { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        ⚡ ElectroERP
        <span>Mobile & Electronics</span>
    </div>

    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item active"><span class="icon">📊</span> Dashboard</a>

    <div class="nav-section">Inventory</div>
    <a href="modules/inventory/index.php" class="nav-item"><span class="icon">📦</span> Products</a>
    <a href="modules/inventory/add.php" class="nav-item"><span class="icon">➕</span> Add Product</a>
    <a href="modules/inventory/categories.php" class="nav-item"><span class="icon">🏷️</span> Categories</a>
    <a href="modules/inventory/bulk_price.php" class="nav-item"><span class="icon">💲</span> Bulk Price Change</a>
    <a href="modules/stock/index.php" class="nav-item"><span class="icon">🔧</span> Stock Adjustment</a>

<div class="nav-section">Transactions</div>
    <a href="modules/sales/pos.php" class="nav-item"><span class="icon">🖥️</span> POS</a>
    <a href="modules/sales/index.php" class="nav-item"><span class="icon">🧾</span> Sales</a>
    <a href="modules/purchases/index.php" class="nav-item"><span class="icon">📥</span> Purchases</a>
    <a href="modules/purchases/add.php" class="nav-item"><span class="icon">➕</span> New Purchase</a>
    <a href="modules/returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <a href="modules/quotations/index.php" class="nav-item"><span class="icon">📋</span> Quotations</a>

    <div class="nav-section">Banking</div>
    <a href="modules/payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="modules/payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="modules/expenses/index.php" class="nav-item"><span class="icon">🧾</span> Expenses</a>
    <a href="modules/cash/index.php" class="nav-item"><span class="icon">🏦</span> Cash Counter</a>

<div class="nav-section">Masters</div>
    <a href="modules/customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="modules/suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>

    <div class="nav-section">HR</div>
    <a href="modules/hr/index.php" class="nav-item"><span class="icon">👨‍💼</span> Employees</a>
    <a href="modules/hr/attendance.php" class="nav-item"><span class="icon">📅</span> Attendance</a>
    <a href="modules/hr/salary.php" class="nav-item"><span class="icon">💵</span> Salary</a>

    <div class="nav-section">Reports</div>
    <a href="modules/reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>

    <div class="nav-section">Settings</div>
    <a href="modules/users/index.php" class="nav-item"><span class="icon">👤</span> User Management</a>
    <a href="modules/settings/index.php" class="nav-item"><span class="icon">⚙️</span> Company Settings</a>

    <div class="sidebar-footer">
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="topbar">
        <h2>Dashboard</h2>
        <span>Welcome, <?php echo $_SESSION['user_name']; ?> &nbsp;|&nbsp; <?php echo date('D, d M Y'); ?></span>
    </div>

    <div class="content">

        <!-- STAT CARDS -->
        <div class="cards">
            <div class="card">
                <h3>Total Products</h3>
                <p><?php echo $total_products; ?></p>
            </div>
            <div class="card green">
                <h3>Today's Sales</h3>
                <p>PKR <?php echo number_format($today_sales, 0); ?></p>
            </div>
            <div class="card red">
                <h3>Low Stock Items</h3>
                <p><?php echo $low_stock; ?></p>
            </div>
            <div class="card">
                <h3>Total Suppliers</h3>
                <p><?php echo $total_suppliers; ?></p>
            </div>
            <div class="card yellow">
                <h3>Today's Purchases</h3>
                <p>PKR <?php echo number_format($today_purchases, 0); ?></p>
            </div>
            <div class="card green">
                <h3>Stock Value</h3>
                <p>PKR <?php echo number_format($total_stock_value, 0); ?></p>
            </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="grid-2">
            <div class="section-box">
                <h3>Recent Sales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($recent_sales)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">No sales yet</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_sales as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['invoice_no']); ?></td>
                            <td><?php echo htmlspecialchars($s['customer_name']); ?></td>
                            <td>PKR <?php echo number_format($s['grand_total'], 0); ?></td>
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

            <div class="section-box">
                <h3>Recent Purchases</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Supplier</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($recent_purchases)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">No purchases yet</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_purchases as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['invoice_no']); ?></td>
                            <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                            <td>PKR <?php echo number_format($p['total_amount'], 0); ?></td>
                            <td>
                                <?php if($p['balance'] == 0): ?>
                                    <span class="badge badge-paid">Paid</span>
                                <?php elseif($p['paid_amount'] > 0): ?>
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
        </div>

    </div>
</div>

</body>
</html>