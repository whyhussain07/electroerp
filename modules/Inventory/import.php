<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$errors = [];
$success = 0;

// Handle download template
if(isset($_GET['template'])) {
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Products');

    // Headers
    $headers = ['name', 'brand', 'model', 'category', 'imei_serial', 'purchase_price', 'mrp_price', 'sale_price', 'stock', 'low_stock_alert', 'description'];
    foreach($headers as $i => $h) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col.'1', strtoupper($h));
        $sheet->getStyle($col.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f172a']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->getColumnDimension($col)->setWidth(20);
    }

    // Sample row
    $sample = ['Samsung Galaxy A55', 'Samsung', 'SM-A556', 'Smartphone', '', '85000', '95000', '92000', '10', '3', 'Sample product'];
    foreach($sample as $i => $val) {
        $sheet->setCellValue(chr(65+$i).'2', $val);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="electroerp_products_template.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Handle import
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $allowed = ['xlsx', 'xls', 'csv'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if(!in_array($ext, $allowed)) {
        $errors[] = 'Invalid file type. Please upload .xlsx, .xls, or .csv file.';
    } elseif($file['size'] > 5000000) {
        $errors[] = 'File too large. Maximum 5MB allowed.';
    } else {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header row
            array_shift($rows);

            $categories = $pdo->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

            foreach($rows as $row_num => $row) {
                // Skip empty rows
                if(empty(array_filter($row))) continue;

                $name = trim($row[0] ?? '');
                if(empty($name)) continue;

                $brand = trim($row[1] ?? '');
                $model = trim($row[2] ?? '');
                $category = trim($row[3] ?? '');
                $imei_serial = trim($row[4] ?? '');
                $purchase_price = (float)($row[5] ?? 0);
                $mrp_price = (float)($row[6] ?? 0);
                $sale_price = (float)($row[7] ?? 0);
                $stock = (int)($row[8] ?? 0);
                $low_stock_alert = (int)($row[9] ?? 5);
                $description = trim($row[10] ?? '');

                // Auto add category if not exists
                if(!empty($category) && !in_array($category, $categories)) {
                    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$category]);
                    $categories[] = $category;
                }

                $pdo->prepare("INSERT INTO products (name, brand, model, category, imei_serial, purchase_price, mrp_price, sale_price, stock, low_stock_alert, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$name, $brand, $model, $category, $imei_serial, $purchase_price, $mrp_price, $sale_price, $stock, $low_stock_alert, $description]);

                $success++;
            }
        } catch(Exception $e) {
            $errors[] = 'Error reading file: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Products | ElectroERP</title>
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
        .template-box {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #334155;
        }
        .template-box h3 { font-size: 16px; color: #38bdf8; margin-bottom: 12px; }
        .template-box p { font-size: 13px; color: #94a3b8; margin-bottom: 16px; line-height: 1.6; }
        .columns-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .col-item {
            background: #0f172a;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            border: 1px solid #334155;
        }
        .col-item span { font-size: 11px; color: #64748b; display: block; margin-top: 2px; }
        .btn-download {
            display: inline-block;
            padding: 12px 24px;
            background: #22c55e;
            color: white;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
        }
        .upload-box {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #334155;
        }
        .upload-box h3 { font-size: 16px; color: #38bdf8; margin-bottom: 16px; }
        .drop-zone {
            border: 2px dashed #334155;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .drop-zone:hover { border-color: #38bdf8; }
        .drop-zone p { color: #94a3b8; font-size: 14px; margin-top: 8px; }
        .drop-zone .icon { font-size: 40px; }
        input[type="file"] { display: none; }
        .file-name { font-size: 13px; color: #38bdf8; margin-top: 8px; }
        .btn-import {
            width: 100%;
            padding: 14px;
            background: #38bdf8;
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-import:hover { background: #0ea5e9; }
        .success-box { background: #dcfce7; color: #16a34a; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .error-box { background: #fee2e2; color: #dc2626; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .error-box ul { margin-top: 8px; padding-left: 20px; }
        .rules-box {
            background: #0f172a;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.8;
        }
        .rules-box strong { color: #f1f5f9; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">⚡ ElectroERP<span>Mobile & Electronics</span></div>
    <div class="nav-section">Main</div>
    <a href="../../dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
    <div class="nav-section">Inventory</div>
    <a href="index.php" class="nav-item"><span class="icon">📦</span> Products</a>
    <a href="add.php" class="nav-item"><span class="icon">➕</span> Add Product</a>
    <a href="categories.php" class="nav-item"><span class="icon">🏷️</span> Categories</a>
    <a href="bulk_price.php" class="nav-item"><span class="icon">💲</span> Bulk Price Change</a>
    <a href="import.php" class="nav-item active"><span class="icon">📤</span> Import from Excel</a>
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
    <a href="../hr/index.php" class="nav-item"><span class="icon">👨‍💼</span> Employees</a>
    <a href="../hr/attendance.php" class="nav-item"><span class="icon">📅</span> Attendance</a>
    <a href="../hr/salary.php" class="nav-item"><span class="icon">💵</span> Salary</a>
    <div class="nav-section">Reports</div>
    <a href="../reports/index.php" class="nav-item"><span class="icon">📈</span> All Reports</a>
    <div class="nav-section">Settings</div>
    <a href="../users/index.php" class="nav-item"><span class="icon">👤</span> User Management</a>
    <a href="../settings/index.php" class="nav-item"><span class="icon">⚙️</span> Company Settings</a>
    <div class="sidebar-footer"><a href="../../logout.php">🚪 Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h2>Import Products from Excel</h2></div>
    <div class="content">

        <?php if($success > 0): ?>
        <div class="success-box">✅ Successfully imported <strong><?php echo $success; ?> products</strong> into inventory!</div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
        <div class="error-box">
            ❌ Import failed:
            <ul><?php foreach($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <!-- Template Section -->
        <div class="template-box">
            <h3>📋 Step 1 — Download Template</h3>
            <p>Download the Excel template below. Fill in your product data following the column format, then upload it back. Do not change column headers or column order.</p>

            <div class="columns-grid">
                <div class="col-item">NAME <span>Product full name *</span></div>
                <div class="col-item">BRAND <span>e.g. Samsung, LG</span></div>
                <div class="col-item">MODEL <span>e.g. SM-A556</span></div>
                <div class="col-item">CATEGORY <span>e.g. Smartphone</span></div>
                <div class="col-item">IMEI_SERIAL <span>Optional</span></div>
                <div class="col-item">PURCHASE_PRICE <span>Numbers only, no PKR</span></div>
                <div class="col-item">MRP_PRICE <span>Numbers only</span></div>
                <div class="col-item">SALE_PRICE <span>Numbers only</span></div>
                <div class="col-item">STOCK <span>Current quantity</span></div>
                <div class="col-item">LOW_STOCK_ALERT <span>Default: 5</span></div>
                <div class="col-item">DESCRIPTION <span>Optional notes</span></div>
            </div>

            <a href="import.php?template=1" class="btn-download">⬇️ Download Excel Template</a>
        </div>

        <!-- Upload Section -->
        <div class="upload-box">
            <h3>📤 Step 2 — Upload Your File</h3>
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <div class="drop-zone" onclick="document.getElementById('excel_file').click()">
                    <div class="icon">📊</div>
                    <p>Click to select your Excel file</p>
                    <p style="font-size:12px;">Supports .xlsx, .xls, .csv — Max 5MB</p>
                    <div class="file-name" id="file-name"></div>
                </div>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv" onchange="showFileName(this)">
                <button type="submit" class="btn-import">📤 Import Products</button>
            </form>

            <div class="rules-box">
                <strong>Important Rules:</strong><br>
                • Row 1 is the header — do not delete or modify it<br>
                • Only <strong>NAME</strong> column is required — others are optional<br>
                • Categories will be auto-created if they don't exist<br>
                • Leave price/stock fields as 0 if unknown<br>
                • Do not merge cells or add extra sheets<br>
                • Save your file as .xlsx before uploading
            </div>
        </div>
    </div>
</div>
<script>
function showFileName(input) {
    const name = input.files[0]?.name || '';
    document.getElementById('file-name').textContent = name ? '📎 ' + name : '';
}
</script>
</body>
</html>