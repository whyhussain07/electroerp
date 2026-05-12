<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

$id = (int)$_GET['id'];
$supplier = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$supplier->execute([$id]);
$s = $supplier->fetch(PDO::FETCH_ASSOC);

if(!$s) {
    header("Location: index.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $balance = (float)$_POST['balance'];
    $notes = trim($_POST['notes']);

    $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=?, city=?, balance=?, notes=? WHERE id=?");
    $stmt->execute([$name, $phone, $email, $address, $city, $balance, $notes, $id]);

    header("Location: index.php?msg=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Supplier | ElectroERP</title>
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
        .content { padding: 30px; max-width: 800px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #94a3b8; }
        input, textarea {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #f1f5f9;
            font-size: 14px;
        }
        input:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { resize: vertical; height: 80px; }
        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Suppliers</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <h2>Edit Supplier</h2>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group full">
                <label>Supplier Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($s['phone']); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($s['email']); ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($s['city']); ?>">
            </div>
            <div class="form-group">
                <label>Balance (PKR)</label>
                <input type="number" name="balance" value="<?php echo $s['balance']; ?>" step="0.01">
            </div>
            <div class="form-group full">
                <label>Address</label>
                <textarea name="address"><?php echo htmlspecialchars($s['address']); ?></textarea>
            </div>
            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes"><?php echo htmlspecialchars($s['notes']); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Supplier</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>