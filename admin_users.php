<?php
// admin_users.php
//session_start();
require_once 'config/database.php';

// Check if admin is logged in


// Database connection
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_user'])) {
        // Edit user
        $user_id = $_POST['user_id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        
        // Build update query
        $sql = "UPDATE student SET First_Name = ?, Middle_Name = ?, Last_Name = ?, Student_ID = ? WHERE UserName = ?";
        $params = [$first_name, $middle_name, $last_name, $student_id, $user_id];
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_password = "UPDATE student SET Password = ? WHERE UserName = ?";
                $stmt_password = $conn->prepare($sql_password);
                $stmt_password->execute([$hashed_password, $user_id]);
                $stmt_password = null;
            }
            
            $_SESSION['admin_message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
            
        } catch(PDOException $e) {
            $_SESSION['admin_message'] = "Error updating user: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: admin_users.php");
        exit();
    }
    
    if (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = $_POST['user_id'];
        
        try {
            $sql = "DELETE FROM student WHERE UserName = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            
            $_SESSION['admin_message'] = "User deleted successfully!";
            $_SESSION['message_type'] = "success";
            
        } catch(PDOException $e) {
            $_SESSION['admin_message'] = "Error deleting user: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: admin_users.php");
        exit();
    }
}

try {
    $sql = "SELECT UserName, First_Name, Middle_Name, Last_Name, Student_ID, Password FROM student ORDER BY Last_Name, First_Name";
    $stmt = $conn->query($sql);
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $users = [];
    $error = "Error loading users: " . $e->getMessage();
}

$edit_user = null;
if (isset($_GET['edit'])) {
    $user_id = $_GET['edit'];
    try {
        $sql = "SELECT UserName, First_Name, Middle_Name, Last_Name, Student_ID, Password FROM student WHERE UserName = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $edit_user = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error loading user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ASCOT Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
    /* Home Page Color Scheme */
    :root {
      --brand: #0f1724;        /* dark navy */
      --accent: #0ea5a4;       /* teal */
      --accent-light: #38bdf8; /* light teal/blue */
      --muted: #6b7280;        /* gray */
      --light-gray: #eef2f6;
      --card: #ffffff;
      --border: rgba(15, 23, 36, 0.08);
      --shadow: rgba(15, 23, 36, 0.1);
    }
    /* Add this to the existing style section in the second file */
/* Navigation Bar - MAKE SAME AS admin_users.php */
/* Navigation Bar - MAKE SAME AS admin_users.php */
.site-header {
    background: #0f1724;
    color: white;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(15, 23, 36, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand img { 
    height: 42px; 
    width: auto; 
    border-radius: 6px; 
}

.brand h1 { 
    font-size: 18px; 
    font-weight: 600;
    margin: 0; 
}

.brand .subtitle {
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    margin-top: 3px;
}

.admin-nav { 
    display: flex; 
    gap: 12px; 
    align-items: center; 
}

.admin-nav a {
    color: white; 
    text-decoration: none; 
    padding: 8px 16px; 
    border-radius: 8px; 
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.admin-nav a:hover { 
    background: rgba(255,255,255,0.1); 
}

.admin-nav a.active { 
    background: rgba(255,255,255,0.15); 
    font-weight: 600; 
}
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    

    body {
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg, #f3f6f9 0%, var(--light-gray) 100%);
      color: var(--brand);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      line-height: 1.45;
      padding: 0px;
      min-height: 100vh;
    }

    /* Header */
    .admin-header {
      background: var(--brand);
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 8px 24px var(--shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .admin-header h1 {
      font-size: 24px;
      font-weight: 600;
      margin: 0;
    }

    .admin-actions-header {
      display: flex;
      gap: 12px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      transition: all 0.2s;
      border: 1px solid transparent;
      cursor: pointer;
      font-family: inherit;
    }

    .btn-primary {
      background: var(--brand);
      color: white;
      box-shadow: 0 6px 18px rgba(15, 23, 36, 0.1);
    }

    .btn-primary:hover {
      background: #1a2436;
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(15, 23, 36, 0.15);
    }

    .btn-secondary {
      background: transparent;
      color: var(--brand);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover {
      background: rgba(15, 23, 36, 0.03);
    }

    .btn-accent {
      background: linear-gradient(90deg, var(--accent), var(--accent-light));
      color: white;
      box-shadow: 0 6px 18px rgba(14, 165, 164, 0.15);
    }

    .btn-accent:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* Main Container */
    .wrap {
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Filters */
    .filter-section {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 16px var(--shadow);
    }

    .filter-section h3 {
      color: var(--brand);
      margin-bottom: 16px;
      font-size: 16px;
      font-weight: 600;
    }

    .filter-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-tab {
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      background: rgba(15, 23, 36, 0.03);
      color: var(--muted);
      border: 1px solid var(--border);
      transition: all 0.2s;
    }

    .filter-tab:hover {
      background: rgba(15, 23, 36, 0.06);
      color: var(--brand);
    }

    .filter-tab.active {
      background: var(--accent);
      color: white;
      border-color: var(--accent);
    }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      border: 1px solid var(--border);
      box-shadow: 0 4px 16px var(--shadow);
      text-align: center;
    }

    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .stat-label {
      color: var(--muted);
      font-size: 14px;
      font-weight: 500;
    }

    /* Table */
    .table-container {
      background: var(--card);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: 0 8px 24px var(--shadow);
      margin-bottom: 24px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
    }

    th {
      background: var(--brand);
      color: white;
      padding: 16px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
    }

    td {
      padding: 16px;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
    }

    tr:hover {
      background: rgba(14, 165, 164, 0.02);
    }

    tr:last-child td {
      border-bottom: none;
    }

    /* Status badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending {
      background: rgba(234, 179, 8, 0.1);
      color: #92400e;
    }

    .status-accepted {
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
    }

    .status-rejected {
      background: rgba(239, 68, 68, 0.1);
      color: #991b1b;
    }

    /* Action buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .action-btn {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .btn-accept {
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .btn-accept:hover {
      background: rgba(34, 197, 94, 0.2);
    }

    .btn-reject {
      background: rgba(239, 68, 68, 0.1);
      color: #991b1b;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-reject:hover {
      background: rgba(239, 68, 68, 0.2);
    }

    .btn-message {
      background: rgba(14, 165, 164, 0.1);
      color: var(--accent);
      border: 1px solid rgba(14, 165, 164, 0.2);
    }

    .btn-message:hover {
      background: rgba(14, 165, 164, 0.2);
    }

    .btn-delete {
      background: rgba(107, 114, 128, 0.1);
      color: var(--muted);
      border: 1px solid rgba(107, 114, 128, 0.2);
    }

    .btn-delete:hover {
      background: rgba(107, 114, 128, 0.2);
    }

    /* Subjects display */
    .subjects-list {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .subjects-list:hover {
      white-space: normal;
      overflow: visible;
      position: relative;
      z-index: 10;
      background: white;
      padding: 8px;
      border-radius: 8px;
      box-shadow: 0 4px 12px var(--shadow);
    }

    /* Messages */
    .message-alert {
      background: rgba(14, 165, 164, 0.1);
      border: 1px solid rgba(14, 165, 164, 0.2);
      color: var(--accent);
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .error-alert {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
      color: #991b1b;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .empty-state-icon {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .admin-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
      }

      .admin-actions-header {
        flex-direction: column;
        width: 100%;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      body {
        padding: 0px;
      }
    }

    @media (max-width: 480px) {
      .filter-tabs {
        flex-direction: column;
      }

      .filter-tab {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>
    <!-- Navigation Bar -->
    <!-- Navigation Bar -->
<nav class="site-header">
    <div class="brand">
        <img src="ascot3.png" alt="ASCOT Logo">
        <div>
            <h1>ASCOT — Admin Portal</h1>
            <div class="subtitle">Aurora State College of Technology</div>
        </div>
    </div>
    
    <div class="admin-nav">
        <a href="admin.php">Enrollment</a>
        <a href="admin_users.php" class="active">Management</a>
        <a href="admin_logout.php" style="color: #ef4444; margin-left: 20px;">Logout</a>
    </div>
</nav>

    <div class="container">

        <!-- Messages -->
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="message-alert <?php echo $_SESSION['message_type'] === 'success' ? 'success-alert' : 'error-alert'; ?>">
                <?php 
                echo $_SESSION['admin_message'];
                unset($_SESSION['admin_message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message-alert error-alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($edit_user): ?>
            <!-- Edit User Form -->
            <div class="edit-form-container">
                <h2 class="form-title">Edit Student: <?php echo htmlspecialchars($edit_user['First_Name'] . ' ' . $edit_user['Last_Name']); ?></h2>
                
                <div class="user-info">
                    <strong>Username:</strong> <?php echo htmlspecialchars($edit_user['UserName']); ?><br>
                    <strong>Current Student ID:</strong> <?php echo htmlspecialchars($edit_user['Student_ID'] ?? 'Not set'); ?>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['UserName']); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_user['First_Name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_user['Middle_Name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_user['Last_Name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_user['Student_ID'] ?? ''); ?>"
                                   placeholder="Enter student ID (e.g., 2023-0001)">
                            <small style="color: #6b7280; font-size: 12px;">Leave empty to remove Student ID</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Password (Optional)</label>
                            <input type="password" name="new_password" class="form-control" 
                                   placeholder="Leave empty to keep current password">
                            <small style="color: #6b7280; font-size: 12px;">Only enter if you want to change the password</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="edit_user" class="btn btn-primary">Update Student</button>
                        <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="delete_user" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">
                            Delete Student
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Users List -->
            <div class="users-table">
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Student ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['UserName']); ?></td>
                                    <td><?php echo htmlspecialchars($user['First_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Middle_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Last_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Student_ID'] ?? 'Not set'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo urlencode($user['UserName']); ?>" 
                                               class="action-btn edit-btn">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Students Found</h3>
                        <p>No student accounts have been created yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>