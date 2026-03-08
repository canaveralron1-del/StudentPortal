<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['username'] !== 'admin') {
    header("Location: StudentLogin.php");
    exit();
}

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "kurt";

// Handle form submissions
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $announcement_type = $_POST['announcement_type'];
        $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $location = trim($_POST['location']);
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "INSERT INTO announcements (title, content, announcement_type, event_date, end_date, location, is_important, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiis", $title, $content, $announcement_type, $event_date, $end_date, $location, $is_important, $is_active, $_SESSION['username']);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Announcement added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding announcement: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        
        header("Location: admin_announcements.php");
        exit();
    }
    
    if (isset($_POST['edit_announcement'])) {
        $id = intval($_POST['id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $announcement_type = $_POST['announcement_type'];
        $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $location = trim($_POST['location']);
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE announcements SET 
                title = ?, content = ?, announcement_type = ?, 
                event_date = ?, end_date = ?, location = ?, 
                is_important = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiii", $title, $content, $announcement_type, $event_date, $end_date, $location, $is_important, $is_active, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Announcement updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating announcement: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        
        header("Location: admin_announcements.php");
        exit();
    }
}

// Handle GET actions
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Announcement deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting announcement: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    header("Location: admin_announcements.php");
    exit();
}

if (isset($_GET['toggle_active'])) {
    $id = intval($_GET['toggle_active']);
    $sql = "UPDATE announcements SET is_active = NOT is_active WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_announcements.php");
    exit();
}

if (isset($_GET['toggle_important'])) {
    $id = intval($_GET['toggle_important']);
    $sql = "UPDATE announcements SET is_important = NOT is_important WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_announcements.php");
    exit();
}

// Fetch all announcements with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT *, 
        CASE 
            WHEN announcement_type = 'event' THEN '📅 Event'
            WHEN announcement_type = 'notice' THEN '📢 Notice'
            WHEN announcement_type = 'holiday' THEN '🎉 Holiday'
            WHEN announcement_type = 'campus_update' THEN '🏫 Campus Update'
            WHEN announcement_type = 'academic' THEN '📚 Academic'
            ELSE '📌 Announcement'
        END as type_label
        FROM announcements WHERE 1=1";

$params = [];
$types = "";

if ($filter !== 'all') {
    $sql .= " AND announcement_type = ?";
    $params[] = $filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR content LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY is_important DESC, event_date ASC, created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN announcement_type = 'event' THEN 1 ELSE 0 END) as events,
    SUM(CASE WHEN announcement_type = 'notice' THEN 1 ELSE 0 END) as notices,
    SUM(CASE WHEN announcement_type = 'holiday' THEN 1 ELSE 0 END) as holidays,
    SUM(CASE WHEN announcement_type = 'campus_update' THEN 1 ELSE 0 END) as campus_updates,
    SUM(CASE WHEN announcement_type = 'academic' THEN 1 ELSE 0 END) as academic,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important
    FROM announcements";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Management — ASCOT BSIT</title>
    <link rel="stylesheet" href="design.css">
    <style>
        :root {
            --brand: #0f1724;
            --accent: #0ea5a4;
            --muted: #6b7280;
            --light-gray: #eef2f6;
            --border: rgba(15, 23, 36, 0.1);
            --event-color: #3b82f6;
            --notice-color: #0ea5a4;
            --holiday-color: #f59e0b;
            --campus-color: #8b5cf6;
            --academic-color: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--brand);
        }

        nav {
            background: var(--brand);
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(15, 23, 36, 0.1);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        nav img {
            height: 42px;
            width: auto;
            border-radius: 6px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-right a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.2s;
        }

        .nav-right a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .dashboard-header h1 {
            font-size: 24px;
            color: var(--brand);
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px var(--border);
            border: 1px solid var(--border);
        }

        .card h2 {
            color: var(--brand);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(14, 165, 164, 0.1);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--brand);
            font-weight: 500;
            font-size: 14px;
        }

        label.required:after {
            content: " *";
            color: #ef4444;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }

        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #0d9493;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--brand);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-success:hover {
            background: #047857;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Filter and Search */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .search-box svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--muted);
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--muted);
        }

        .stat-card.total .stat-value { color: var(--brand); }
        .stat-card.events .stat-value { color: var(--event-color); }
        .stat-card.notices .stat-value { color: var(--notice-color); }
        .stat-card.holidays .stat-value { color: var(--holiday-color); }
        .stat-card.campus_updates .stat-value { color: var(--campus-color); }
        .stat-card.academic .stat-value { color: var(--academic-color); }
        .stat-card.active .stat-value { color: #059669; }
        .stat-card.important .stat-value { color: #dc2626; }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--light-gray);
            font-weight: 600;
            font-size: 14px;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Announcement Type Badges */
        .type-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
            display: inline-block;
        }

        .type-badge.event { background: rgba(59, 130, 246, 0.1); color: var(--event-color); }
        .type-badge.notice { background: rgba(14, 165, 164, 0.1); color: var(--notice-color); }
        .type-badge.holiday { background: rgba(245, 158, 11, 0.1); color: var(--holiday-color); }
        .type-badge.campus_update { background: rgba(139, 92, 246, 0.1); color: var(--campus-color); }
        .type-badge.academic { background: rgba(16, 185, 129, 0.1); color: var(--academic-color); }

        /* Status Badges */
        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .status-active { background: #d1fae5; color: #059669; }
        .status-inactive { background: #fee2e2; color: #dc2626; }
        .status-important { background: #fef3c7; color: #d97706; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Message Styles */
        .message {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-success {
            background: #d1fae5;
            color: #059669;
            border-left: 4px solid #059669;
        }

        .message-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        /* Date Display */
        .date-display {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            nav {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .nav-right {
                width: 100%;
                justify-content: flex-end;
            }
            
            th, td {
                padding: 8px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 16px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-left">
            <img src="ascot3.png" alt="ASCOT Logo">
            <div>
                <h2>Announcement Management</h2>
                <div style="font-size: 14px; color: rgba(255,255,255,0.8);">ASCOT BSIT Portal</div>
            </div>
        </div>
        <div class="nav-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message message-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
                <button onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <div class="dashboard-header">
            <h1>📢 Announcement Management</h1>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add New Announcement
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Announcements</div>
            </div>
            <div class="stat-card events">
                <div class="stat-value"><?php echo $stats['events'] ?? 0; ?></div>
                <div class="stat-label">Events</div>
            </div>
            <div class="stat-card notices">
                <div class="stat-value"><?php echo $stats['notices'] ?? 0; ?></div>
                <div class="stat-label">Notices</div>
            </div>
            <div class="stat-card holidays">
                <div class="stat-value"><?php echo $stats['holidays'] ?? 0; ?></div>
                <div class="stat-label">Holidays</div>
            </div>
            <div class="stat-card campus_updates">
                <div class="stat-value"><?php echo $stats['campus_updates'] ?? 0; ?></div>
                <div class="stat-label">Campus Updates</div>
            </div>
            <div class="stat-card academic">
                <div class="stat-value"><?php echo $stats['academic'] ?? 0; ?></div>
                <div class="stat-label">Academic</div>
            </div>
            <div class="stat-card active">
                <div class="stat-value"><?php echo $stats['active'] ?? 0; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card important">
                <div class="stat-value"><?php echo $stats['important'] ?? 0; ?></div>
                <div class="stat-label">Important</div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="filter-bar">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <form method="GET" action="" style="display: inline;">
                    <input type="text" name="search" placeholder="Search announcements..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <div class="filter-buttons">
                <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=event" class="filter-btn <?php echo $filter === 'event' ? 'active' : ''; ?>">📅 Events</a>
                <a href="?filter=notice" class="filter-btn <?php echo $filter === 'notice' ? 'active' : ''; ?>">📢 Notices</a>
                <a href="?filter=holiday" class="filter-btn <?php echo $filter === 'holiday' ? 'active' : ''; ?>">🎉 Holidays</a>
                <a href="?filter=campus_update" class="filter-btn <?php echo $filter === 'campus_update' ? 'active' : ''; ?>">🏫 Campus Updates</a>
                <a href="?filter=academic" class="filter-btn <?php echo $filter === 'academic' ? 'active' : ''; ?>">📚 Academic</a>
            </div>
        </div>

        <!-- Announcements Table -->
        <div class="card">
            <h2>Announcements List (<?php echo count($announcements); ?>)</h2>
            
            <?php if (empty($announcements)): ?>
                <div style="text-align: center; padding: 40px; color: var(--muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No announcements found.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): 
                                $type_class = str_replace(' ', '_', strtolower($announcement['announcement_type']));
                                $event_date = !empty($announcement['event_date']) ? date('M d, Y', strtotime($announcement['event_date'])) : 'TBA';
                            ?>
                                <tr>
                                    <td><?php echo $announcement['id']; ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                        <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                            <?php echo substr(htmlspecialchars($announcement['content']), 0, 60); ?>...
                                        </div>
                                    </td>
                                    <td><span class="type-badge <?php echo $type_class; ?>"><?php echo $announcement['type_label']; ?></span></td>
                                    <td>
                                        <div class="date-display">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                            <?php echo $event_date; ?>
                                        </div>
                                    </td>
                                    <td><?php echo !empty($announcement['location']) ? htmlspecialchars($announcement['location']) : '-'; ?></td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <?php if ($announcement['is_active']): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">Inactive</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($announcement['is_important']): ?>
                                                <span class="status-badge status-important">Important</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?toggle_active=<?php echo $announcement['id']; ?>" 
                                               class="btn btn-sm btn-secondary">
                                                <?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            <a href="?toggle_important=<?php echo $announcement['id']; ?>" 
                                               class="btn btn-sm btn-secondary">
                                                <?php echo $announcement['is_important'] ? 'Unmark' : 'Mark Important'; ?>
                                            </a>
                                            <a href="?delete=<?php echo $announcement['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this announcement?')">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Announcement</h2>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="required">Title</label>
                        <input type="text" name="title" required maxlength="255" 
                               placeholder="Enter announcement title">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Type</label>
                        <select name="announcement_type" required>
                            <option value="event">📅 Event</option>
                            <option value="notice" selected>📢 Notice</option>
                            <option value="holiday">🎉 Holiday</option>
                            <option value="campus_update">🏫 Campus Update</option>
                            <option value="academic">📚 Academic</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Event Date</label>
                        <input type="date" name="event_date">
                    </div>
                    
                    <div class="form-group">
                        <label>End Date (for multi-day events)</label>
                        <input type="date" name="end_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" maxlength="255" 
                               placeholder="Enter location (optional)">
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="required">Content</label>
                        <textarea name="content" required 
                                  placeholder="Enter announcement content (supports emojis)"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="is_important">
                                Mark as Important
                            </label>
                            <label>
                                <input type="checkbox" name="is_active" checked>
                                Active (visible to students)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" name="add_announcement" class="btn btn-primary">
                        Add Announcement
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                closeAddModal();
            }
        }
        
        // Auto-submit search on enter
        document.querySelector('.search-box input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Set minimum date for event date fields
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="event_date"]').min = today;
            document.querySelector('input[name="end_date"]').min = today;
        });
    </script>
</body>
</html>