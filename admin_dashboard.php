<?php
// admin_dashboard.php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get admin data
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'Admin';
$admin_last_login = $_SESSION['admin_last_login'] ?? date('Y-m-d H:i:s');

// Get statistics
try {
    $conn = getDBConnection();
    
    // Count enrollments by status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM enrollments GROUP BY status");
    $enrollmentStats = $stmt->fetchAll();
    
    // Count total students from student table
    $stmt = $conn->query("SELECT COUNT(*) as count FROM student");
    $userCount = $stmt->fetch()['count'];
    
    // Count total messages
    $stmt = $conn->query("SELECT COUNT(*) as count FROM messages");
    $messageCount = $stmt->fetch()['count'];
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ASCOT Enrollment</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg,#f3f6f9 0%, #eef2f6 100%);
            color: #0b1220;
            min-height: 100vh;
        }
        .site-header {
            background: #0f1724;
            color: white;
            padding: 12px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
        }
        .brand {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .brand img { height:42px; width:auto; border-radius:6px; }
        .brand h1 { font-size:16px; margin:0; }
        .admin-nav { display:flex; gap:10px; align-items:center; }
        .admin-nav a {
            color:white; text-decoration:none; padding:8px 12px; border-radius:8px; font-size:14px;
        }
        .admin-nav a:hover { background: rgba(255,255,255,0.06); }
        .admin-nav a.active { background: rgba(255,255,255,0.12); font-weight: 600; }
        
        .container {
            max-width:1200px;
            margin:28px auto;
            padding:0 18px;
        }
        
        .welcome-box {
            background: white;
            border-radius: 14px;
            padding: 30px;
            margin-bottom: 28px;
            box-shadow: 0 8px 24px rgba(12,18,30,0.06);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(9,15,25,0.04);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0f1724;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .recent-enrollments, .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(9,15,25,0.04);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            font-weight: 600;
            color: #374151;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-ongoing { background: #fef3c7; color: #d97706; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="brand">
            <img src="ascot3.png" alt="ASCOT logo" onerror="this.style.display='none'">
            <div>
                <h1>ASCOT Admin Dashboard</h1>
                <div style="font-size:12px; color:rgba(255,255,255,0.8);">Welcome, <?php echo htmlspecialchars($admin_name); ?></div>
            </div>
        </div>
        
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="admin_enrollments.php">Enrollments</a>
            <a href="admin_users.php">Users</a>
            <a href="admin_messages.php">Messages</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    
    <div class="container">
        <div class="welcome-box">
            <h1>Welcome to Admin Dashboard</h1>
            <p>Role: <?php echo htmlspecialchars($admin_role); ?> | Last login: <?php 
                if ($admin_last_login) {
                    $date = new DateTime($admin_last_login);
                    echo $date->format('M j, Y g:i A');
                } else {
                    echo 'First login';
                }
            ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $userCount ?? 0; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $messageCount ?? 0; ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <?php 
            if (!empty($enrollmentStats)): 
                foreach($enrollmentStats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stat['count']; ?></div>
                    <div class="stat-label">Enrollments (<?php echo ucfirst($stat['status']); ?>)</div>
                </div>
                <?php endforeach; 
            endif; ?>
        </div>
        
        <div class="dashboard-grid">
            <div class="recent-enrollments">
                <h2>Recent Enrollments</h2>
                <?php
                try {
                    $stmt = $conn->prepare("SELECT * FROM enrollments ORDER BY created_at DESC LIMIT 10");
                    $stmt->execute();
                    $enrollments = $stmt->fetchAll();
                    
                    if ($enrollments): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Year Level</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($enrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['year_level']); ?></td>
                                <td><span class="status-badge status-<?php echo $enrollment['status']; ?>"><?php echo ucfirst($enrollment['status']); ?></span></td>
                                <td><?php 
                                    $date = new DateTime($enrollment['created_at']);
                                    echo $date->format('M d, Y');
                                ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>No enrollments found.</p>
                    <?php endif;
                    
                } catch(PDOException $e) {
                    echo "<p>Error loading enrollments.</p>";
                }
                ?>
            </div>
            
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
                    <a href="admin_enrollments.php?status=ongoing" style="background: #0f1724; color: white; padding: 12px; border-radius: 8px; text-decoration: none; text-align: center;">Review Pending Enrollments</a>
                    <a href="admin_messages.php" style="background: #0ea5a4; color: white; padding: 12px; border-radius: 8px; text-decoration: none; text-align: center;">Send Messages</a>
                    <a href="admin_users.php" style="background: #8b5cf6; color: white; padding: 12px; border-radius: 8px; text-decoration: none; text-align: center;">Manage Users</a>
                    <a href="logout.php" style="background: #ef4444; color: white; padding: 12px; border-radius: 8px; text-decoration: none; text-align: center;">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>