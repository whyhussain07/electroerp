<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if(isset($_GET['delete']) && isAdmin()) {
    $pdo->prepare("DELETE FROM salary_payments WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: salary.php?msg=deleted"); exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("INSERT INTO salary_payments (employee_id, amount, month, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([(int)$_POST['employee_id'], (float)$_POST['amount'], trim($_POST['month']), $_POST['payment_date'], $_POST['payment_method'], trim($_POST['notes'])]);
    header("Location: salary.php?msg=paid"); exit();
}

$employees = $pdo->query("SELECT * FROM employees WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$payments = $pdo->query("SELECT sp.*, e.name as employee_name FROM salary_payments sp LEFT JOIN employees e ON sp.employee_id = e.id ORDER BY sp.payment_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; display: flex; }
        .sidebar { width: 240px; height: 100vh; background: #1e293b; border-right: 1px solid #334155; display: flex; flex-direction: column; position: fixed; left: 0; top: 0; overflow-y: auto; }
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
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px; }
        .form-box { background: #1e293b; border-radius: 12px; padding: 24px; border: 1px solid #334155; }
        .form-box h3 { font-size: 16px; margin-bottom: 20px; color: #38bdf8; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        label { font-size: 13px; color: #94a3b8; }
        input, select { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus { outline: none; border-color: #38bdf8; }
        .btn-save { width: 100%; padding: 12px; background: #22c55e; color: white; font-size: 14px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; margin-top: 8px; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .section-box { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .section-box h3 { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #334155; color: #94a3b8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; background: #0f172a; }
        td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .empty { text-align: center; padding: 30px; color: #64748b; }
        .admin-badge { background: #dbeafe; color: #2563eb; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
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
    <a href="../inventory/bulk_price.php" class="nav-item"><span class="icon">💲</span> Bulk Price Change</a>
    <a href="../stock/index.php" class="nav-item"><span class="icon">🔧</span> Stock Adjustment</a>
    <div class="nav-section">Transactions</div>
    <a href="../sales/pos.php" class="nav-item"><span class="icon">🖥️</span> POS</a>
    <a href="../sales/index.php" class="nav-item"><span class="icon">🧾</span> Sales</a>
    <a href="../purchases/index.php" class="nav-item"><span class="icon">📥</span> Purchases</a>
    <a href="../returns/index.php" class="nav-item"><span class="icon">↩️</span> Returns</a>
    <a href="../quotations/index.php" class="nav-item"><span class="icon">📋</span> Quotations</a>
    <div class="nav-section">Banking</div>
    <a href="../payments/index.php?type=in" class="nav-item"><span class="icon">💰</span> Payment In</a>
    <a href="../payments/index.php?type=out" class="nav-item"><span class="icon">💸</span> Payment Out</a>
    <a href="../expenses/index.php" class="nav-item"><span class="icon">🧾</span> Expenses</a>
    <a href="../cash/index.php" class="nav-item"><span class="icon">🏦</span> Cash Counter</a>
    <div class="nav-section">Masters</div>
    <a href="../customers/index.php" class="nav-item"><span class="icon">👥</span> Customers</a>
    <a href="../suppliers/index.php" class="nav-item"><span class="icon">🏭</span> Suppliers</a>
    <div class="nav-section">HR</div>
    <a href="index.php" class="nav-item"><span class="icon">👨‍💼</span> Employees</a>
    <a href="attendance.php" class="nav-item"><span class="icon">📅</span> Attendance</a>
    <a href="salary.php" class="nav-item active"><span class="icon">💵</span> Salary</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="nav-section">Settings</div>
    <a href="../users/index.php" class="nav-item"><span class="icon">👤</span> User Management</a>
    <a href="../settings/index.php" class="nav-item"><span class="icon">⚙️</span> Company Settings</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h2>Salary Payments</h2>
        <?php if(isAdmin()): ?><span class="admin-badge">👑 Admin Mode</span><?php endif; ?>
    </div>
    <div class="content">
        <?php if(isset($_GET['msg'])): ?>
        <div class="success"><?php echo $_GET['msg']==='paid'?'✅ Salary recorded.':'🗑️ Payment deleted.'; ?></div>
        <?php endif; ?>
        <div class="grid-2">
            <div class="form-box">
                <h3>💵 Pay Salary</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Employee *</label>
                        <select name="employee_id" required onchange="fillSalary(this)">
                            <option value="">-- Select Employee --</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" data-salary="<?php echo $emp['salary']; ?>"><?php echo htmlspecialchars($emp['name']); ?> — PKR <?php echo number_format($emp['salary'],0); ?>/mo</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Month</label><input type="text" name="month" value="<?php echo date('F Y'); ?>"></div>
                    <div class="form-group"><label>Amount (PKR) *</label><input type="number" name="amount" id="salary_amount" step="0.01" value="0" required></div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group"><label>Notes</label><input type="text" name="notes" placeholder="Optional"></div>
                    <button type="submit" class="btn-save">💾 Record Payment</button>
                </form>
            </div>
            <div class="section-box">
                <h3>Salary Payment History</h3>
                <table>
                    <thead><tr><th>Employee</th><th>Month</th><th>Amount</th><th>Date</th><?php if(isAdmin()): ?><th>Action</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="5" class="empty">No payments yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['month']); ?></td>
                            <td>PKR <?php echo number_format($p['amount'], 0); ?></td>
                            <td><?php echo $p['payment_date']; ?></td>
                            <?php if(isAdmin()): ?>
                            <td><a href="salary.php?delete=<?php echo $p['id']; ?>" class="btn-danger" onclick="return confirm('Delete this payment?')">Delete</a></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function fillSalary(select) {
    const salary = select.options[select.selectedIndex].dataset.salary || 0;
    document.getElementById('salary_amount').value = salary;
}
</script>
</body>
</html>