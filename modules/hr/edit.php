<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$e = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$e->execute([$id]);
$e = $e->fetch(PDO::FETCH_ASSOC);
if(!$e) { header("Location: index.php"); exit(); }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE employees SET name=?, phone=?, email=?, designation=?, department=?, salary=?, joining_date=?, status=?, address=?, notes=? WHERE id=?")
        ->execute([trim($_POST['name']), trim($_POST['phone']), trim($_POST['email']), trim($_POST['designation']), trim($_POST['department']), (float)$_POST['salary'], $_POST['joining_date'], $_POST['status'], trim($_POST['address']), trim($_POST['notes']), $id]);
    header("Location: index.php?msg=updated"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Employee | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 800px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { resize: vertical; height: 80px; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Employees</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>Edit Employee</h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group full"><label>Full Name *</label><input type="text" name="name" value="<?php echo htmlspecialchars($e['name']); ?>" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($e['phone']); ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($e['email']); ?>"></div>
            <div class="form-group"><label>Designation</label><input type="text" name="designation" value="<?php echo htmlspecialchars($e['designation']); ?>"></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" value="<?php echo htmlspecialchars($e['department']); ?>"></div>
            <div class="form-group"><label>Monthly Salary (PKR)</label><input type="number" name="salary" value="<?php echo $e['salary']; ?>" step="0.01"></div>
            <div class="form-group"><label>Joining Date</label><input type="date" name="joining_date" value="<?php echo $e['joining_date']; ?>"></div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?php echo $e['status']==='active'?'selected':''; ?>>Active</option>
                    <option value="inactive" <?php echo $e['status']==='inactive'?'selected':''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group full"><label>Address</label><textarea name="address"><?php echo htmlspecialchars($e['address']); ?></textarea></div>
            <div class="form-group full"><label>Notes</label><textarea name="notes"><?php echo htmlspecialchars($e['notes']); ?></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary">Update Employee</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>