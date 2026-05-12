<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("INSERT INTO expenses (title, category, amount, payment_method, expense_date, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([trim($_POST['title']), trim($_POST['category']), (float)$_POST['amount'], $_POST['payment_method'], $_POST['expense_date'], trim($_POST['note']), $_SESSION['user_id']]);
    header("Location: index.php?msg=added"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expense | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 600px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Expenses</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>Add Expense</h2>
    <form method="POST">
        <div class="form-group"><label>Title *</label><input type="text" name="title" placeholder="e.g. Electricity Bill, Rent, Salary" required></div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" placeholder="e.g. Utilities, Rent, Transport" list="cat_suggestions">
                <datalist id="cat_suggestions">
                    <option value="Rent">
                    <option value="Electricity">
                    <option value="Internet">
                    <option value="Salary">
                    <option value="Transport">
                    <option value="Repair">
                    <option value="Miscellaneous">
                </datalist>
            </div>
            <div class="form-group"><label>Amount (PKR) *</label><input type="number" name="amount" step="0.01" placeholder="0" required></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>"></div>
        </div>
        <div class="form-group"><label>Note</label><input type="text" name="note" placeholder="Optional"></div>
        <button type="submit" class="btn btn-primary">Save Expense</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>