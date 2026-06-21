<?php
// sidebar.php - Modern Modular Navigation Menu
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'cashier';
$display_role = ($role == 'admin') ? 'Admin / Manager' : 'Counter Staff';
?>

<style>
    /* Sidebar Specific Styling */
    :root {
        --sidebar-bg: #0A3D47;
        --primary: #0C7B7B;
        --danger: #D63E3E;
    }
    
    .sidebar {
        width: 260px;
        background-color: var(--sidebar-bg);
        color: white;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        height: 100vh;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Updated Logo Container and Image Styling */
    .brand-logo-container {
        width: 45px; /* Adjust width if your logo is wider */
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px; /* Optional: smooths edges if your logo has a background */
        overflow: hidden;
    }

    .brand-logo {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Ensures the logo doesn't stretch or distort */
    }

    .brand-text h2 { font-size: 1rem; margin: 0; color: white; }
    .brand-text p { font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 2px 0 0 0; }

    .role-badge-container {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .role-badge {
        display: inline-block;
        background: <?php echo ($role == 'admin') ? 'rgba(23,168,128,0.2)' : 'rgba(59,130,196,0.2)'; ?>;
        color: <?php echo ($role == 'admin') ? '#17A880' : '#3B82C4'; ?>;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .sidebar-nav {
        padding: 1.5rem 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .nav-item {
        text-decoration: none;
        color: rgba(255,255,255,0.6);
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }

    .nav-item:hover { background: rgba(255,255,255,0.05); color: white; }
    
    .nav-item.active {
        background: rgba(23,168,128,0.15);
        color: #17A880;
        border-left: 4px solid #17A880;
    }

    .sidebar-footer {
        padding: 1.5rem 1rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .logout-btn { color: rgba(255,255,255,0.5); }
    .logout-btn:hover { color: var(--danger); background: rgba(214,62,62,0.1); }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="brand-logo-container">
            <img src="logo.jpg" alt="HopeMed Logo" class="brand-logo">
        </div>
        <div class="brand-text">
            <h2>HopeMed</h2>
            <p>Pharmacy System</p>
        </div>
    </div>
    
    <div class="role-badge-container">
        <span class="role-badge"><?php echo $display_role; ?></span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"> Dashboard</a>
        <a href="pos.php" class="nav-item <?php echo ($current_page == 'pos.php') ? 'active' : ''; ?>"> Point of Sale</a>
        <a href="inventory.php" class="nav-item <?php echo ($current_page == 'inventory.php' || strpos($current_page, 'medicine') !== false) ? 'active' : ''; ?>"> Inventory</a>
        
        <?php if ($role == 'admin'): ?>
            <a href="staff.php" class="nav-item <?php echo ($current_page == 'staff.php' || strpos($current_page, 'staff') !== false || $current_page == 'register.php') ? 'active' : ''; ?>"> Staff Management</a>
            <a href="reports.php" class="nav-item <?php echo (strpos($current_page, 'report') !== false || strpos($current_page, 'sale_details') !== false) ? 'active' : ''; ?>"> Reports</a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item logout-btn">🚪 Sign Out</a>
    </div>
</aside>