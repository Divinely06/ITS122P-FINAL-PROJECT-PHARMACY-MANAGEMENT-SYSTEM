<?php
// reports.php - Modern Sales Reports

session_start();

// i-redirect sa login kung hindi naka-login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// admin lang ang pwedeng makakita ng reports
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// kunin lahat ng sales kasama ang pangalan ng cashier
$sales_list = mysqli_query($conn, "SELECT sales.*, users.full_name 
                                   FROM sales 
                                   JOIN users ON sales.user_id = users.user_id 
                                   ORDER BY sale_date DESC");

// i-count kung ilan lahat ng sales
$total_sales = mysqli_num_rows($sales_list);

// kalkulahin ang total sales
$revenue_query = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) AS total_revenue FROM sales"));
$total_revenue = $revenue_query['total_revenue'] ?? 0;

// kalkulahin ang Average Basket Size
$avg_basket = $total_sales > 0 ? ($total_revenue / $total_sales) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - HopeMed Pharmacy</title>
    <style>
        /* Global Variables & Resets to match Dashboard */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --bg-main: #F2F6FA;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --card-border: rgba(12,123,123,0.08);
            --danger: #D63E3E;
            --warning: #F59E2B;
            --font-main: system-ui, -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-main); }

        body {
            display: flex;
            height: 100vh;
            background-color: var(--bg-main);
            overflow: hidden;
        }

        /* Main Content Styling */
        .dashboard-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .dash-header { display: flex; justify-content: space-between; align-items: center; }
        .dash-header h1 { font-size: 1.5rem; color: var(--text-main); }
        .dash-header p { font-size: 0.875rem; color: var(--text-muted); margin-top: 4px; }
        
        .btn-export {
            background-color: var(--primary-light);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 2px 4px rgba(23,168,128,0.2);
        }
        .btn-export:hover { background-color: #14936f; transform: translateY(-1px); }

        /* Grid & Cards */
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }

        .card { background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid var(--card-border); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; }
        .card-title { font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        .card-subtitle { font-size: 0.875rem; color: var(--text-muted); margin-top: 4px; }

        .kpi-value { font-size: 1.75rem; font-weight: 600; color: var(--text-main); margin-top: 1rem; }
        .kpi-label { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px;}
        .kpi-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* Modern Table Styling */
        .table-container { padding: 0; overflow: hidden; }
        .table-padding { padding: 1.5rem; border-bottom: 1px solid var(--card-border); }
        
        /* Table Scroll Implementation */
        .table-scroll {
            max-height: 55vh; /* Sets maximum height before scrolling starts */
            overflow-y: auto;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        
        /* Sticky Headers */
        th { 
            padding: 16px; 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            border-bottom: 1px solid rgba(12,123,123,0.06); 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            background: #F8F9FA; /* Solid background so text doesn't overlap */
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        td { padding: 16px; font-size: 0.875rem; color: var(--text-main); border-bottom: 1px solid rgba(12,123,123,0.04); }
        tr:hover td { background-color: rgba(12,123,123,0.02); }
        
        /* Custom Scrollbar for the table */
        .table-scroll::-webkit-scrollbar { width: 8px; }
        .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .table-scroll::-webkit-scrollbar-thumb { background: rgba(12, 123, 123, 0.2); border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb:hover { background: rgba(12, 123, 123, 0.5); }

        .tx-id { color: var(--primary); font-family: monospace; font-weight: 600; font-size: 0.9rem;}
        .view-btn { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.875rem; background: rgba(12,123,123,0.1); padding: 6px 12px; border-radius: 6px; transition: background 0.2s;}
        .view-btn:hover { background: rgba(12,123,123,0.2); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        
        <div class="dash-header">
            <div>
                <h1>Sales Reports</h1>
                <p>Complete transaction history and revenue analytics</p>
            </div>
            <div>
                <button onclick="window.location.href='export_sales_xml.php'" class="btn-export">
                    📥 Export Sales to XML
                </button>
            </div>
        </div>

        <div class="grid-3">
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(12,123,123,0.15); color: var(--primary);">📈</div></div>
                <div class="kpi-value">₱<?php echo number_format($total_revenue, 2); ?></div>
                <div class="kpi-label">Total Revenue</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(23,168,128,0.15); color: var(--primary-light);">💳</div></div>
                <div class="kpi-value"><?php echo number_format($total_sales); ?></div>
                <div class="kpi-label">Total Transactions</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(245,158,43,0.15); color: var(--warning);">🛒</div></div>
                <div class="kpi-value">₱<?php echo number_format($avg_basket, 2); ?></div>
                <div class="kpi-label">Avg. Basket Size</div>
            </div>
        </div>

        <div class="card table-container">
            <div class="table-padding">
                <h2 class="card-title">Transaction History</h2>
                <p class="card-subtitle">All recorded sales sorted by most recent</p>
            </div>
            
            <?php if ($total_sales == 0): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                    Wala pang sales record.
                </div>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Cashier</th>
                                <th>Total Amount</th>
                                <th>Amount Paid</th>
                                <th>Change</th>
                                <th>Date & Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sale = mysqli_fetch_assoc($sales_list)): ?>
                            <tr>
                                <td class="tx-id">TXN-<?php echo $sale['sale_id']; ?></td>
                                <td><?php echo htmlspecialchars($sale['full_name']); ?></td>
                                <td style="font-weight: 600;">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td>₱<?php echo number_format($sale['amount_paid'], 2); ?></td>
                                <td>₱<?php echo number_format($sale['change_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                <td><a href="sale_details.php?id=<?php echo $sale['sale_id']; ?>" class="view-btn">View Details</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>