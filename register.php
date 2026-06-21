<?php
// Add Staff Page - Pharmacy Management System

session_start();

// pag hindi naka-login, i-redirect sa login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // kunin yung inputs galing sa form
    $uname = $_POST['username'];
    $pword = $_POST['password'];
    $fname = $_POST['full_name'];
    $urole = $_POST['role'];
    $umail = $_POST['email'];

    // check kung may blanko
    if (empty($uname) || empty($pword) || empty($fname) || empty($umail)) {
        $error_msg = "All fields are required!";
    } else {
        // i-save sa database
        $sql = "INSERT INTO users (username, password, full_name, role, email) 
                VALUES ('$uname', '$pword', '$fname', '$urole', '$umail')";

        if (mysqli_query($conn, $sql)) {
            // successful, balik sa staff list
            header('Location: staff.php');
            exit();
        } else {
            $error_msg = "Hindi na-save: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff Member - HopeMed Pharmacy</title>
    <style>
        /* Global & Theme Styles */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --danger: #D63E3E;
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
        .page-header { width: 100%; max-width: 600px; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; }
        .header-title h2 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 4px; }
        .header-title p { font-size: 0.875rem; color: var(--text-muted); }

        .btn-back { color: var(--text-muted); text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: color 0.2s; }
        .btn-back:hover { color: var(--primary); }

        /* Form Card */
        .form-card { background: white; width: 100%; max-width: 600px; border-radius: 16px; padding: 2rem; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }

        /* Alert Message */
        .alert-error { background: rgba(214,62,62,0.1); color: var(--danger); padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.5rem; border: 1px solid rgba(214,62,62,0.2); }

        /* Form Layout */
        .form-group { margin-bottom: 1.25rem; }
        .form-group.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-main); font-size: 0.875rem; color: var(--text-main); outline: none; transition: border-color 0.2s; }
        input:focus, select:focus { border-color: var(--primary); background: white; }

        /* Buttons */
        .form-actions { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { flex: 1; padding: 12px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; border: none; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #0a6363; }
        .btn-secondary { background: var(--bg-main); color: var(--text-muted); border: 1px solid var(--border); }
        .btn-secondary:hover { background: #e2e8f0; color: var(--text-main); }
    </style>
</head>
<body>

    <!-- Include Modular Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="main-container">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-title">
                <h2>Add New Staff Member</h2>
                <p>Register a new employee to the system</p>
            </div>
            <a href="staff.php" class="btn-back">← Back to Staff List</a>
        </div>

        <!-- Add Form -->
        <div class="form-card">
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert-error">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="e.g. Juan dela Cruz" required>
                </div>

                <div class="form-group grid">
                    <div>
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g. juan@hopemed.com" required>
                    </div>
                    <div>
                        <label>System Role</label>
                        <select name="role" required>
                            <option value="" disabled selected>-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="pharmacist">Pharmacist</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </div>

                <div class="form-group grid">
                    <div>
                        <label>Username</label>
                        <input type="text" name="username" placeholder="e.g. jdelacruz" required>
                    </div>
                    <div>
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="staff.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Staff Member</button>
                </div>

            </form>
        </div>

    </main>

</body>
</html>