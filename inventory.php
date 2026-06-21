<?php
// inventory.php - Modern Inventory Management

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// Check user role
$is_admin = ($_SESSION['role'] == 'admin');

// Fetch all medicines from the database
$med_list = mysqli_query($conn, "SELECT * FROM medicines ORDER BY name ASC");

// Count total medicines
$total_meds = mysqli_num_rows($med_list);

// Check if a success or delete flag was passed in the URL
$show_added = isset($_GET['added']) && $_GET['added'] == '1';
$show_deleted = isset($_GET['deleted']) && $_GET['deleted'] == '1';
$show_updated = isset($_GET['updated']) && $_GET['updated'] == '1'; // Added an update flag just in case you need it!
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - HopeMed Pharmacy</title>
    <style>
        /* Global & Theme Styles */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --bg-main: #F2F6FA;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --border: rgba(12,123,123,0.12);
            --danger: #D63E3E;
            --warning: #F59E2B;
            --success: #17A880;
            --font-main: system-ui, -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-main); }

        body {
            display: flex;
            height: 100vh;
            background-color: var(--bg-main);
            overflow: hidden;
        }

        /* Modern Toast Notification (Pop-up) */
        .toast-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast-popup.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Main Content Layout */
        .inventory-container {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .header-title h1 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 4px; }
        .header-title p { font-size: 0.875rem; color: var(--text-muted); }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        /* Buttons */
        .btn {
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #0a6363; }

        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: var(--bg-main); }

        /* Toolbar (Search & Filters) */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .search-wrapper {
            position: relative;
            width: 300px;
        }

        .search-wrapper input {
            width: 100%;
            padding: 10px 16px 10px 36px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--bg-main);
            font-size: 0.875rem;
            outline: none;
        }

        .search-wrapper input:focus { border-color: var(--primary); }
        .search-wrapper span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }

        /* Data Table */
        .table-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        /* Table Scroll Implementation */
        .table-scroll {
            max-height: 55vh;
            overflow-y: auto;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        
        /* Sticky Headers */
        th { 
            padding: 16px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            color: var(--text-muted); 
            border-bottom: 1px solid var(--border); 
            background: #F8F9FA; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        td { padding: 16px; font-size: 0.875rem; color: var(--text-main); border-bottom: 1px solid rgba(12,123,123,0.04); vertical-align: middle; }
        tr:hover td { background-color: rgba(242, 246, 250, 0.5); }

        /* Custom Scrollbar */
        .table-scroll::-webkit-scrollbar { width: 8px; }
        .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .table-scroll::-webkit-scrollbar-thumb { background: rgba(12, 123, 123, 0.2); border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb:hover { background: rgba(12, 123, 123, 0.5); }

        /* Table Components */
        .med-info { display: flex; flex-direction: column; gap: 4px; }
        .med-name { font-weight: 600; color: var(--text-main); }
        .med-category { font-size: 0.75rem; color: var(--text-muted); }

        .price-tag { font-weight: 600; }

        /* Status Badges */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-good { background: #F0FBF7; color: var(--success); }
        .status-low { background: #FFF8F0; color: var(--warning); }
        .status-critical { background: #FFF0F0; color: var(--danger); }

        /* Actions */
        .action-links a { font-size: 0.875rem; font-weight: 500; text-decoration: none; padding: 6px 10px; border-radius: 6px; transition: background 0.2s; }
        .edit-link { color: var(--primary); }
        .edit-link:hover { background: rgba(12,123,123,0.1); }
        .delete-link { color: var(--danger); }
        .delete-link:hover { background: rgba(214,62,62,0.1); }
        .view-only { color: var(--text-muted); font-size: 0.875rem; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php if ($show_added || $show_deleted || $show_updated): 
        // Set dynamic content based on the action
        if ($show_added) {
            $toast_bg = 'var(--success)';
            $toast_icon = '✅';
            $toast_msg = 'Medicine successfully added to inventory!';
        } elseif ($show_deleted) {
            $toast_bg = 'var(--danger)';
            $toast_icon = '🗑️';
            $toast_msg = 'Medicine permanently removed from inventory.';
        } elseif ($show_updated) {
            $toast_bg = 'var(--primary-light)';
            $toast_icon = '✏️';
            $toast_msg = 'Medicine details successfully updated!';
        }
    ?>
    <div id="actionToast" class="toast-popup" style="background-color: <?php echo $toast_bg; ?>;">
        <span style="font-size: 1.2rem;"><?php echo $toast_icon; ?></span>
        <?php echo $toast_msg; ?>
    </div>
    <script>
        setTimeout(function() { document.getElementById('actionToast').classList.add('show'); }, 100);
        setTimeout(function() { document.getElementById('actionToast').classList.remove('show'); }, 3500);
        window.history.replaceState({}, document.title, "inventory.php");
    </script>
    <?php endif; ?>

    <main class="inventory-container">
        
        <div class="page-header">
            <div class="header-title">
                <h1>Inventory Management</h1>
                <p>Track and manage <?php echo $total_meds; ?> medicines in stock</p>
            </div>
            
            <?php if ($is_admin): ?>
            <div class="header-actions">
                <a href="export_xml.php" class="btn btn-outline">
                    📥 Export to XML
                </a>
                <a href="add_medicine.php" class="btn btn-primary">
                    + Add New Medicine
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="toolbar">
            <div class="search-wrapper">
                <span>🔍</span>
                <input type="text" id="tableSearch" placeholder="Search medicines by name or ID..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="table-card">
            <?php if ($total_meds == 0): ?>
                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                    <p>No medicines found in the inventory.</p>
                    <?php if ($is_admin): ?>
                        <a href="add_medicine.php" style="color: var(--primary); text-decoration: none; font-weight: 500; margin-top: 10px; display: inline-block;">Add your first medicine</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-scroll">
                    <table id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Medicine Details</th>
                                <th>Stock Status</th>
                                <th>Price</th>
                                <th>Unit</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($med = mysqli_fetch_assoc($med_list)): 
                                
                                // Determine stock status logic
                                $stock = $med['stock_quantity'];
                                $threshold = isset($med['reorder_level']) ? $med['reorder_level'] : 10;
                                
                                if ($stock <= 0 || $stock <= $threshold) {
                                    $status_class = 'status-critical';
                                    $status_text = $stock . ' (Critical)';
                                } elseif ($stock <= ($threshold * 2)) {
                                    $status_class = 'status-low';
                                    $status_text = $stock . ' (Low)';
                                } else {
                                    $status_class = 'status-good';
                                    $status_text = $stock . ' (Good)';
                                }
                            ?>
                            <tr class="item-row">
                                <td style="color: var(--text-muted); font-family: monospace;">#<?php echo $med['medicine_id']; ?></td>
                                <td>
                                    <div class="med-info">
                                        <span class="med-name"><?php echo htmlspecialchars($med['name']); ?></span>
                                        <span class="med-category"><?php echo htmlspecialchars($med['category']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="price-tag">₱<?php echo number_format($med['price'], 2); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($med['unit']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo $med['expiry_date']; ?></td>
                                <td class="action-links">
                                    <?php if ($is_admin): ?>
                                        <a href="edit_medicine.php?id=<?php echo $med['medicine_id']; ?>" class="edit-link">Edit</a>
                                        <a href="delete_medicine.php?id=<?php echo $med['medicine_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($med['name']); ?>?')">Delete</a>
                                    <?php else: ?>
                                        <span class="view-only">View Only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <script>
        function filterTable() {
            const input = document.getElementById("tableSearch");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("inventoryTable");
            
            if (!table) return;
            
            const rows = table.getElementsByClassName("item-row");

            for (let i = 0; i < rows.length; i++) {
                // Get the ID column (index 0) and the Details column (index 1)
                const idCell = rows[i].getElementsByTagName("td")[0];
                const detailsCell = rows[i].getElementsByTagName("td")[1];
                
                if (idCell || detailsCell) {
                    const idText = idCell.textContent || idCell.innerText;
                    const detailsText = detailsCell.textContent || detailsCell.innerText;
                    
                    if (idText.toLowerCase().indexOf(filter) > -1 || detailsText.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }       
            }
        }
    </script>

</body>
</html>