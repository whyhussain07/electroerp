<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password']));
    $role = $_POST['role'];

    $exists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $exists->execute([$email]);
    if($exists->fetch()) {
        $error = 'Email already exists.';
    } else {
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
            ->execute([$name, $email, $password, $role]);
        header("Location: index.php?msg=added"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User | ElectroERP</title>
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
        input, select { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus { outline: none; border-color: #38bdf8; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
        .error { background: #fee2e2; color: #dc2626; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php">← Users</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>Add New User</h2>
    <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group"><label>Full Name *</label><input type="text" name="name" required placeholder="e.g. Ahmed Ali"></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="user@email.com"></div>
        <div class="form-row">
            <div class="form-group"><label>Password *</label><input type="password" name="password" required placeholder="Min 6 characters"></div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role">
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save User</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>