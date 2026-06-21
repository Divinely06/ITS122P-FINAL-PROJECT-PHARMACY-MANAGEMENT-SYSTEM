<?php
// staff.php - Modern Staff Management

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// Only admin can access the staff list
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Fetch all staff from the database
$staff_list = mysqli_query($conn, "SELECT * FROM users ORDER BY full_name ASC");

// Count total staff members
$total_staff = mysqli_num_rows($staff_list);

// Check if a success or delete flag was passed in the URL
$show_added = isset($_GET['added']) && $_GET['added'] == '1';
$show_deleted = isset($_GET['deleted']) && $_GET['deleted'] == '1';
$show_updated = isset($_GET['updated']) && $_GET['updated'] == '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - HopeMed Pharmacy</title>
    <style>
        /* Global & Theme Styles */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --secondary: #3B82C4;
            --bg-main: #F2F6FA;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --border: rgba(12,123,123,0.12);
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

        /* Modern Toast Notification (Pop-up) */
        .toast-popup {
            position: fixed;
            top: 20px;
            right: 20px;
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
        .staff-container {
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

        /* Toolbar (Search) */
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

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 16px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border); background: rgba(242, 246, 250, 0.4); text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 16px; font-size: 0.875rem; color: var(--text-main); border-bottom: 1px solid rgba(12,123,123,0.04); vertical-align: middle; }
        tr:hover td { background-color: rgba(242, 246, 250, 0.5); }

        /* Staff User Profile Cell */
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem; flex-shrink: 0; }
        .user-info { display: flex; flex-direction: column; gap: 2px; }
        .user-name { font-weight: 600; color: var(--text-main); }
        .user-username { font-size: 0.75rem; color: var(--text-muted); }

        /* Role Badges */
        .role-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .role-admin { background: rgba(12,123,123,0.1); color: var(--primary); }
        .role-pharmacist { background: rgba(59,130,196,0.1); color: var(--secondary); }
        .role-cashier { background: rgba(245,158,43,0.1); color: var(--warning); }

        /* Actions */
        .action-links a { font-size: 0.875rem; font-weight: 500; text-decoration: none; padding: 6px 10px; border-radius: 6px; transition: background 0.2s; }
        .edit-link { color: var(--primary); }
        .edit-link:hover { background: rgba(12,123,123,0.1); }
        .delete-link { color: var(--danger); }
        .delete-link:hover { background: rgba(214,62,62,0.1); }
        .view-only { color: var(--text-muted); font-size: 0.875rem; font-weight: 500; padding: 6px 10px; background: #F2F6FA; border-radius: 6px; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php if ($show_added || $show_deleted || $show_updated): 
        // Set dynamic content based on the action using SOLID HEX COLORS
        if ($show_added) {
            $toast_bg = '#17A880'; // Solid Green
            $toast_icon = '✅';
            $toast_msg = 'Successfully added to the system!';
        } elseif ($show_deleted) {
            $toast_bg = '#D63E3E'; // Solid Red
            $toast_icon = '🗑️';
            $toast_msg = 'Successfully removed from the system.';
        } elseif ($show_updated) {
            $toast_bg = '#3B82C4'; // Solid Blue
            $toast_icon = '✏️';
            $toast_msg = 'Details successfully updated!';
        }
    ?>
    <div id="actionToast" class="toast-popup" style="background-color: <?php echo $toast_bg; ?>; color: #FFFFFF;">
        <span style="font-size: 1.2rem;"><?php echo $toast_icon; ?></span>
        <?php echo $toast_msg; ?>
    </div>
    <script>
        setTimeout(function() { document.getElementById('actionToast').classList.add('show'); }, 100);
        setTimeout(function() { document.getElementById('actionToast').classList.remove('show'); }, 3500);

        // This clears the URL parameters based on which page you are currently on
        const currentPath = window.location.pathname.split('/').pop();
        window.history.replaceState({}, document.title, currentPath);
    </script>
    <?php endif; ?>

    <main class="staff-container">
        
        <div class="page-header">
            <div class="header-title">
                <h1>Staff Management</h1>
                <p>Manage accounts for <?php echo $total_staff; ?> system users</p>
            </div>
            
            <a href="add_staff.php" class="btn btn-primary">
                + Register New Staff
            </a>
        </div>

        <div class="toolbar">
            <div class="search-wrapper">
                <span>🔍</span>
                <input type="text" id="staffSearch" placeholder="Search by name, username, or role..." onkeyup="filterStaff()">
            </div>
        </div>

        <div class="table-card">
            <?php if ($total_staff == 0): ?>
                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                    <p>No staff members registered yet.</p>
                </div>
            <?php else: ?>
                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Email Address</th>
                            <th>System ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($staff = mysqli_fetch_assoc($staff_list)): 
                            
                            // Get initials for the avatar
                            $name_parts = explode(' ', trim($staff['full_name']));
                            $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
                            
                            // Determine role badge class
                            $role_class = 'role-cashier'; 
                            if ($staff['role'] == 'admin') $role_class = 'role-admin';
                            if ($staff['role'] == 'pharmacist') $role_class = 'role-pharmacist';
                            
                            // Change avatar color slightly based on role
                            $avatar_bg = 'var(--primary-light)';
                            if ($staff['role'] == 'admin') $avatar_bg = 'var(--primary)';
                            if ($staff['role'] == 'pharmacist') $avatar_bg = 'var(--secondary)';
                        ?>
                        <tr class="staff-row">
                            <td>
                                <div class="user-profile">
                                    <div class="avatar" style="background: <?php echo $avatar_bg; ?>;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name"><?php echo htmlspecialchars($staff['full_name']); ?></span>
                                        <span class="user-username">@<?php echo htmlspecialchars($staff['username']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $role_class; ?>">
                                    <?php echo htmlspecialchars($staff['role']); ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($staff['email']); ?></td>
                            <td style="color: var(--text-muted); font-family: monospace;">USR-<?php echo str_pad($staff['user_id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td class="action-links">
                                <a href="edit_staff.php?id=<?php echo $staff['user_id']; ?>" class="edit-link">Edit</a>
                                
                                <?php if ($staff['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="delete_staff.php?id=<?php echo $staff['user_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($staff['full_name']); ?> from the system?')">Delete</a>
                                <?php else: ?>
                                    <span class="view-only">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>

    <script>
        function filterStaff() {
            const input = document.getElementById("staffSearch");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("staffTable");
            
            if (!table) return;
            
            const rows = table.getElementsByClassName("staff-row");

            for (let i = 0; i < rows.length; i++) {
                const profileCell = rows[i].getElementsByTagName("td")[0];
                const roleCell = rows[i].getElementsByTagName("td")[1];
                
                if (profileCell || roleCell) {
                    const profileText = profileCell.textContent || profileCell.innerText;
                    const roleText = roleCell.textContent || roleCell.innerText;
                    
                    if (profileText.toLowerCase().indexOf(filter) > -1 || roleText.toLowerCase().indexOf(filter) > -1) {
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