<?php
// dashboard.php - Main command center

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// 1. Calculate Total Inventory Items
$inv_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM medicines");
$inv_data = mysqli_fetch_assoc($inv_query);
$total_meds = $inv_data['total'];

// 2. Calculate Low Stock Items 
$low_stock_query = mysqli_query($conn, "SELECT COUNT(*) as low FROM medicines WHERE stock_quantity <= 10");
$low_stock_data = mysqli_fetch_assoc($low_stock_query);
$low_stock = $low_stock_data['low'];

// Fetch actual low stock items for the alert panel
$low_items_query = mysqli_query($conn, "SELECT name, stock_quantity, unit FROM medicines WHERE stock_quantity <= 10 LIMIT 4");

// 3. Calculate Today's Sales Revenue
$sales_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM sales WHERE DATE(sale_date) = CURDATE()");
$sales_data = mysqli_fetch_assoc($sales_query);
$today_revenue = $sales_data['revenue'] ? $sales_data['revenue'] : 0;

// 4. Calculate Medicines Expiring in the Next 30 Days
$expiring_query = mysqli_query($conn, "SELECT COUNT(*) as expiring FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$expiring_data = mysqli_fetch_assoc($expiring_query);
$expiring_soon = $expiring_data['expiring'];

// 5. Fetch 5 Most Recent Transactions
$recent_sales = mysqli_query($conn, "SELECT sales.sale_id, sales.total_amount, sales.sale_date, users.full_name 
                                     FROM sales 
                                     JOIN users ON sales.user_id = users.user_id 
                                     ORDER BY sales.sale_date DESC LIMIT 5");

// 6. Fetch Top 5 Products by Quantity Sold
$top_products_query = mysqli_query($conn, "
    SELECT m.name, SUM(si.quantity) as total_sold 
    FROM sale_items si 
    JOIN medicines m ON si.medicine_id = m.medicine_id 
    GROUP BY si.medicine_id 
    ORDER BY total_sold DESC 
    LIMIT 5
");

$product_labels = [];
$product_data = [];

if ($top_products_query) {
    while ($row = mysqli_fetch_assoc($top_products_query)) {
        $product_labels[] = $row['name'];
        $product_data[] = $row['total_sold'];
    }
}

// 7. Fetch Weekly Revenue (Last 7 Days)
$weekly_labels_map = [];
$weekly_data_map = [];

// Initialize the last 7 days with 0 revenue (prevents gaps in the chart)
for ($i = 6; $i >= 0; $i--) {
    $date_val = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days")); // Gets 'Mon', 'Tue', etc.
    $weekly_labels_map[$date_val] = $day_name;
    $weekly_data_map[$date_val] = 0;
}

$start_date = date('Y-m-d', strtotime('-6 days'));
$weekly_query = mysqli_query($conn, "
    SELECT DATE(sale_date) as sale_day, SUM(total_amount) as daily_revenue 
    FROM sales 
    WHERE DATE(sale_date) >= '$start_date' 
    GROUP BY DATE(sale_date)
");

if ($weekly_query) {
    while ($row = mysqli_fetch_assoc($weekly_query)) {
        $day = $row['sale_day'];
        if (isset($weekly_data_map[$day])) {
            $weekly_data_map[$day] = $row['daily_revenue'];
        }
    }
}

// Convert associative arrays to indexed arrays for JavaScript
$chart_labels = array_values($weekly_labels_map);
$chart_data = array_values($weekly_data_map);

$current_date = date('l, F j, Y');
$current_time = date('g:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HopeMed Pharmacy</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Global Variables & Resets */
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

        /* Dashboard Content Styling */
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
        .time-badge { background: white; border: 1px solid var(--card-border); padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; color: var(--text-muted); }

        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; }

        .card { background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid var(--card-border); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; }
        .card-title { font-size: 1rem; font-weight: 600; color: var(--text-main); }
        .card-subtitle { font-size: 0.75rem; color: var(--text-muted); }

        .kpi-value { font-size: 1.75rem; font-weight: 600; color: var(--text-main); margin-top: 1rem; }
        .kpi-label { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }
        .kpi-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 12px 16px; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid rgba(12,123,123,0.06); }
        td { padding: 12px 16px; font-size: 0.75rem; color: var(--text-main); border-bottom: 1px solid rgba(12,123,123,0.04); }
        .tx-id { color: var(--primary); font-family: monospace; }
        
        .alert-item { background: #FFF8F0; border: 1px solid rgba(245, 158, 43, 0.2); padding: 12px; border-radius: 12px; margin-bottom: 12px; }
        .alert-header { display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 500; }
        .alert-stock { color: var(--danger); }
        .progress-bar { height: 6px; background: #FFE4B5; border-radius: 4px; margin-top: 8px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--warning); }
        
        .chart-container { position: relative; height: 200px; width: 100%; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        
        <div class="dash-header">
            <div>
                <h1>
                    <?php 
                        // Properly check if the user is an admin or counter staff
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                            echo 'Admin Dashboard';
                        } else {
                            echo 'Counter Staff Dashboard';
                        }
                    ?>
                </h1>
                <p><?php echo $current_date; ?> · HopeMed Pharmacy</p>
            </div>
            <div class="time-badge">🕒 Last updated: <?php echo $current_time; ?></div>
        </div>

        <div class="grid-4">
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(12,123,123,0.15); color: var(--primary);">📈</div></div>
                <div class="kpi-value">₱<?php echo number_format($today_revenue, 2); ?></div>
                <div class="kpi-label">Today's Revenue</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(23,168,128,0.15); color: var(--primary-light);">📦</div></div>
                <div class="kpi-value"><?php echo number_format($total_meds); ?></div>
                <div class="kpi-label">Total Medicines Tracked</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(245,158,43,0.15); color: var(--warning);">⚠️</div></div>
                <div class="kpi-value"><?php echo $low_stock; ?></div>
                <div class="kpi-label">Low Stock Alerts</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="kpi-icon" style="background: rgba(214,62,62,0.15); color: var(--danger);">🕒</div></div>
                <div class="kpi-value"><?php echo $expiring_soon; ?></div>
                <div class="kpi-label">Expiring < 30 Days</div>
            </div>
        </div>

        <div class="grid-3">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Weekly Revenue</h2>
                        <p class="card-subtitle">Sales performance for the last 7 days</p>
                    </div>
                </div>
                <div class="chart-container"><canvas id="revenueChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Top Products</h2>
                        <p class="card-subtitle">By units sold all time</p>
                    </div>
                </div>
                <div class="chart-container"><canvas id="productsChart"></canvas></div>
            </div>
        </div>

        <div class="grid-3">
            <div class="card" style="padding: 0;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--card-border);">
                    <h2 class="card-title">Recent Transactions</h2>
                    <p class="card-subtitle">Latest sales processed</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Date & Time</th>
                            <th>Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_sales) > 0): ?>
                            <?php while ($sale = mysqli_fetch_assoc($recent_sales)): ?>
                            <tr>
                                <td class="tx-id">TXN-<?php echo $sale['sale_id']; ?></td>
                                <td>Walk-in</td>
                                <td style="font-weight: 600;">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo date('h:i A', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['full_name']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No recent transactions.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="card-title">Low Stock Alerts</h2>
                <p class="card-subtitle" style="margin-bottom: 1rem;">Items below threshold</p>
                
                <?php if (mysqli_num_rows($low_items_query) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($low_items_query)): 
                        $percent = ($item['stock_quantity'] / 10) * 100;
                    ?>
                    <div class="alert-item">
                        <div class="alert-header">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="alert-stock">Stock: <?php echo $item['stock_quantity']; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center;">Inventory levels are healthy.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        // ==========================================
        // DYNAMIC WEEKLY REVENUE CHART
        // ==========================================
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        let gradient = ctxRev.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(12, 123, 123, 0.2)');
        gradient.addColorStop(1, 'rgba(12, 123, 123, 0)');

        new Chart(ctxRev, {
            type: 'line',
            data: {
                // Dynamically injected labels (Last 7 Days)
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    // Dynamically injected revenue data
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#0C7B7B',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4, // Added point radius so hover works better
                    pointBackgroundColor: '#0C7B7B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6B7E91', font: { size: 11 } } },
                    y: { 
                        grid: { borderDash: [3, 3], color: 'rgba(0,0,0,0.05)' }, 
                        ticks: { color: '#6B7E91', font: { size: 11 }, callback: function(value) { return '₱' + (value/1000) + 'k'; } },
                        beginAtZero: true
                    }
                }
            }
        });

        // ==========================================
        // DYNAMIC TOP PRODUCTS CHART
        // ==========================================
        const ctxProd = document.getElementById('productsChart').getContext('2d');
        new Chart(ctxProd, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($product_labels); ?>,
                datasets: [{
                    label: 'Units Sold',
                    data: <?php echo json_encode($product_data); ?>,
                    backgroundColor: '#17A880',
                    borderRadius: 4,
                    barThickness: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false, beginAtZero: true },
                    y: { grid: { display: false }, ticks: { color: '#6B7E91', font: { size: 10 } }, border: { display: false } }
                }
            }
        });
    </script>
</body>
</html>