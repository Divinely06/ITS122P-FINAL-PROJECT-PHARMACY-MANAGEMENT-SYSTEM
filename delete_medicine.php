<?php
// delete_medicine.php - Modern Delete Logic

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// Ensure only admins can perform deletions
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Get the medicine ID
$med_id = $_GET['id'] ?? '';

// Check if ID exists
if (empty($med_id)) {
    header('Location: inventory.php');
    exit();
}

// Clean input to prevent SQL injection
$safe_id = mysqli_real_escape_string($conn, $med_id);

// Attempt to delete from database
$sql = "DELETE FROM medicines WHERE medicine_id='$safe_id'";

if (mysqli_query($conn, $sql)) {
    // SUCCESS: Redirect back to inventory with the new deleted signal for the pop-up
    header('Location: inventory.php?deleted=1');
    exit();
} else {
    // Check for Foreign Key Constraint Error (Error 1451)
    $error_code = mysqli_errno($conn);
    $error_text = mysqli_error($conn);
    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cannot Delete - HopeMed Pharmacy</title>
        <style>
            /* Modern Error Page Styles */
            :root {
                --primary: #0C7B7B;
                --danger: #D63E3E;
                --bg-main: #F2F6FA;
                --text-main: #0E1C2B;
                --text-muted: #6B7E91;
                --border: rgba(12,123,123,0.15);
                --font-main: system-ui, -apple-system, sans-serif;
            }

            * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-main); }

            body { 
                display: flex; 
                height: 100vh; 
                background-color: var(--bg-main); 
                align-items: center; 
                justify-content: center; 
                padding: 20px;
            }

            .error-card { 
                background: white; 
                width: 100%; 
                max-width: 450px; 
                border-radius: 16px; 
                padding: 2.5rem; 
                text-align: center; 
                border: 1px solid var(--border); 
                box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
            }

            .icon-circle { 
                width: 64px; 
                height: 64px; 
                background: rgba(214,62,62,0.1); 
                color: var(--danger); 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                font-size: 1.8rem; 
                margin: 0 auto 1.5rem auto; 
            }

            h2 { color: var(--text-main); font-size: 1.25rem; margin-bottom: 0.75rem; }
            p { color: var(--text-muted); font-size: 0.875rem; line-height: 1.6; margin-bottom: 2rem; }
            
            .btn { 
                display: inline-block; 
                padding: 12px 24px; 
                background: var(--primary); 
                color: white; 
                border-radius: 10px; 
                font-weight: 600; 
                text-decoration: none; 
                font-size: 0.875rem; 
                transition: background 0.2s; 
                width: 100%;
            }
            .btn:hover { background: #0a6363; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="icon-circle">⚠️</div>
            
            <?php if ($error_code == 1451): ?>
                <h2>Action Denied</h2>
                <p>This medicine cannot be deleted because it is already linked to existing <strong>Sales Transactions</strong>.<br><br>Deleting it would break past receipts and revenue analytics.</p>
            <?php else: ?>
                <h2>System Error</h2>
                <p>An unexpected database error occurred while trying to delete this record:<br><br><i><?php echo htmlspecialchars($error_text); ?></i></p>
            <?php endif; ?>
            
            <a href="inventory.php" class="btn">Return to Inventory</a>
        </div>
    </body>
    </html>
    
    <?php
    exit();
}
?>