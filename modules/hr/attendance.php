<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$date = $_GET['date'] ?? date('Y-m-d');

if(isset($_GET['delete']) && isAdmin()) {
    $pdo->prepare("DELETE FROM attendance WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: attendance.php?date=$date&msg=deleted"); exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_ids = $_POST['employee_id'];
    $statuses = $_POST['status'];
    $notes = $_POST['notes'];
    foreach($employee_ids as $i => $emp_id) {
        $exists = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $exists->execute([$emp_id, $date]);
        if($exists->fetchColumn()) {
            $pdo->prepare("UPDATE attendance SET status = ?, notes = ? WHERE employee_id = ? AND date = ?")
                ->execute([$statuses[$i], $notes[$i], $emp_id, $date]);
        } else {
            $pdo->prepare("INSERT INTO attendance (employee_id, date, status, notes) VALUES (?, ?, ?, ?)")
                ->execute([$emp_id, $date, $statuses[$i], $notes[$i]]);
        }
    }
    header("Location: attendance.php?date=$date&msg=saved"); exit();
}

$employees = $pdo->query("SELECT * FROM employees WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$existing = $pdo->prepare("SELECT * FROM attendance WHERE date = ?");
$existing->execute([$date]);
$existing = $existing->fetchAll(PDO::FETCH_ASSOC);
$att_map = [];
foreach($existing as $a) { $att_map[$a['employee_id']] = $a; }

$all_records = $pdo->prepare("SELECT a.*, e.name as employee_name FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.date = ? ORDER BY e.name ASC");
$all_records->execute([$date]);
$all_records = $all_records->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance | ElectroERP</title>
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
        .date-bar { display: flex; gap: 12px; align-items: center; margin-bottom: 24px; background: #1e293b; padding: 16px 20px; border-radius: 12px; border: 1px solid #334155; }
        .date-bar label { font-size: 13px; color: #94a3b8; }
        .date-bar input { padding: 8px 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; }
        .btn { padding: 9px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-save { background: #22c55e; color: white; padding: 12px 30px; font-size: 14px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; margin-top: 20px; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .section-title { font-size: 16px; font-weight: 600; margin: 24px 0 16px; color: #38bdf8; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        select { padding: 7px 10px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; }
        input[type="text"] { padding: 7px 10px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; width: 100%; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-present { background: #dcfce7; color: #16a34a; }
        .badge-absent { background: #fee2e2; color: #dc2626; }
        .badge-half_day { background: #fef9c3; color: #ca8a04; }
        .badge-leave { background: #dbeafe; color: #2563eb; }
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
    <a href="attendance.php" class="nav-item active"><span class="icon">📅</span> Attendance</a>
    <a href="salary.php" class="nav-item"><span class="icon">💵</span> Salary</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="nav-section">Settings</div>
    <a href="../users/index.php" class="nav-item"><span class="icon">👤</span> User Management</a>
    <a href="../settings/index.php" class="nav-item"><span class="icon">⚙️</span> Company Settings</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h2>Attendance</h2>
        <?php if(isAdmin()): ?><span class="admin-badge">👑 Admin Mode</span><?php endif; ?>
    </div>
    <div class="content">
        <form method="GET" class="date-bar">
            <label>Date:</label>
            <input type="date" name="date" value="<?php echo $date; ?>">
            <button type="submit" class="btn btn-primary">Load</button>
        </form>
        <?php if(isset($_GET['msg'])): ?>
        <div class="success"><?php echo $_GET['msg']==='saved'?'✅ Attendance saved.':'🗑️ Record deleted.'; ?></div>
        <?php endif; ?>

        <?php if(empty($employees)): ?>
            <div style="text-align:center; padding:40px; color:#64748b;">No active employees. Add employees first.</div>
        <?php else: ?>
        <div class="section-title">Mark Attendance — <?php echo $date; ?></div>
        <form method="POST">
            <table>
                <thead><tr><th>#</th><th>Employee</th><th>Designation</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach($employees as $i => $emp): ?>
                <?php $att = $att_map[$emp['id']] ?? null; ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($emp['name']); ?><input type="hidden" name="employee_id[]" value="<?php echo $emp['id']; ?>"></td>
                    <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                    <td>
                        <select name="status[]">
                            <option value="present" <?php echo ($att && $att['status']==='present')?'selected':''; ?>>✅ Present</option>
                            <option value="absent" <?php echo ($att && $att['status']==='absent')?'selected':''; ?>>❌ Absent</option>
                            <option value="half_day" <?php echo ($att && $att['status']==='half_day')?'selected':''; ?>>⚡ Half Day</option>
                            <option value="leave" <?php echo ($att && $att['status']==='leave')?'selected':''; ?>>🏖️ Leave</option>
                        </select>
                    </td>
                    <td><input type="text" name="notes[]" value="<?php echo htmlspecialchars($att['notes'] ?? ''); ?>" placeholder="Optional"></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn-save">💾 Save Attendance</button>
        </form>
        <?php endif; ?>

        <?php if(!empty($all_records) && isAdmin()): ?>
        <div class="section-title">Saved Records — <?php echo $date; ?> (Admin: Delete)</div>
        <table>
            <thead><tr><th>#</th><th>Employee</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($all_records as $i => $r): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($r['employee_name']); ?></td>
                <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$r['status'])); ?></span></td>
                <td><?php echo htmlspecialchars($r['notes']); ?></td>
                <td><a href="attendance.php?date=<?php echo $date; ?>&delete=<?php echo $r['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this record?')">Delete</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>