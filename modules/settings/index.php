<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE company_settings SET company_name=?, address=?, phone=?, email=?, city=?, ntn=?, strn=?, footer_note=? WHERE id=1")
        ->execute([trim($_POST['company_name']), trim($_POST['address']), trim($_POST['phone']), trim($_POST['email']), trim($_POST['city']), trim($_POST['ntn']), trim($_POST['strn']), trim($_POST['footer_note'])]);
    header("Location: index.php?msg=saved"); exit();
}

$settings = $pdo->query("SELECT * FROM company_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Settings | ElectroERP</title>
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
        .content { padding: 30px; max-width: 800px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #94a3b8; }
        input, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { resize: vertical; height: 80px; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .section-title { font-size: 14px; color: #38bdf8; margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid #334155; grid-column: span 2; }
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
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="nav-section">Settings</div>
    <a href="index.php" class="nav-item active"><span class="icon">⚙️</span> Company Settings</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Company Settings</h2></div>
    <div class="content">
        <?php if(isset($_GET['msg'])): ?>
        <div class="success">✅ Settings saved successfully.</div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-grid">
                <div class="section-title">🏢 Business Information</div>
                <div class="form-group full"><label>Company Name *</label><input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>"></div>
                <div class="form-group"><label>City</label><input type="text" name="city" value="<?php echo htmlspecialchars($settings['city']); ?>"></div>
                <div class="form-group"><label>NTN Number</label><input type="text" name="ntn" value="<?php echo htmlspecialchars($settings['ntn']); ?>" placeholder="National Tax Number"></div>
                <div class="form-group full"><label>Address</label><textarea name="address"><?php echo htmlspecialchars($settings['address']); ?></textarea></div>
                <div class="section-title">🧾 Invoice Settings</div>
                <div class="form-group"><label>STRN Number</label><input type="text" name="strn" value="<?php echo htmlspecialchars($settings['strn']); ?>" placeholder="Sales Tax Registration Number"></div>
                <div class="form-group full"><label>Invoice Footer Note</label><textarea name="footer_note" placeholder="e.g. Thank you for your business! Goods once sold will not be returned."><?php echo htmlspecialchars($settings['footer_note']); ?></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Settings</button>
        </form>
    </div>
</div>
</body>
</html>