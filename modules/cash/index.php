<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$date = $_GET['date'] ?? date('Y-m-d');

// Get today's figures
$sales_total = $pdo->prepare("SELECT COALESCE(SUM(paid_amount),0) FROM sales WHERE sale_date = ?");
$sales_total->execute([$date]);
$sales_total = $sales_total->fetchColumn();

$purchase_total = $pdo->prepare("SELECT COALESCE(SUM(paid_amount),0) FROM purchases WHERE purchase_date = ?");
$purchase_total->execute([$date]);
$purchase_total = $purchase_total->fetchColumn();

$expense_total = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date = ?");
$expense_total->execute([$date]);
$expense_total = $expense_total->fetchColumn();

$payment_in = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE type = 'in' AND payment_date = ?");
$payment_in->execute([$date]);
$payment_in = $payment_in->fetchColumn();

$payment_out = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE type = 'out' AND payment_date = ?");
$payment_out->execute([$date]);
$payment_out = $payment_out->fetchColumn();

// Get opening balance from previous day's closing
$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
$opening = $pdo->prepare("SELECT closing_balance FROM cash_counter WHERE date = ?");
$opening->execute([$prev_date]);
$opening = $opening->fetchColumn() ?: 0;

$total_in = $opening + $sales_total + $payment_in;
$total_out = $purchase_total + $expense_total + $payment_out;
$closing = $total_in - $total_out;

// Save/update cash counter
$exists = $pdo->prepare("SELECT id FROM cash_counter WHERE date = ?");
$exists->execute([$date]);
if($exists->fetchColumn()) {
    $pdo->prepare("UPDATE cash_counter SET opening_balance=?, total_sales=?, total_purchases=?, total_expenses=?, total_payment_in=?, total_payment_out=?, closing_balance=? WHERE date=?")
        ->execute([$opening, $sales_total, $purchase_total, $expense_total, $payment_in, $payment_out, $closing, $date]);
} else {
    $pdo->prepare("INSERT INTO cash_counter (date, opening_balance, total_sales, total_purchases, total_expenses, total_payment_in, total_payment_out, closing_balance) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$date, $opening, $sales_total, $purchase_total, $expense_total, $payment_in, $payment_out, $closing]);
}

// Recent transactions
$recent_sales = $pdo->prepare("SELECT 'Sale' as type, invoice_no as ref, customer_name as party, paid_amount as amount, 'in' as direction FROM sales WHERE sale_date = ? AND paid_amount > 0");
$recent_sales->execute([$date]);
$recent_sales = $recent_sales->fetchAll(PDO::FETCH_ASSOC);

$recent_expenses = $pdo->prepare("SELECT 'Expense' as type, title as ref, category as party, amount, 'out' as direction FROM expenses WHERE expense_date = ?");
$recent_expenses->execute([$date]);
$recent_expenses = $recent_expenses->fetchAll(PDO::FETCH_ASSOC);

$recent_payments = $pdo->prepare("SELECT CONCAT('Payment ', UPPER(type)) as type, reference_no as ref, party_name as party, amount, type as direction FROM payments WHERE payment_date = ?");
$recent_payments->execute([$date]);
$recent_payments = $recent_payments->fetchAll(PDO::FETCH_ASSOC);

$all_transactions = array_merge($recent_sales, $recent_expenses, $recent_payments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Counter | ElectroERP</title>
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
        .date-bar { display: flex; gap: 12px; align-items: center; margin-bottom: 24px; }
        .date-bar input { padding: 9px 12px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 13px; }
        .btn { padding: 9px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px; }
        .card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        .card h4 { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
        .card p { font-size: 20px; font-weight: 700; color: #38bdf8; }
        .card.green p { color: #22c55e; }
        .card.red p { color: #ef4444; }
        .card.yellow p { color: #f59e0b; }
        .card.big { grid-column: span 2; background: #0f172a; border: 2px solid #38bdf8; }
        .card.big p { font-size: 28px; color: #38bdf8; }
        .section-box { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .section-box h3 { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #334155; color: #94a3b8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; background: #0f172a; }
        td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .empty { text-align: center; padding: 30px; color: #64748b; }
        @media print {
            .sidebar, .date-bar, .topbar { display: none; }
            .main { margin-left: 0; }
            body { background: white; color: black; }
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
    <a href="../returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <div class="nav-section">Banking</div>
    <a href="../payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="../payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="../expenses/index.php" class="nav-item"><span class="icon">🧾</span> Expenses</a>
    <a href="index.php" class="nav-item active"><span class="icon">🏦</span> Cash Counter</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h2>Cash Counter</h2>
        <button onclick="window.print()" class="btn btn-primary">🖨 Print</button>
    </div>
    <d