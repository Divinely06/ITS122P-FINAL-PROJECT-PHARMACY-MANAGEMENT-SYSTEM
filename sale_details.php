<?php
// sale_details.php - para makita ang detalye ng isang benta

session_start();

// i-redirect sa login kung hindi naka-login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// kunin yung id ng sale na gusto tingnan
$sale_id = $_GET['id'];

// check kung may id
if (empty($sale_id)) {
    header('Location: reports.php');
    exit();
}

// kunin yung info ng sale kasama ang pangalan ng cashier
$sale_info = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT sales.*, users.full_name 
     FROM sales 
     JOIN users ON sales.user_id = users.user_id 
     WHERE sale_id='$sale_id'"));

// kung wala yung sale, balik sa reports
if (!$sale_info) {
    header('Location: reports.php');
    exit();
}

// kunin lahat ng items na nabenta sa transaction na ito
$sold_items = mysqli_query($conn, 
    "SELECT sale_items.*, medicines.name 
     FROM sale_items 
     JOIN medicines ON sale_items.medicine_id = medicines.medicine_id 
     WHERE sale_id='$sale_id'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Details - HopeMed Pharmacy</title>
    <style>
        /* Global & Theme Styles */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --bg-main: #F2F6FA;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --border: rgba(12,123,123,0.15);
            --font-main: system-ui, -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-main); }

        body { display: flex; height: 100vh; background-color: var(--bg-main); overflow: hidden; }

        /* Main Content Layout */
        .main-container { flex: 1; padding: 2rem; overflow-y: auto; display: flex; flex-direction: column; align-items: center; }

        /* Header */
        .page-header { width: 100%; max-width: 700px; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; }
        .header-title h2 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 4px; }
        .header-title p { font-size: 0.875rem; color: var(--text-muted); }

        .btn-back { color: var(--text-muted); text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: color 0.2s; }
        .btn-back:hover { color: var(--primary); }

        /* Receipt Card */
        .receipt-card { background: white; width: 100%; max-width: 700px; border-radius: 16px; padding: 2.5rem; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

        .receipt-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed var(--border); padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
        .brand-name { font-size: 1.25rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 8px; }
        .brand-icon { width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        
        /* Meta Info Grid */
        .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .meta-box label { display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .meta-box p { font-size: 1rem; font-weight: 600; color: var(--text-main); }
        .tx-id { font-family: monospace; color: var(--primary); }

        /* Itemized Table */
        table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 2rem; }
        th { padding: 12px 16px; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border); background: #F8F9FA; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 14px 16px; font-size: 0.875rem; color: var(--text-main); border-bottom: 1px solid rgba(12,123,123,0.06); }
        .num-col { text-align: right; }

        /* Totals Area */
        .totals-container { width: 300px; margin-left: auto; border-top: 2px solid var(--border); padding-top: 1rem; }
        .total-row { display: flex; justify-content: space-between; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 8px; }
        .total-row .val { font-weight: 600; color: var(--text-main); }
        .total-row.grand-total { font-size: 1.25rem; font-weight: 700; color: var(--primary); border-top: 1px dashed var(--border); padding-top: 12px; margin-top: 8px; }
        .total-row.grand-total .val { color: var(--primary); }

        /* Actions */
        .card-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; }
        .btn { padding: 10px 20px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; border: none; }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #F8F9FA; }
        .btn-print { background: var(--primary); color: white; }
        .btn-print:hover { background: #0a6363; }

        /* Print Styles */
        @media print {
            body { background: white; }
            .sidebar, .page-header, .card-actions { display: none !important; }
            .main-container { padding: 0; }
            .receipt-card { border: none; box-shadow: none; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-container">
        
        <div class="page-header">
            <div class="header-title">
                <h2>Transaction Details</h2>
            </div>
            <a href="reports.php" class="btn-back">← Back to Reports</a>
        </div>

        <div class="receipt-card">
            
            <div class="receipt-header">
                <div class="brand-name">
                    <div class="brand-icon">HM</div>
                    HopeMed Pharmacy
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; padding: 4px 10px; background: #F0FBF7; color: var(--primary-light); border-radius: 20px;">
                        Completed
                    </span>
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-box">
                    <label>Transaction ID</label>
                    <p class="tx-id">TXN-<?php echo str_pad($sale_info['sale_id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="meta-box">
                    <label>Date & Time</label>
                    <p><?php echo date('M d, Y · h:i A', strtotime($sale_info['sale_date'])); ?></p>
                </div>
                <div class="meta-box">
                    <label>Assisted By (Cashier)</label>
                    <p><?php echo htmlspecialchars($sale_info['full_name']); ?></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="num-col">Qty</th>
                        <th class="num-col">Unit Price</th>
                        <th class="num-col">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($sold_items)): ?>
                    <tr>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="num-col"><?php echo $item['quantity']; ?></td>
                        <td class="num-col" style="color: var(--text-muted);">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="num-col" style="font-weight: 600;">₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="totals-container">
                <div class="total-row grand-total">
                    <span>Total Cost</span>
                    <span class="val">₱<?php echo number_format($sale_info['total_amount'], 2); ?></span>
                </div>
                <div class="total-row" style="margin-top: 12px;">
                    <span>Amount Tendered</span>
                    <span class="val">₱<?php echo number_format($sale_info['amount_paid'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Change</span>
                    <span class="val">₱<?php echo number_format($sale_info['change_amount'], 2); ?></span>
                </div>
            </div>

            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-outline">🖨️ Print Receipt</button>
                <a href="reports.php" class="btn btn-print">Done</a>
            </div>

        </div>

    </main>

</body>
</html>