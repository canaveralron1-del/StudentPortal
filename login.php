<?php
// login.php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $conn = getDBConnection();
        
        // Check users table
        $stmt = $conn->prepare("SELECT id, username, password_hash, first_name, last_name, student_id FROM users WHERE username = :username OR email = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['student_id'] = $user['student_id'] ?? '';
            
            // If username is 'admin', set admin flag
            if ($user['username'] === 'admin') {
                $_SESSION['is_admin'] = true;
                header('Location: admin_dashboard.php');
            } else {
                $_SESSION['is_admin'] = false;
                header('Location: profile.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ASCOT Enrollment</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f1724 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #0f1724;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #6b7280;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #0ea5a4;
            box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.1);
        }
        .btn-login {
            width: 100%;
            background: #0f1724;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #1e293b;
        }
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .login-footer a {
            color: #0ea5a4;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>ASCOT Enrollment System</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p>Test credentials:</p>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>Student:</strong> student1 / admin123</p>
            <p style="margin-top: 10px;"><a href="index.php">← Back to Home</a></p>
        </div>
    </div>
</body>
</html>