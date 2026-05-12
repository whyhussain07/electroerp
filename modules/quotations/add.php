<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']) ?: 'Walk-in Customer';
    $customer_phone = trim($_POST['customer_phone']);
    $quote_date = $_POST['quote_date'];
    $valid_until = $_POST['valid_until'];
    $discount = (float)$_POST['discount'];
    $notes = trim($_POST['notes']);

    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['sale_price'];

    $total_amount = 0;
    foreach($quantities as $i => $qty) {
        $total_amount += (float)$prices[$i] * (int)$qty;
    }
    $grand_total = $total_amount - $discount;

    $last = $pdo->query("SELECT MAX(id) as last_id FROM quotations")->fetch(PDO::FETCH_ASSOC);
    $next_id = ($last['last_id'] ?? 0) + 1;
    $quote_no = 'QUO-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO quotations (customer_name, customer_phone, quote_no, quote_date, valid_until, total_amount, discount, grand_total, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customer_name, $customer_phone, $quote_no, $quote_date, $valid_until, $total_amount, $discount, $grand_total, $notes, $_SESSION['user_id']]);
    $quote_id = $pdo->lastInsertId();

    foreach($product_ids as $i => $product_id) {
        $qty = (int)$quantities[$i];
        $price = (float)$prices[$i];
        $pdo->prepare("INSERT INTO quotation_items (quotation_id, product_id, quantity, sale_price, total) VALUES (?, ?, ?, ?, ?)")
            ->execute([$quote_id, $product_id, $qty, $price, $qty * $price]);
    }

    header("Location: view.php?id=$quote_id&new=1"); exit();
}

$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$customers = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Quotation | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 14px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .pos-layout { display: grid; grid-template-columns: 1fr 360px; height: calc(100vh - 57px); }
        .pos-left { padding: 24px; overflow-y: auto; }
        .pos-right { background: #1e293b; border-left: 1px solid #334155; padding: 24px; display: flex; flex-direction: column; overflow-y: auto; }
        h3 { font-size: 15px; color: #94a3b8; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        label { font-size: 12px; color: #64748b; }
        input, select { padding: 9px 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 13px; width: 100%; }
        input:focus, select:focus { outline: none; border-color: #38bdf8; }
        .item-row { display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 8px; margin-bottom: 10px; align-items: center; }
        .item-row.header span { font-size: 11px; color: #64748b; }
        .item-row.header { margin-bottom: 8px; }
        .btn-remove { background: #ef4444; color: white; border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 12px; }
        .btn-add-row { background: transparent; color: #38bdf8; border: 1px dashed #38bdf8; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-bottom: 20px; width: 100%; }
        .totals-box { margin-top: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; border-bottom: 1px solid #334155; }
        .total-row.grand { font-size: 18px; font-weight: 700; color: #38bdf8; }
        .btn-save { width: 100%; padding: 14px; background: #38bdf8; color: #0f172a; font-size: 16px; font-weight: 700; border: none; border-radius: 10px; cursor: pointer; margin-top: 16px; }
        .divider { border: none; border-top: 1px solid #334155; margin: 16px 0; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP — New Quotation</h1>
    <div><a href="index.php">← Quotations</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<form method="POST">
<div class="pos-layout">
    <div class="pos-left">
        <h3>Items</h3>
        <div class="item-row header">
            <span>Product</span><span>Qty</span><span>Price (PKR)</span><span></span>
        </div>
        <div id="items-container">
            <div class="item-row">
                <select name="product_id[]" required onchange="fillPrice(this)">
                    <option value="">-- Select Product --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['sale_price']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="quantity[]" value="1" min="1" oninput="updateTotals()">
                <input type="number" name="sale_price[]" value="0" step="0.01" oninput="updateTotals()">
                <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
            </div>
        </div>
        <button type="button" class="btn-add-row" onclick="addRow()">+ Add Item</button>
        <hr class="divider">
        <h3>Customer Info</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" name="customer_name" list="customer_list" placeholder="Walk-in Customer">
                <datalist id="customer_list">
                    <?php foreach($customers as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['name']); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="customer_phone" placeholder="03001234567">
            </div>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <input type="text" name="notes" placeholder="Optional notes">
        </div>
    </div>
    <div class="pos-right">
        <h3>Quotation Details</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Quote Date</label>
                <input type="date" name="quote_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Valid Until</label>
                <input type="date" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>
        </div>
        <div class="totals-box">
            <div class="total-row"><span>Subtotal</span><span id="subtotal-display">PKR 0.00</span></div>
            <div class="total-row">
                <span>Discount (PKR)</span>
                <input type="number" name="discount" id="discount" value="0" step="0.01" oninput="updateTotals()" style="width:120px; text-align:right;">
            </div>
            <div class="total-row grand"><span>Grand Total</span><span id="grand-display">PKR 0.00</span></div>
            <button type="submit" class="btn-save">💾 Save Quotation</button>
        </div>
    </div>
</div>
</form>
<script>
const productOptions = `<?php foreach($products as $p): ?><option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['sale_price']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?>`;

function fillPrice(select) {
    const row = select.closest('.item-row');
    const price = select.options[select.selectedIndex].dataset.price || 0;
    row.querySelector('input[name="sale_price[]"]').value = price;
    updateTotals();
}
function updateTotals() {
    const rows = document.querySelectorAll('#items-container .item-row');
    let subtotal = 0;
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="sale_price[]"]').value) || 0;
        subtotal += qty * price;
    });
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    document.getElementById('subtotal-display').textContent = 'PKR ' + subtotal.toFixed(2);
    document.getElementById('grand-display').textContent = 'PKR ' + (subtotal - discount).toFixed(2);
}
function addRow() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `<select name="product_id[]" required onchange="fillPrice(this)"><option value="">-- Select Product --</option>${productOptions}</select><input type="number" name="quantity[]" value="1" min="1" oninput="updateTotals()"><input type="number" name="sale_price[]" value="0" step="0.01" oninput="updateTotals()"><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>`;
    container.appendChild(div);
}
function removeRow(btn) {
    if(document.querySelectorAll('#items-container .item-row').length === 1) return;
    btn.closest('.item-row').remove();
    updateTotals();
}
</script>
</body>
</html>