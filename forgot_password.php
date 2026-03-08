<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Process form if submitted
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "kurt");
    
    if ($conn->connect_error) {
        $error = "Database connection failed: " . $conn->connect_error;
    } else {
        // Get form data
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        
        // Validate
        if (empty($identifier) || empty($password) || empty($confirm)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if identifier exists
            $sql = "SELECT UserName FROM student WHERE Student_ID = ? OR UserName = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Student ID or Username not found.";
            } else {
                $user_data = $result->fetch_assoc();
                $username = $user_data['UserName'];
                
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE student SET Password = ? WHERE UserName = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $username);
                
                if ($update_stmt->execute()) {
                    $success = "Password reset successfully!";
                    // Redirect after 2 seconds
                    echo '<script>setTimeout(function() { window.location.href = "StudentLogin.php"; }, 2000);</script>';
                } else {
                    $error = "Failed to reset password.";
                }
                $update_stmt->close();
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — ASCOT BSIT Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0f1724;
            --accent: #0ea5a4;
            --accent-light: #38bdf8;
            --muted: #6b7280;
            --light-gray: #eef2f6;
            --border: rgba(15, 23, 36, 0.1);
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--brand);
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
        }

        nav {
            background: var(--brand);
            color: white;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(15, 23, 36, 0.1);
        }

        nav img {
            height: 42px;
            width: auto;
            margin-right: 16px;
            border-radius: 6px;
        }

        nav h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .page {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .container {
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 8px 24px rgba(15, 23, 36, 0.08);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 480px;
        }

        h2 {
            color: var(--brand);
            margin-bottom: 16px;
            font-size: 24px;
            font-weight: 700;
        }

        p {
            color: var(--muted);
            margin-bottom: 24px;
            line-height: 1.6;
            font-size: 15px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--brand);
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.1);
        }

        button[type="submit"] {
            background: linear-gradient(135deg, var(--brand) 0%, #1a2436 100%);
            color: white;
            border: none;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(15, 23, 36, 0.15);
            font-family: 'Inter', sans-serif;
            width: 100%;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(15, 23, 36, 0.2);
            background: linear-gradient(135deg, #1a2436 0%, var(--brand) 100%);
        }

        a {
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: var(--muted);
            font-size: 14px;
            border-top: 1px solid var(--border);
        }

        .error-message {
            color: #dc2626;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: #fee2e2;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }

        .success-message {
            color: #059669;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: #d1fae5;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid #059669;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 480px) {
            .container {
                padding: 32px 24px;
            }
            
            .page {
                padding: 24px 16px;
            }
            
            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<nav>
    <div style="display:flex; align-items:center;">
        <img src="ascot3.png" alt="ASCOT Logo">
        <h2>ASCOT — BSIT Enrollment Portal</h2>
    </div>
</nav>

<main class="page">
    <div class="container">
        <h2>Reset Your Password</h2>
        <p>Enter your Student ID or Username and a new password.</p>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-row">
                <label for="identifier">Student ID or Username</label>
                <input type="text" id="identifier" name="identifier" placeholder="Enter your Student ID or Username" required value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter new password" required>
            </div>
            
            <div class="form-row">
                <label for="confirm">Confirm New Password</label>
                <input type="password" id="confirm" name="confirm" placeholder="Re-enter new password" required>
            </div>
            
            <button type="submit">Reset Password</button>
        </form>
        
        <div class="back-link">
            <a href="StudentLogin.php">← Back to login</a>
        </div>
    </div>
</main>

<footer>© 2025 ASCOT BSIT Enrollment System</footer>

</body>
</html>