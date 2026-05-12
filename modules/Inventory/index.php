<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header("Location: index.php?msg=deleted");
    exit();
}

// Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | ElectroERP</title>
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
        .topbar a:hover { color: #f1f5f9; }
        .content { padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header h2 { font-size: 20px; }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-danger { background: #ef4444; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-edit { background: #f59e0b; color: #0f172a; font-size: 12px; padding: 6px 12px; }
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #263348; }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-low { background: #fee2e2; color: #dc2626; }
        .badge-ok { background: #dcfce7; color: #16a34a; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <div class="page-header">
        <h2>Inventory Management</h2>
        <a href="add.php" class="btn btn-primary">+ Add Product</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="success">
            <?php
                if($_GET['msg'] === 'added') echo 'Product added successfully.';
                if($_GET['msg'] === 'updated') echo 'Product updated successfully.';
                if($_GET['msg'] === 'deleted') echo 'Product deleted.';
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Category</th>
                <th>Purchase Price</th>
                <th>MRP Price</th>
                <th>Wholesale Price</th>
                <th>Stock Quantity</th>
                <th>Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($products)): ?>
            <tr><td colspan="10" class="empty">No products yet. Add your first product.</td></tr>
        <?php else: ?>
            <?php foreach($products as $i => $p): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['brand']); ?></td>
                <td><?php echo htmlspecialchars($p['model']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
                <td>PKR <?php echo number_format($p['purchase_price'], 2); ?></td>
                <td>PKR <?php echo number_format($p['mrp_price'], 2); ?></td>
                <td>PKR <?php echo number_format($p['sale_price'], 2); ?></td>
                <td><?php echo $p['stock']; ?></td>
                <td>
                    <?php if($p['stock'] <= $p['low_stock_alert']): ?>
                        <span class="badge badge-low">Low Stock</span>
                    <?php else: ?>
                        <span class="badge badge-ok">In Stock</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-edit">Edit</a>
                    <a href="index.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>