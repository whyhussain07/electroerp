<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int)$_POST['supplier_id'];
    $invoice_no = trim($_POST['invoice_no']);
    $purchase_date = $_POST['purchase_date'];
    $paid_amount = (float)$_POST['paid_amount'];
    $notes = trim($_POST['notes']);

    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['purchase_price'];

    $total_amount = 0;
    foreach($quantities as $i => $qty) {
        $total_amount += (float)$prices[$i] * (int)$qty;
    }

    $balance = $total_amount - $paid_amount;

    // Insert purchase header
    $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, invoice_no, purchase_date, total_amount, paid_amount, balance, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$supplier_id, $invoice_no, $purchase_date, $total_amount, $paid_amount, $balance, $notes]);
    $purchase_id = $pdo->lastInsertId();

    // Insert items and update stock
    foreach($product_ids as $i => $product_id) {
        $qty = (int)$quantities[$i];
        $price = (float)$prices[$i];
        $total = $qty * $price;

        $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, purchase_price, total) VALUES (?, ?, ?, ?, ?)")
            ->execute([$purchase_id, $product_id, $qty, $price, $total]);

        // Update stock
        $pdo->prepare("UPDATE products SET stock = stock + ?, purchase_price = ? WHERE id = ?")
            ->execute([$qty, $price, $product_id]);
    }

    // Update supplier balance
    if($balance > 0) {
        $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?")->execute([$balance, $supplier_id]);
    }

    header("Location: index.php?msg=added");
    exit();
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Purchase | ElectroERP</title>
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
        .content { padding: 30px; max-width: 1000px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #f1f5f9;
            font-size: 14px;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        textarea { resize: vertical; height: 60px; }
        .items-section { margin-bottom: 24px; }
        .items-section h3 { font-size: 16px; margin-bottom: 16px; color: #94a3b8; }
        .item-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .item-row.header { margin-bottom: 8px; }
        .item-row.header span { font-size: 12px; color: #64748b; }
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
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
        .btn-add-row { background: #1e293b; color: #38bdf8; border: 1px dashed #38bdf8; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-bottom: 20px; }
        .btn-remove { background: #ef4444; color: white; border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 12px; }
        .total-box {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: flex-end;
            gap: 40px;
        }
        .total-box div { text-align: right; }
        .total-box label { display: block; font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .total-box span { font-size: 20px; font-weight: 700; color: #38bdf8; }
        #balance-display { color: #ef4444; }
    </style>
</head>
<body>

<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div>
        <a href="index.php">← Purchases</a>
        <a href="../../dashboard.php">Dashboard</a>
        <a href="../../logout.php">Logout</a>
    </div>
</div>

<div class="content">
    <h2>New Purchase Invoice</h2>

    <form method="POST" id="purchaseForm">
        <div class="form-grid">
            <div class="form-group">
                <label>Supplier *</label>
                <select name="supplier_id" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Invoice No</label>
                <input type="text" name="invoice_no" placeholder="e.g. INV-001">
            </div>
            <div class="form-group">
                <label>Purchase Date *</label>
                <input type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Paid Amount (PKR)</label>
                <input type="number" name="paid_amount" id="paid_amount" placeholder="0" step="0.01" value="0" oninput="updateTotals()">
            </div>
            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes" placeholder="Optional notes"></textarea>
            </div>
        </div>

        <div class="items-section">
            <h3>Items</h3>
            <div class="item-row header">
                <span>Product</span>
                <span>Quantity</span>
                <span>Purchase Price</span>
                <span>Total</span>
                <span></span>
            </div>
            <div id="items-container">
                <div class="item-row">
                    <select name="product_id[]" required onchange="fillPrice(this)">
                        <option value="">-- Select Product --</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['purchase_price']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity[]" placeholder="0" min="1" value="1" oninput="updateTotals()">
                    <input type="number" name="purchase_price[]" placeholder="0" step="0.01" value="0" oninput="updateTotals()">
                    <input type="text" readonly id="row-total-0" value="PKR 0.00" style="background:#0f172a; color:#38bdf8;">
                    <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                </div>
            </div>
            <button type="button" class="btn-add-row" onclick="addRow()">+ Add Item</button>
        </div>

        <div class="total-box">
            <div>
                <label>Total Amount</label>
                <span id="total-display">PKR 0.00</span>
            </div>
            <div>
                <label>Paid Amount</label>
                <span id="paid-display">PKR 0.00</span>
            </div>
            <div>
                <label>Balance Due</label>
                <span id="balance-display">PKR 0.00</span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Purchase</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
let rowCount = 1;

function fillPrice(select) {
    const row = select.closest('.item-row');
    const price = select.options[select.selectedIndex].dataset.price || 0;
    row.querySelector('input[name="purchase_price[]"]').value = price;
    updateTotals();
}

function updateTotals() {
    const rows = document.querySelectorAll('#items-container .item-row');
    let total = 0;
    rows.forEach((row, i) => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="purchase_price[]"]').value) || 0;
        const rowTotal = qty * price;
        total += rowTotal;
        const totalField = row.querySelector('input[id^="row-total"]');
        if(totalField) totalField.value = 'PKR ' + rowTotal.toFixed(2);
    });
    const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
    const balance = total - paid;
    document.getElementById('total-display').textContent = 'PKR ' + total.toFixed(2);
    document.getElementById('paid-display').textContent = 'PKR ' + paid.toFixed(2);
    document.getElementById('balance-display').textContent = 'PKR ' + balance.toFixed(2);
}

function addRow() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `
        <select name="product_id[]" required onchange="fillPrice(this)">
            <option value="">-- Select Product --</option>
            <?php foreach($products as $p): ?>
            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['purchase_price']; ?>">
                <?php echo htmlspecialchars($p['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" placeholder="0" min="1" value="1" oninput="updateTotals()">
        <input type="number" name="purchase_price[]" placeholder="0" step="0.01" value="0" oninput="updateTotals()">
        <input type="text" readonly id="row-total-${rowCount}" value="PKR 0.00" style="background:#0f172a; color:#38bdf8;">
        <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
    `;
    container.appendChild(div);
    rowCount++;
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#items-container .item-row');
    if(rows.length === 1) return;
    btn.closest('.item-row').remove();
    updateTotals();
}
</script>

</body>
</html>