<?php
session_start();

// DATABASE CONNECTION
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "kurt";

// PROCESS LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['student_id']) && isset($_POST['password'])) {
    
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $input_first_name = trim($_POST['first_name']);
    $input_last_name = trim($_POST['last_name']);
    $input_student_id = trim($_POST['student_id']);
    $input_password = $_POST['password'];
    
    $sql = "SELECT UserName, Password, First_Name, Middle_Name, Last_Name, Student_ID FROM student WHERE Student_ID = ? AND First_Name = ? AND Last_Name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $input_student_id, $input_first_name, $input_last_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        if (password_verify($input_password, $student['Password'])) {
            $_SESSION['user_id'] = $student['UserName'];
            $_SESSION['username'] = $student['UserName'];
            $_SESSION['first_name'] = $student['First_Name'];
            $_SESSION['last_name'] = $student['Last_Name'];
            $_SESSION['middle_name'] = $student['Middle_Name'];
            $_SESSION['student_id'] = $student['Student_ID'];
            $_SESSION['logged_in'] = true;
            
            $full_name = trim($student['First_Name'] . ' ' . $student['Middle_Name'] . ' ' . $student['Last_Name']);
            $_SESSION['full_name'] = $full_name ?: $student['UserName'];
            
            if ($student['UserName'] === 'admin' || $student['UserName'] === 'Administrator') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.html");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password. Please try again.";
        }
    } else {
        $_SESSION['login_error'] = "Invalid credentials. Please check your Student ID, name, or create an account.";
    }
    
    $stmt->close();
    $conn->close();
}

// Fetch announcements from database with filtering
$announcements = [];
$announcement_stats = [
    'total' => 0,
    'events' => 0,
    'notices' => 0,
    'holidays' => 0,
    'campus_updates' => 0
];

try {
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    // Get current date for filtering
    $current_date = date('Y-m-d');
    
    // Fetch active announcements (show important ones first, then by date)
    $sql = "SELECT *, 
            CASE 
                WHEN announcement_type = 'event' THEN '📅 Event'
                WHEN announcement_type = 'notice' THEN '📢 Notice'
                WHEN announcement_type = 'holiday' THEN '🎉 Holiday'
                WHEN announcement_type = 'campus_update' THEN '🏫 Campus Update'
                WHEN announcement_type = 'academic' THEN '📚 Academic'
                ELSE '📌 Announcement'
            END as type_label
            FROM announcements 
            WHERE is_active = 1 
            ORDER BY is_important DESC, event_date ASC, created_at DESC 
            LIMIT 8";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
            $announcement_stats['total']++;
            
            switch($row['announcement_type']) {
                case 'event': $announcement_stats['events']++; break;
                case 'notice': $announcement_stats['notices']++; break;
                case 'holiday': $announcement_stats['holidays']++; break;
                case 'campus_update': $announcement_stats['campus_updates']++; break;
            }
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    // Fallback to default announcements
    $announcements = [
        [
            'title' => 'Enrollment Period', 
            'content' => '📢 Enrollment is now open until April 30.', 
            'type_label' => '📢 Notice',
            'event_date' => date('Y-m-d'),
            'location' => 'Registrar Office',
            'is_important' => 1
        ],
        [
            'title' => 'Student Orientation', 
            'content' => '📝 New student orientation scheduled on May 5.', 
            'type_label' => '📅 Event',
            'event_date' => date('Y-m-d'),
            'location' => 'Main Auditorium',
            'is_important' => 1
        ],
        [
            'title' => 'Campus Maintenance', 
            'content' => '🏫 Campus maintenance: no classes next Monday.', 
            'type_label' => '🏫 Campus Update',
            'event_date' => date('Y-m-d'),
            'location' => 'Campus-wide',
            'is_important' => 0
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="design.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — ASCOT BSIT Portal</title>
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
            color: var(--brand);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: #f8fafc;
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

        .container {
            max-width: 1200px;
            margin: 0px auto;
            padding: 0px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0px;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 0 16px;
            }
        }

        .glass {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: 0 8px 24px rgba(15, 23, 36, 0.08);
            border: 1px solid var(--border);
        }

        .glass h2 {
            color: var(--brand);
            margin-bottom: 24px;
            font-size: 22px;
            font-weight: 600;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(14, 165, 164, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: left;
        }

        label {
            display: block;
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

        input[type="text"]:focus,
        input[type="password"]:focus {
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
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(15, 23, 36, 0.2);
            background: linear-gradient(135deg, #1a2436 0%, var(--brand) 100%);
        }

        .glass > div {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        a {
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        a:hover {
            color: var(--brand);
            text-decoration: underline;
        }

        /* Announcement Styling */
        .announcement-item {
            background: white;
            border-left: 4px solid var(--accent);
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
            transition: all 0.2s;
            border: 1px solid var(--border);
        }

        .announcement-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(15, 23, 36, 0.1);
        }

        .announcement-item.event {
            border-left-color: var(--event-color);
            background: linear-gradient(to right, rgba(59, 130, 246, 0.03), white);
        }

        .announcement-item.notice {
            border-left-color: var(--notice-color);
            background: linear-gradient(to right, rgba(14, 165, 164, 0.03), white);
        }

        .announcement-item.holiday {
            border-left-color: var(--holiday-color);
            background: linear-gradient(to right, rgba(245, 158, 11, 0.03), white);
        }

        .announcement-item.campus_update {
            border-left-color: var(--campus-color);
            background: linear-gradient(to right, rgba(139, 92, 246, 0.03), white);
        }

        .announcement-item.academic {
            border-left-color: var(--academic-color);
            background: linear-gradient(to right, rgba(16, 185, 129, 0.03), white);
        }

        .announcement-item.important {
            border: 2px solid #ef4444;
            background: linear-gradient(to right, rgba(239, 68, 68, 0.05), white);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 12px;
        }

        .announcement-title {
            font-weight: 600;
            color: var(--brand);
            font-size: 15px;
            flex: 1;
        }

        .announcement-type {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .announcement-type.event {
            background: rgba(59, 130, 246, 0.1);
            color: var(--event-color);
        }

        .announcement-type.notice {
            background: rgba(14, 165, 164, 0.1);
            color: var(--notice-color);
        }

        .announcement-type.holiday {
            background: rgba(245, 158, 11, 0.1);
            color: var(--holiday-color);
        }

        .announcement-type.campus_update {
            background: rgba(139, 92, 246, 0.1);
            color: var(--campus-color);
        }

        .announcement-type.academic {
            background: rgba(16, 185, 129, 0.1);
            color: var(--academic-color);
        }

        .announcement-content {
            color: var(--muted);
            margin-bottom: 12px;
        }

        .announcement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #94a3b8;
        }

        .announcement-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .announcement-location {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .important-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            margin-left: 8px;
        }

        /* Announcement Filter */
        .announcement-filter {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 6px 12px;
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

        .filter-btn.event:hover,
        .filter-btn.event.active {
            background: var(--event-color);
            border-color: var(--event-color);
        }

        .filter-btn.notice:hover,
        .filter-btn.notice.active {
            background: var(--notice-color);
            border-color: var(--notice-color);
        }

        .filter-btn.holiday:hover,
        .filter-btn.holiday.active {
            background: var(--holiday-color);
            border-color: var(--holiday-color);
        }

        .filter-btn.campus_update:hover,
        .filter-btn.campus_update.active {
            background: var(--campus-color);
            border-color: var(--campus-color);
        }

        .filter-btn.academic:hover,
        .filter-btn.academic.active {
            background: var(--academic-color);
            border-color: var(--academic-color);
        }

        /* Announcement Stats */
        .announcement-stats {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(14, 165, 164, 0.05);
            border-radius: 8px;
            font-size: 12px;
        }

        .stat-count {
            font-weight: 700;
            color: var(--accent);
        }

        .stat-label {
            color: var(--muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 0px auto;
                gap: 24px;
            }
            
            .glass {
                padding: 24px;
            }
            
            nav {
                padding: 12px 16px;
            }
            
            nav h2 {
                font-size: 16px;
            }
            
            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .announcement-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 12px;
                margin: 16px auto;
            }
            
            .glass {
                padding: 20px;
            }
            
            button[type="submit"] {
                padding: 12px 20px;
                font-size: 15px;
            }
        }
        
        .error-message {
            color: #dc2626;
            margin-bottom: 12px;
            padding: 10px;
            background: #fee2e2;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }
        
        .success-message {
            color: #059669;
            margin-bottom: 12px;
            padding: 10px;
            background: #d1fae5;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid #059669;
        }
        
        .admin-link {
            display: inline-block;
            margin-top: 16px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #0ea5a4 0%, #38bdf8 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .admin-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 164, 0.2);
        }

        .empty-announcements {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty-announcements svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        /* Instructions Section */
.instructions-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.instruction-step {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    border-left: 4px solid #0ea5a4;
}

.step-number {
    background: #0ea5a4;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
    font-size: 14px;
}

.step-content h4 {
    margin: 0 0 6px 0;
    color: var(--brand);
    font-size: 15px;
}

.step-content p {
    margin: 0 0 4px 0;
    color: var(--muted);
    font-size: 14px;
}

.step-content small {
    color: #94a3b8;
    font-size: 12px;
}

.contact-box {
    background: #0f1724;
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 10px;
}

.contact-box h4 {
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-details ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.contact-details li {
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-details li:last-child {
    border-bottom: none;
}

/* For Option 2 Hybrid */
.quick-guide {
    margin-bottom: 24px;
}

.guide-icons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.guide-icon {
    text-align: center;
    flex: 1;
    min-width: 70px;
}

.guide-icon .icon {
    font-size: 24px;
    margin-bottom: 4px;
}

.guide-icon span {
    font-size: 12px;
    color: var(--muted);
}

.guide-arrow {
    color: var(--accent);
    font-weight: bold;
}

.important-notices {
    margin-bottom: 24px;
}

.urgent-notice {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #fef2f2;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #dc2626;
}

.urgent-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.deadlines ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.deadlines li {
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
}

.deadlines li:last-child {
    border-bottom: none;
}
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <div style="display:flex; align-items:center;">
        <img src="ascot3.png" alt="ASCOT Logo">
        <h2>ASCOT — BSIT Enrollment Portal</h2>
    </div>
</nav>

<div class="container">
    <!-- LOGIN CARD -->
    <div class="glass">
        <h2>Student Login</h2>
        
        <?php if (!empty($_SESSION['login_error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['login_error']); ?></div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['register_success'])): ?>
            <div class="success-message"><?php echo $_SESSION['register_success']; ?></div>
            <?php unset($_SESSION['register_success']); ?>
        <?php endif; ?>
        
        <form action="" method="post">
            <div class="form-row">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="Enter first name" required>
            </div>
            
            <div class="form-row">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Enter last name" required>
            </div>
            
            <div class="form-row">
                <label>Student ID</label>
                <input type="text" name="student_id" placeholder="Enter your official Student ID" required>
            </div>
            
            <div class="form-row">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit">Log In</button>
        </form>
        
        <div style="margin-top:12px; display:flex; gap:12px; align-items:center;">
            <a href="forgot_password.php">Forgot password?</a>
            <span style="color:var(--muted);">|</span>
            <a href="new.php">New here? Create account</a>
        </div>
    </div>
    <!-- PORTAL INSTRUCTIONS PANEL -->
<div class="glass">
    <h2> Portal Guide</h2>
    
    <div class="instructions-section">
        <!-- Step 1 -->
        <div class="instruction-step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h4>New Students: Create Account</h4>
                <p>First time here? Click "Create account" to register with your ASCOT student details.</p>
                <small><i>Required: Valid Student ID and personal information</i></small>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="instruction-step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h4>Returning Students: Log In</h4>
                <p>Use your First Name, Last Name, Student ID, and password to access your portal.</p>
                <small><i>Contact IT if you forgot your password</i></small>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="instruction-step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h4>Complete Enrollment</h4>
                <p>After login, go to "Enrollment" to select subjects for the upcoming semester.</p>
                <small><i>Submission deadline: April 30, 2025</i></small>
            </div>
        </div>
        
        <!-- Contact Info 
        <div class="contact-box">
            <h4><i class="fas fa-life-ring"></i> Need Help?</h4>
            <div class="contact-details">
                <p><strong>IT Department Support:</strong></p>
                <ul>
                    <li>📍 Room 201, Main Building</li>
                    <li>📧 bsit.support@ascot.edu.ph</li>
                    <li>📞 (042) 555-1234 (Mon-Fri, 8AM-5PM)</li>
                    <li>💬 Facebook: @ASCOTBSIT</li>
                </ul>
            </div>
        </div>-->
    </div>
</div>
    <!-- ANNOUNCEMENTS PANEL -->
    
        
        
    </div>
</div>

<script>
    // Announcement filtering
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const announcementItems = document.querySelectorAll('.announcement-item');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filterType = this.getAttribute('data-filter');
                
                // Filter announcements
                announcementItems.forEach(item => {
                    if (filterType === 'all' || item.getAttribute('data-type') === filterType) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // View all announcements link
        document.getElementById('view-all-link').addEventListener('click', function(e) {
            e.preventDefault();
            alert('This would show all announcements in a separate page. Feature coming soon!');
        });
        
        // Auto-refresh announcements every 60 seconds
        setInterval(() => {
            console.log('Announcements would auto-refresh in a real application');
        }, 60000);
    });
</script>

</body>
</html>