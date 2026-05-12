<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

// Add category
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if($name !== '') {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
        header("Location: categories.php?msg=added");
        exit();
    }
}

// Delete category
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    header("Location: categories.php?msg=deleted");
    exit();
}

// Fetch all
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories | ElectroERP</title>
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
        .content { padding: 30px; max-width: 600px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .add-form {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
        }
        input[type="text"] {
            flex: 1;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #f1f5f9;
            font-size: 14px;
        }
        input[type="text"]:focus { outline: none; border-color: #38bdf8; }
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
        .success { background: #dcfce7; color: #16a34a; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        thead { background: #334155; }
        th { padding: 14px 16px; text-align: left; font-size: 13px; color: #94a3b8; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
        .empty { text-align: center; padding: 30px; color: #94a3b8; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Inventory</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <h2>Manage Categories</h2>

    <?php if(isset($_GET['msg'])): ?>
        <div class="success">
            <?php
                if($_GET['msg'] === 'added') echo 'Category added.';
                if($_GET['msg'] === 'deleted') echo 'Category deleted.';
            ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="add-form">
        <input type="text" name="name" placeholder="Enter category name e.g. Used Phones" required>
        <button type="submit" class="btn btn-primary">+ Add</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($categories)): ?>
            <tr><td colspan="3" class="empty">No categories yet.</td></tr>
        <?php else: ?>
            <?php foreach($categories as $i => $cat): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td>
                    <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>