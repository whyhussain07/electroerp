<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit(); }
require_once '../../includes/db.php';

$type = $_GET['type'] ?? 'in';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $party_name = trim($_POST['party_name']);
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $reference_no = trim($_POST['reference_no']);
    $note = trim($_POST['note']);
    $payment_date = $_POST['payment_date'];
    $reference_type = $_POST['reference_type'];
    $reference_id = (int)$_POST['reference_id'];
    $party_type = $_POST['party_type'];
    $party_id = (int)$_POST['party_id'];

    $pdo->prepare("INSERT INTO payments (type, reference_type, reference_id, party_type, party_id, party_name, amount, payment_method, reference_no, note, payment_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$type, $reference_type, $reference_id, $party_type, $party_id, $party_name, $amount, $payment_method, $reference_no, $note, $payment_date, $_SESSION['user_id']]);

    // Update sale/purchase balance
    if($reference_type === 'sale' && $reference_id > 0) {
        $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + ?, balance = balance - ? WHERE id = ?")
            ->execute([$amount, $amount, $reference_id]);
    }
    if($reference_type === 'purchase' && $reference_id > 0) {
        $pdo->prepare("UPDATE purchases SET paid_amount = paid_amount + ?, balance = balance - ? WHERE id = ?")
            ->execute([$amount, $amount, $reference_id]);
        // Update supplier balance
        $purchase = $pdo->prepare("SELECT supplier_id FROM purchases WHERE id = ?");
        $purchase->execute([$reference_id]);
        $purchase = $purchase->fetch(PDO::FETCH_ASSOC);
        if($purchase) {
            $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?")
                ->execute([$amount, $purchase['supplier_id']]);
        }
    }

    header("Location: index.php?type=$type&msg=added"); exit();
}

$customers = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sales = $pdo->query("SELECT * FROM sales WHERE balance > 0 ORDER BY sale_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$purchases = $pdo->query("SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.balance > 0 ORDER BY p.purchase_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Payment | ElectroERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }
        .topbar { background: #1e293b; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .topbar h1 { color: #38bdf8; font-size: 20px; }
        .topbar a { color: #94a3b8; text-decoration: none; font-size: 13px; margin-left: 16px; }
        .content { padding: 30px; max-width: 700px; }
        h2 { font-size: 20px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { font-size: 13px; color: #94a3b8; }
        input, select, textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f1f5f9; font-size: 14px; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        .btn { padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn-primary { background: #38bdf8; color: #0f172a; }
        .btn-secondary { background: #334155; color: #f1f5f9; margin-left: 10px; }
        .type-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-bottom: 20px; }
        .type-in { background: #dcfce7; color: #16a34a; }
        .type-out { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>⚡ ElectroERP</h1>
    <div><a href="index.php?type=<?php echo $type; ?>">← Payments</a><a href="../../dashboard.php">Dashboard</a><a href="../../logout.php">Logout</a></div>
</div>
<div class="content">
    <h2>New Payment <?php echo $type==='in'?'In':'Out'; ?></h2>
    <span class="type-badge type-<?php echo $type; ?>"><?php echo $type==='in'?'💰 Receiving Money':'💸 Paying Money'; ?></span>

    <form method="POST">
        <input type="hidden" name="type" value="<?php echo $type; ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Party Type</label>
                <select name="party_type" id="party_type" onchange="updateParty()">
                    <option value="other">Other</option>
                    <?php if($type==='in'): ?>
                    <option value="customer">Customer</option>
                    <?php else: ?>
                    <option value="supplier">Supplier</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group" id="party_select_group" style="display:none;">
                <label>Select <?php echo $type==='in'?'Customer':'Supplier'; ?></label>
                <select name="party_id" id="party_id" onchange="fillPartyName()">
                    <option value="0">-- Select --</option>
                    <?php if($type==='in'): ?>
                        <?php foreach($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['name']); ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Party Name *</label>
            <input type="text" name="party_name" id="party_name" placeholder="Customer or supplier name" required>
        </div>

        <div class="form-group">
            <label>Against Invoice (Optional)</label>
            <select name="reference_id" id="reference_id" onchange="setRefType()">
                <option value="0" data-type="other">-- General Payment --</option>
                <?php if($type==='in'): ?>
                    <?php foreach($sales as $s): ?>
                        <option value="<?php echo $s['id']; ?>" data-type="sale">SALE: <?php echo htmlspecialchars($s['invoice_no']); ?> — PKR <?php echo number_format($s['balance'],0); ?> due</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach($purchases as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-type="purchase">PO: <?php echo htmlspecialchars($p['invoice_no']); ?> — <?php echo htmlspecialchars($p['supplier_name']); ?> — PKR <?php echo number_format($p['balance'],0); ?> due</option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <input type="hidden" name="reference_type" id="reference_type" value="other">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Amount (PKR) *</label>
                <input type="number" name="amount" step="0.01" placeholder="0" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Reference No</label>
                <input type="text" name="reference_no" placeholder="Cheque no / transaction ID">
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Note</label>
            <input type="text" name="note" placeholder="Optional note">
        </div>

        <button type="submit" class="btn btn-primary">Save Payment</button>
        <a href="index.php?type=<?php echo $type; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<script>
function updateParty() {
    const type = document.getElementById('party_type').value;
    document.getElementById('party_select_group').style.display = type !== 'other' ? 'flex' : 'none';
}
function fillPartyName() {
    const select = document.getElementById('party_id');
    const name = select.options[select.selectedIndex].dataset.name || '';
    document.getElementById('party_name').value = name;
}
function setRefType() {
    const select = document.getElementById('reference_id');
    const type = select.options[select.selectedIndex].dataset.type || 'other';
    document.getElementById('reference_type').value = type;
}
</script>
</body>
</html>