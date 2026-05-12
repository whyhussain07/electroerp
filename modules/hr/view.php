<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$e = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$e->execute([$id]);
$e = $e->fetch(PDO::FETCH_ASSOC);
if(!$e) { header("Location: index.php"); exit(); }

$attendance = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30");
$attendance->execute([$id]);
$attendance = $attendance->fetchAll(PDO::FETCH_ASSOC);

$salary_history = $pdo->prepare("SELECT * FROM salary_payments WHERE employee_id = ? ORDER BY payment_date DESC");
$salary_history->execute([$id]);
$salary_history = $salary_history->fetchAll(PDO::FETCH_ASSOC);

$present = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'present' AND MONTH(date) = MONTH(NOW())");
$present->execute([$id]);
$present = $present->fetchColumn();

$absent = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'absent' AND MONTH(date) = MONTH(NOW())");
$absent->execute([$id]);
$absent = $absent->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 900px; }
        .info-box { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 24px; border: 1px solid #334155; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-box h2 { font-size: 20px; grid-column: span 2; margin-bottom: 8px; }
        .info-item label { font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; }
        .info-item p { font-size: 14px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat { background: #1e293b; border-radius: 10px; padding: 18px; border: 1px solid #334155; }
        .stat h4 { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
        .stat p { font-size: 20px; font-weight: 700; color: #38bdf8; }
        .stat.green p { color: #22c55e; }
        .stat.red p { color: #ef4444; }
        .section-box { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; margin-bottom: 24px; }
        .section-box h3 { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #334155; color: #94a3b8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; background: #0f172a; }
        td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-present { background: #dcfce7; color: #16a34a; }
        .badge-absent { background: #fee2e2; color: #dc2626; }
        .badge-half_day { background: #fef9c3; color: #ca8a04; }
        .badge-leave { background: #dbeafe; color: #2563eb; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #334155; color: #f1f5f9; }
        .btn-edit { background: #f59e0b; color: #0f172a; margin-left: 10px; }
        .empty { text-align: center; padding: 20px; color: #64748b; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Employees</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <div class="info-box">
        <h2><?php echo htmlspecialchars($e['name']); ?></h2>
        <div class="info-item"><label>Designation</label><p><?php echo htmlspecialchars($e['designation']); ?></p></div>
        <div class="info-item"><label>Department</label><p><?php echo htmlspecialchars($e['department']); ?></p></div>
        <div class="info-item"><label>Phone</label><p><?php echo htmlspecialchars($e['phone']); ?></p></div>
        <div class="info-item"><label>Email</label><p><?php echo htmlspecialchars($e['email']); ?></p></div>
        <div class="info-item"><label>Joining Date</label><p><?php echo $e['joining_date']; ?></p></div>
        <div class="info-item"><label>Monthly Salary</label><p>PKR <?php echo number_format($e['salary'], 0); ?></p></div>
    </div>
    <div class="stats">
        <div class="stat green"><h4>Present This Month</h4><p><?php echo $present; ?></p></div>
        <div class="stat red"><h4>Absent This Month</h4><p><?php echo $absent; ?></p></div>
        <div class="stat"><h4>Monthly Salary</h4><p>PKR <?php echo number_format($e['salary'], 0); ?></p></div>
        <div class="stat"><h4>Total Payments</h4><p><?php echo count($salary_history); ?></p></div>
    </div>
    <div class="section-box">
        <h3>Recent Attendance (Last 30 days)</h3>
        <table>
            <thead><tr><th>Date</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if(empty($attendance)): ?>
                <tr><td colspan="3" class="empty">No attendance records.</td></tr>
            <?php else: ?>
                <?php foreach($attendance as $a): ?>
                <tr>
                    <td><?php echo $a['date']; ?></td>
                    <td><span class="badge badge-<?php echo $a['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $a['status'])); ?></span></td>
                    <td><?php echo htmlspecialchars($a['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="section-box">
        <h3>Salary Payment History</h3>
        <table>
            <thead><tr><th>Month</th><th>Amount</th><th>Method</th><th>Date</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if(empty($salary_history)): ?>
                <tr><td colspan="5" class="empty">No salary payments yet.</td></tr>
            <?php else: ?>
                <?php foreach($salary_history as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['month']); ?></td>
                    <td>PKR <?php echo number_format($s['amount'], 0); ?></td>
                    <td><?php echo ucfirst($s['payment_method']); ?></td>
                    <td><?php echo $s['payment_date']; ?></td>
                    <td><?php echo htmlspecialchars($s['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
    <a href="edit.php?id=<?php echo $e['id']; ?>" class="btn btn-edit">Edit Employee</a>
</div>
</body>
</html>