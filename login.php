<?php
// login.php - login page ng pharmacy system

session_start();

include 'config.php';

// kung naka-login na, i-redirect na sa dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // kunin yung sinulat sa form
    $uname = $_POST['username'];
    $pword = $_POST['password'];

    // check kung may blanko
    if (empty($uname) || empty($pword)) {
        $error_msg = "Please fill in all fields.";
    } else {
        // hanapin sa database
        $sql = "SELECT * FROM users WHERE username='$uname' AND password='$pword'";
        $result = mysqli_query($conn, $sql);
        $user_data = mysqli_fetch_assoc($result);

        if ($user_data) {
            // tama ang credentials, i-save sa session
            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role'];

            // i-redirect sa dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            // mali ang username o password
            $error_msg = "Incorrect username or password. Please try again!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HopeMed Pharmacy</title>
    <style>
        /* Global Styles */
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --primary-dark: #0A3D47;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --bg-light: #F2F6FA;
            --font-main: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-main);
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-light);
        }

        /* Layout Panels */
        .left-panel {
            display: none;
            width: 55%;
            background: linear-gradient(145deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%);
            padding: 3rem;
            color: white;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .left-panel {
                display: flex;
            }
        }

        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        /* Left Panel Content */
        .brand-logo {
            display: flex;
            align-items: center; 
            justify-content: flex-start; 
            width: 100%;
            margin-bottom: 1rem;
        }

        .brand-logo-img {
            width: 200px;    
            height: 100; 
            object-fit: contain; 
        }

        .hero-title {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.7);
            max-width: 350px;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 350px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(4px);
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.875rem;
        }

        .footer-note {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
        }

        /* Right Panel Form */
        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 1.875rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(12, 123, 123, 0.15);
            background: white;
            color: var(--text-main);
            font-size: 0.875rem;
            transition: all 0.2s;
            outline: none;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(12, 123, 123, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>

    <div class="left-panel">
        <div>
            <div class="brand-logo">
                <img src="logo.png" alt="HopeMed Logo" class="brand-logo-img">
            </div>
        </div>

        <div>
            <h1 class="hero-title">Management<br>System</h1>
            <p class="hero-subtitle">A centralized platform for inventory, sales, and healthcare operations at HopeMed Pharmacy.</p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <span>⚡</span>
                    <span>Real-time inventory tracking</span>
                </div>
                <div class="feature-item">
                    <span>🛡️</span>
                    <span>Role-based access control</span>
                </div>
                <div class="feature-item">
                    <span>💊</span>
                    <span>Point-of-sale & reporting</span>
                </div>
            </div>
        </div>

        <p class="footer-note">Supporting SDG 3 · Good Health and Well-Being</p>
    </div>

    <div class="right-panel">
        <div class="login-container">
            
            <div class="form-header">
                <h2>Welcome back</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            <form method="POST" action="">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <div style="overflow: hidden;">
                        <label style="float: left;">Password</label>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password-input" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <p style="color: #D63E3E; font-size: 13px; padding: 12px; background: #FFF0F0; border-radius: 8px; border: 1px solid rgba(214, 62, 62, 0.2); margin-bottom: 15px;">
                        <?php echo $error_msg; ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="submit-btn">Sign in</button>
            </form>

        </div>
    </div>             

    <script>
        function togglePassword() {
            var x = document.getElementById("password-input");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>

</body>
</html>