<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: NewL.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "kurt");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get user info from database FIRST
$username = $_SESSION['username'];
$sql = "SELECT UserName, First_Name, Middle_Name, Last_Name, email FROM student WHERE UserName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found in database.");
}

$student = $result->fetch_assoc();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Check if email is being changed and if it already exists
        if ($email !== $student['email']) {
            $check_email_sql = "SELECT UserName FROM student WHERE email = ? AND UserName != ?";
            $check_email_stmt = $conn->prepare($check_email_sql);
            $check_email_stmt->bind_param("ss", $email, $_SESSION['username']);
            $check_email_stmt->execute();
            $email_result = $check_email_stmt->get_result();
            
            if ($email_result->num_rows > 0) {
                $_SESSION['profile_error'] = "Email '$email' is already registered by another user.";
                $check_email_stmt->close();
                header("Location: profile.php");
                exit();
            }
            $check_email_stmt->close();
        }
        
        $update_sql = "UPDATE student SET First_Name = ?, Middle_Name = ?, Last_Name = ?, email = ? WHERE UserName = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssss", $first_name, $middle_name, $last_name, $email, $_SESSION['username']);
        
        if ($update_stmt->execute()) {
            $_SESSION['profile_success'] = "Profile updated successfully!";
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            // Refresh student data
            $student['First_Name'] = $first_name;
            $student['Middle_Name'] = $middle_name;
            $student['Last_Name'] = $last_name;
            $student['email'] = $email;
        } else {
            $_SESSION['profile_error'] = "Failed to update profile: " . $conn->error;
        }
        $update_stmt->close();
    }
    
    // Handle Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['password_error'] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['password_error'] = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $_SESSION['password_error'] = "New password must be at least 8 characters long.";
        } else {
            // Get current password hash
            $pass_sql = "SELECT Password FROM student WHERE UserName = ?";
            $pass_stmt = $conn->prepare($pass_sql);
            $pass_stmt->bind_param("s", $_SESSION['username']);
            $pass_stmt->execute();
            $pass_result = $pass_stmt->get_result();
            
            if ($pass_result->num_rows > 0) {
                $user_data = $pass_result->fetch_assoc();
                $current_hash = $user_data['Password'];
                
                // Verify current password
                if (password_verify($current_password, $current_hash)) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $update_pass_sql = "UPDATE student SET Password = ? WHERE UserName = ?";
                    $update_pass_stmt = $conn->prepare($update_pass_sql);
                    $update_pass_stmt->bind_param("ss", $new_hash, $_SESSION['username']);
                    
                    if ($update_pass_stmt->execute()) {
                        $_SESSION['password_success'] = "Password changed successfully!";
                    } else {
                        $_SESSION['password_error'] = "Failed to update password.";
                    }
                    $update_pass_stmt->close();
                } else {
                    $_SESSION['password_error'] = "Current password is incorrect.";
                }
            }
            $pass_stmt->close();
        }
        
        header("Location: profile.php");
        exit();
    }
}

// Create full name
$full_name = trim($student['First_Name'] . ' ' . $student['Middle_Name'] . ' ' . $student['Last_Name']);
if (empty(trim($full_name))) {
    $full_name = $student['UserName'];
}

// Initialize enrollment variables
$enrollment = null;
$subjects = [];
$year_text = 'Not Enrolled';
$semester_text = '';
$enrollment_date = '';
$enrollment_status = 'Not Enrolled';

// Get latest enrollment data if exists
$enrollment_sql = "SELECT * FROM enrollments WHERE username = ? ORDER BY created_at DESC LIMIT 1";
$enrollment_stmt = $conn->prepare($enrollment_sql);
$enrollment_stmt->bind_param("s", $username);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();

if ($enrollment_result->num_rows > 0) {
    $enrollment = $enrollment_result->fetch_assoc();
    
    // Get subjects if enrollment exists
    if (!empty($enrollment['subjects'])) {
        $subjects = json_decode($enrollment['subjects'], true);
    }
    
    // Map year level to text
    $year_mapping = [
        '1' => '1st Year',
        '2' => '2nd Year', 
        '3' => '3rd Year',
        '4' => '4th Year',
        'mid' => 'Mid Year'
    ];
    $year_text = $year_mapping[$enrollment['year_level']] ?? 'Year ' . $enrollment['year_level'];
    
    // Map semester to text
    $semester_mapping = [
        '1' => '1st Semester',
        '2' => '2nd Semester'
    ];
    $semester_text = $semester_mapping[$enrollment['semester']] ?? 'Semester ' . $enrollment['semester'];
    
    // Format enrollment date
    $enrollment_date = date('F d, Y', strtotime($enrollment['created_at']));
    $enrollment_status = ucfirst($enrollment['status']);
}

// Get user initials for avatar
$initials = '';
if (!empty($student['First_Name']) && !empty($student['Last_Name'])) {
    $initials = strtoupper(substr($student['First_Name'], 0, 1) . substr($student['Last_Name'], 0, 1));
} else {
    $initials = strtoupper(substr($student['UserName'], 0, 2));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Profile — ASCOT BSIT Enrollment</title>
  
  <link rel="stylesheet" href="design2.css">
  <style>
    /* Reset-ish */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    :root{
      --brand:#0f1724;        /* dark navy */
      --accent:#0ea5a4;       /* teal */
      --muted:#6b7280;        /* gray */
      --card:#ffffff;
      --glass: rgba(255,255,255,0.6);
    }
    body {
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg,#f3f6f9 0%, #eef2f6 100%);
      color: #0b1220;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      line-height:1.45;
    }

    /* NAV */
    header.site-header {
      background: var(--brand);
      color: white;
      padding: 12px 18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
    }
    .brand {
      display:flex;
      align-items:center;
      gap:12px;
    }
    .brand img { height:42px; width:auto; border-radius:6px; }
    .brand h1 { font-size:18px; letter-spacing:0.2px; margin:0; }
    nav.primary { display:flex; gap:10px; align-items:center; }
    nav.primary a {
      color:white; text-decoration:none; padding:8px 12px; border-radius:8px; font-size:14px;
      opacity:0.95;
    }
    nav.primary a:hover { background: rgba(255,255,255,0.06); }
    nav.primary a.active { background: rgba(255,255,255,0.06); }

    /* Main layout */
    main.container {
      max-width:1200px;
      margin:28px auto;
      padding:0 18px;
    }

    /* Profile Hero Banner */
    .profile-hero {
      background: linear-gradient(rgba(15,23,36,0.85), rgba(15,23,36,0.75)), url('ascot.jpg');
      background-size: cover;
      background-position: center;
      border-radius: 14px;
      padding: 50px 40px;
      color: white;
      margin-bottom: 40px;
      box-shadow: 0 8px 24px rgba(12,18,30,0.1);
      min-height: 300px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }
    .profile-hero .kicker {
      background: linear-gradient(90deg,var(--accent), #38bdf8);
      color:white;
      padding:6px 12px;
      border-radius:999px;
      font-size:14px;
      font-weight:600;
      letter-spacing:0.2px;
      box-shadow: 0 6px 18px rgba(14,165,164,0.2);
      width:fit-content;
      margin-bottom: 16px;
    }
    .profile-hero h2 { font-size:32px; margin-bottom:12px; }
    .profile-hero p { font-size:16px; opacity:0.9; max-width:700px; }

    /* Profile Avatar */
    .profile-avatar {
      position: absolute;
      bottom: -40px;
      left: 40px;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: white;
      border: 4px solid white;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .avatar-initials {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), #38bdf8);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      font-weight: 700;
    }

    /* Content Cards */
    .content-section {
      background: white;
      border-radius: 12px;
      padding: 28px;
      margin-bottom: 24px;
      border: 1px solid rgba(15,23,36,0.04);
      box-shadow: 0 6px 18px rgba(12,18,30,0.04);
    }
    .section-title {
      color: var(--brand);
      font-size: 22px;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid rgba(14,165,164,0.1);
    }

    /* Profile Sections - ONE PER ROW */
    .profile-sections {
      display: flex;
      flex-direction: column;
      gap: 24px;
      margin-top: 60px;
    }

    /* Profile Cards */
    .profile-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid rgba(15,23,36,0.04);
      box-shadow: 0 6px 18px rgba(9,15,25,0.04);
    }
    
    .profile-card h2 {
      color: var(--brand);
      font-size: 18px;
      margin-bottom: 18px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(15,23,36,0.06);
    }

    /* Information Rows */
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 14px 0;
      border-bottom: 1px solid rgba(15,23,36,0.04);
    }
    
    .info-row:last-child {
      border-bottom: none;
    }
    
    .info-label {
      color: var(--muted);
      font-weight: 500;
    }
    
    .info-value {
      color: var(--brand);
      font-weight: 600;
      text-align: right;
    }

    /* Stats Cards */
    .stats-section {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin: 32px 0;
    }
    
    @media (max-width: 768px) {
      .stats-section {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (max-width: 480px) {
      .stats-section {
        grid-template-columns: 1fr;
      }
    }
    
    .stat-box {
      background: white;
      padding: 24px;
      border-radius: 12px;
      text-align: center;
      border: 1px solid rgba(15,23,36,0.04);
      box-shadow: 0 6px 18px rgba(12,18,30,0.04);
      transition: transform 0.2s;
    }
    
    .stat-box:hover {
      transform: translateY(-4px);
    }
    
    .stat-number {
      font-size: 36px;
      font-weight: bold;
      color: var(--accent);
      margin-bottom: 8px;
    }
    
    .stat-label {
      color: var(--brand);
      font-size: 14px;
      font-weight: 600;
    }

    /* Courses List */
    .courses-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .course-item {
      padding: 14px;
      margin-bottom: 10px;
      background: rgba(15,23,36,0.02);
      border-radius: 8px;
      border-left: 4px solid var(--accent);
    }
    
    .course-code {
      font-weight: 700;
      color: var(--brand);
    }
    
    .course-name {
      color: var(--muted);
      font-size: 14px;
      margin-top: 4px;
    }

    /* Buttons */
    .btn{
      display:inline-flex; gap:10px; align-items:center; text-decoration:none;
      padding:10px 16px; border-radius:10px; font-weight:600; font-size:14px;
      border:1px solid rgba(15,23,36,0.06);
      transition: all 0.2s;
      cursor: pointer;
      font-family: inherit;
    }
    .btn.primary { 
      background:var(--brand); color:white; 
      box-shadow: 0 8px 18px rgba(15,23,36,0.06); 
    }
    .btn.primary:hover {
      background: #1a2436;
      transform: translateY(-1px);
    }
    .btn.outline { 
      background:transparent; color:var(--brand); 
      border:1px solid rgba(15,23,36,0.08); 
    }
    .btn.outline:hover {
      background: rgba(15,23,36,0.02);
    }

    /* Status Badge */
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-accepted {
      background: #d1fae5;
      color: #059669;
    }
    
    .status-ongoing {
      background: #fef3c7;
      color: #d97706;
    }
    
    .status-pending {
      background: #e0e7ff;
      color: #4f46e5;
    }

    /* Success/Error Messages */
    .alert {
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid;
    }
    
    .alert-success {
      background: #d1fae5;
      border-color: #059669;
      color: #065f46;
    }
    
    .alert-error {
      background: #fee2e2;
      border-color: #dc2626;
      color: #7f1d1d;
    }

    /* Edit Form */
    .edit-form {
      display: none;
      margin-top: 20px;
      padding: 20px;
      background: #f8fafc;
      border-radius: 8px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: var(--brand);
      font-weight: 500;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: 'Inter', sans-serif;
    }
    
    .form-btns {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    /* Password strength indicator */
    .password-strength {
      margin-top: 4px;
      font-size: 12px;
    }

    .strength-meter {
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin-top: 4px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      transition: width 0.3s;
    }

    /* Form requirement indicators */
    .required-indicator {
      color: #ef4444;
      margin-left: 2px;
    }

    /* Toggle password visibility */
    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      font-size: 12px;
    }

    .toggle-password:hover {
      color: var(--accent);
    }

    /* Buttons Row for Courses Section */
    .buttons-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 24px;
    }
    
    .buttons-row .btn {
      flex: 1;
      min-width: 150px;
    }
    
    @media (max-width: 768px) {
      .buttons-row {
        flex-direction: column;
      }
      
      .buttons-row .btn {
        width: 100%;
      }
    }

    /* Footer */
    footer.site-footer {
      margin-top:28px; padding:20px; background:var(--brand); color:white; border-radius:10px;
      max-width:1200px; margin-left:auto; margin-right:auto; display:flex; justify-content:space-between; align-items:center;
    }

    /* Responsive */
    @media (max-width:1000px){
      .stats-section { grid-template-columns: repeat(2, 1fr); }
      footer.site-footer { flex-direction:column; gap:12px; text-align:center; }
    }
    @media (max-width:768px){
      .stats-section { grid-template-columns: 1fr; }
      .profile-hero h2 { font-size: 26px; }
      .profile-hero { padding: 30px 20px; }
      .profile-avatar {
        left: 50%;
        transform: translateX(-50%);
        bottom: -60px;
      }
    }
    @media (max-width:560px){
      header.site-header { flex-direction: column; align-items: flex-start; }
      nav.primary { width: 100%; margin-top: 10px; }
    }
  </style>
</head>

<body>
  <!-- HEADER / NAV -->
  <header class="site-header" role="banner">
    <div class="brand">
      <img src="ascot3.png" alt="ASCOT logo" onerror="this.style.display='none'">
      <div>
        <h1 style="font-size:16px;">ASCOT — BSIT Enrollment Portal</h1>
        <div style="font-size:12px; color:rgba(255,255,255,0.8); margin-top:3px;">Aurora State College of Technology</div>
      </div>
    </div>

    <nav class="primary" role="navigation" aria-label="Primary">
      <a href="index.html">Home</a>
      <a href="enrollment.php">Enroll</a>
      <a href="about.html">Programs</a>
      <a href="contact.html">Contact</a>
      <a href="messages.html">Messages</a>
      <a href="profile.php" class="active">My Profile</a>
      <a href="logIn.html" class="active">Log Out</a>
    </nav>
  </header>

  <main class="container" role="main">
    <!-- Success/Error Messages -->
    <?php if (!empty($_SESSION['profile_success'])): ?>
      <div class="alert alert-success"><?php echo $_SESSION['profile_success']; unset($_SESSION['profile_success']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['profile_error'])): ?>
      <div class="alert alert-error"><?php echo $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['enrollment_success'])): ?>
      <div class="alert alert-success"><?php echo $_SESSION['enrollment_success']; unset($_SESSION['enrollment_success']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['enrollment_error'])): ?>
      <div class="alert alert-error"><?php echo $_SESSION['enrollment_error']; unset($_SESSION['enrollment_error']); ?></div>
    <?php endif; ?>
    
    <!-- Password Change Messages -->
    <?php if (!empty($_SESSION['password_success'])): ?>
      <div class="alert alert-success"><?php echo $_SESSION['password_success']; unset($_SESSION['password_success']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['password_error'])): ?>
      <div class="alert alert-error"><?php echo $_SESSION['password_error']; unset($_SESSION['password_error']); ?></div>
    <?php endif; ?>

    <!-- Profile Hero Banner -->
    <section class="profile-hero" aria-label="Student Profile">
      <div class="profile-avatar">
        <div class="avatar-initials"><?php echo $initials; ?></div>
      </div>
      <div class="kicker">WELCOME, STUDENT!</div>
      <h2><?php echo htmlspecialchars($full_name); ?></h2>
      <p>ASCOT BSIT Student • <?php echo $year_text; ?> • Username: <?php echo htmlspecialchars($student['UserName']); ?></p>
    </section>

    <!-- Profile Stats -->
    <section class="content-section" aria-labelledby="stats-heading">
      <h3 class="section-title" id="stats-heading">Academic Statistics</h3>
      <div class="stats-section">
        <div class="stat-box">
          <div class="stat-number"><?php echo $enrollment ? count($subjects) : '0'; ?></div>
          <div class="stat-label">Subjects Enrolled</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-number"><?php echo $enrollment ? $year_text : '--'; ?></div>
          <div class="stat-label">Year Level</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-number"><?php echo $enrollment ? $enrollment_status : 'None'; ?></div>
          <div class="stat-label">Enrollment Status</div>
        </div>
      </div>
    </section>

    <!-- Profile Sections - ONE PER ROW -->
    <div class="profile-sections">
      
      <!-- Personal Information -->
      <section class="profile-card" aria-labelledby="personal-heading">
        <h2 id="personal-heading">Personal Information</h2>
        <div class="info-row">
          <span class="info-label">Full Name</span>
          <span class="info-value"><?php echo htmlspecialchars($full_name); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Username</span>
          <span class="info-value"><?php echo htmlspecialchars($student['UserName']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">First Name</span>
          <span class="info-value"><?php echo htmlspecialchars($student['First_Name'] ?? 'Not set'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Middle Name</span>
          <span class="info-value"><?php echo htmlspecialchars($student['Middle_Name'] ?? 'Not set'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Last Name</span>
          <span class="info-value"><?php echo htmlspecialchars($student['Last_Name'] ?? 'Not set'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Email</span>
          <span class="info-value"><?php echo htmlspecialchars($student['email'] ?? 'Not set'); ?></span>
        </div>
        
        <!-- Edit Profile Button -->
        <div style="margin-top: 20px;">
          <button class="btn outline" onclick="toggleEditForm('editProfileForm')" style="width: 100%;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Edit Profile Information
          </button>
        </div>
        
        <!-- Edit Profile Form (Hidden by default) -->
        <div id="editProfileForm" class="edit-form">
          <form method="POST">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" class="form-control" 
                     value="<?php echo htmlspecialchars($student['First_Name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" class="form-control" 
                     value="<?php echo htmlspecialchars($student['Middle_Name'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" class="form-control" 
                     value="<?php echo htmlspecialchars($student['Last_Name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label>Email Address *</label>
              <input type="email" name="email" class="form-control" 
                     value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
            </div>
            <div class="form-btns">
              <button type="submit" name="update_profile" class="btn primary">Save Changes</button>
              <button type="button" class="btn outline" onclick="toggleEditForm('editProfileForm')">Cancel</button>
            </div>
          </form>
        </div>
        
        <!-- Change Password Button -->
        <div style="margin-top: 15px;">
          <button class="btn outline" onclick="toggleEditForm('changePasswordForm')" style="width: 100%;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M15 7a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h6z" stroke="currentColor" stroke-width="2"/>
              <path d="M12 14v2" stroke="currentColor" stroke-width="2"/>
            </svg>
            Change Password
          </button>
        </div>
        
        <!-- Change Password Form (Hidden by default) -->
        <div id="changePasswordForm" class="edit-form">
          <form method="POST">
            <div class="form-group">
              <label>Current Password *</label>
              <div class="password-wrapper">
                <input type="password" name="current_password" class="form-control" id="current_password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">Show</button>
              </div>
            </div>
            <div class="form-group">
              <label>New Password *</label>
              <div class="password-wrapper">
                <input type="password" name="new_password" class="form-control" id="new_password" 
                       placeholder="At least 8 characters" required onkeyup="checkPasswordStrength()">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">Show</button>
              </div>
              <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                Must be at least 8 characters long
              </div>
              <div class="password-strength">
                <div class="strength-meter" id="strength-meter"></div>
                <div class="strength-label" id="strength-label">Password Strength</div>
              </div>
            </div>
            <div class="form-group">
              <label>Confirm New Password *</label>
              <div class="password-wrapper">
                <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">Show</button>
              </div>
            </div>
            <div class="form-btns">
              <button type="submit" name="change_password" class="btn primary">Change Password</button>
              <button type="button" class="btn outline" onclick="toggleEditForm('changePasswordForm')">Cancel</button>
            </div>
          </form>
        </div>
      </section>

      <!-- Academic Information -->
      <section class="profile-card" aria-labelledby="academic-heading">
        <h2 id="academic-heading">Academic Information</h2>
        <div class="info-row">
          <span class="info-label">Program</span>
          <span class="info-value">BS Information Technology</span>
        </div>
        <div class="info-row">
          <span class="info-label">Year Level</span>
          <span class="info-value"><?php echo $year_text; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Semester</span>
          <span class="info-value"><?php echo $enrollment ? $semester_text : 'Not enrolled'; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Academic Standing</span>
          <span class="info-value">Good Standing</span>
        </div>
        <?php if ($enrollment): ?>
        <div class="info-row">
          <span class="info-label">Enrollment Date</span>
          <span class="info-value"><?php echo $enrollment_date; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Application Status</span>
          <span class="info-value">
            <span class="status-badge status-<?php echo htmlspecialchars($enrollment['status']); ?>">
              <?php echo $enrollment_status; ?>
            </span>
          </span>
        </div>
        <?php endif; ?>
      </section>

      <!-- Current Semester Courses -->
      <section class="profile-card" aria-labelledby="courses-heading">
        <h2 id="courses-heading">
          <?php if ($enrollment): ?>
            Current Semester (<?php echo $year_text . ' - ' . $semester_text; ?>)
          <?php else: ?>
            No Active Enrollment
          <?php endif; ?>
        </h2>
        
        <?php if ($enrollment && !empty($subjects)): ?>
          <ul class="courses-list">
            <?php foreach ($subjects as $subject): ?>
              <li class="course-item">
                <div class="course-code"><?php echo htmlspecialchars($subject); ?></div>
                <div class="course-name">BSIT Subject • Units: 3</div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php elseif ($enrollment && empty($subjects)): ?>
          <div style="text-align: center; padding: 30px; color: var(--muted);">
            No subjects selected for this enrollment.
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 30px; color: var(--muted);">
            You haven't enrolled yet.
            <br>
            <a href="enrollment.php" class="btn primary" style="margin-top: 15px; display: inline-block;">
              Enroll Now →
            </a>
          </div>
        <?php endif; ?>
        
        <div class="buttons-row">
          <a href="enrollment.php" class="btn primary">
            View Enrollment
          </a>
          
          <?php if ($enrollment): ?>
            <a href="download_enrollment.php?id=<?php echo $enrollment['id']; ?>" class="btn primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Download Enrollment PDF
            </a>
            
            
          <?php endif; ?>
        </div>
      </section>

      <!-- Account & Settings -->
      <section class="profile-card" aria-labelledby="account-heading">
        <h2 id="account-heading">Account & Settings</h2>
        <div class="info-row">
          <span class="info-label">Last Login</span>
          <span class="info-value"><?php echo date('F d, Y h:i A'); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Account Created</span>
          <span class="info-value">Active</span>
        </div>
        <div class="info-row">
          <span class="info-label">Email Notifications</span>
          <span class="info-value">Enabled</span>
        </div>
        <div class="info-row">
          <span class="info-label">Portal Language</span>
          <span class="info-value">English</span>
        </div>
      </section>
      
    </div> <!-- End of profile-sections -->
  </main>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div style="display:flex; gap:12px; align-items:center;">
      <img src="images/ascot-logo.png" alt="ASCOT" style="height:42px; width:auto; border-radius:6px; opacity:0.95;" onerror="this.style.display='none'">
      <div>
        <div style="font-weight:700;">Aurora State College of Technology</div>
        <div style="font-size:13px; color:rgba(255,255,255,0.85);">Bachelor of Science in Information Technology</div>
      </div>
    </div>

    <div style="text-align:right; font-size:13px;">
      <div>© 2025 ASCOT — Student Enrollment System</div>
      <div style="color:rgba(255,255,255,0.8); margin-top:6px;">Privacy • Terms • Support</div>
    </div>
  </footer>

  <script>
    // Function to toggle edit form visibility
    function toggleEditForm(formId) {
      const form = document.getElementById(formId);
      form.style.display = form.style.display === 'block' ? 'none' : 'block';
      
      // Close other forms when opening a new one
      const allForms = ['editProfileForm', 'changePasswordForm'];
      allForms.forEach(id => {
        if (id !== formId) {
          document.getElementById(id).style.display = 'none';
        }
      });
    }

    // Toggle password visibility
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      const toggle = document.querySelector(`[onclick="togglePasswordVisibility('${inputId}')"]`);
      
      if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = 'Hide';
      } else {
        input.type = 'password';
        toggle.textContent = 'Show';
      }
    }

    // Check password strength
    function checkPasswordStrength() {
      const password = document.getElementById('new_password').value;
      const strengthMeter = document.getElementById('strength-meter');
      const strengthLabel = document.getElementById('strength-label');
      
      let strength = 0;
      let label = 'Very Weak';
      let color = '#ef4444';
      let width = '0%';
      let className = '';
      
      if (password.length >= 8) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;
      
      switch(strength) {
        case 0:
        case 1:
          label = 'Very Weak';
          color = '#ef4444';
          width = '20%';
          className = 'strength-weak';
          break;
        case 2:
          label = 'Weak';
          color = '#ef4444';
          width = '40%';
          className = 'strength-weak';
          break;
        case 3:
          label = 'Medium';
          color = '#f59e0b';
          width = '60%';
          className = 'strength-medium';
          break;
        case 4:
          label = 'Strong';
          color = '#10b981';
          width = '80%';
          className = 'strength-strong';
          break;
        case 5:
          label = 'Very Strong';
          color = '#10b981';
          width = '100%';
          className = 'strength-strong';
          break;
      }
      
      strengthMeter.innerHTML = `<div class="strength-fill ${className}" style="width: ${width}; background: ${color};"></div>`;
      strengthLabel.textContent = label;
      strengthLabel.style.color = color;
    }

    // Interactive functionality for the profile page
    document.addEventListener('DOMContentLoaded', function() {
      // Simulate dynamic data (in real app, this would come from API)
      function updateLiveData() {
        const subjectCount = <?php echo count($subjects); ?>;
        const subjectElement = document.querySelector('.stat-number:first-child');
        if (subjectElement && subjectCount > 0) {
          subjectElement.textContent = subjectCount;
        }
      }
      
      // Initialize
      updateLiveData();
      
      // Clear password fields when closing form
      const changePasswordForm = document.getElementById('changePasswordForm');
      if (changePasswordForm) {
        const observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'style' && changePasswordForm.style.display === 'none') {
              // Clear password fields
              document.getElementById('current_password').value = '';
              document.getElementById('new_password').value = '';
              document.getElementById('confirm_password').value = '';
              // Reset strength meter
              document.getElementById('strength-meter').innerHTML = '';
              document.getElementById('strength-label').textContent = 'Password Strength';
              document.getElementById('strength-label').style.color = '';
            }
          });
        });
        
        observer.observe(changePasswordForm, { attributes: true });
      }
    });
  </script>
</body>
</html>

<?php
// Close database connections
$stmt->close();
if (isset($enrollment_stmt)) $enrollment_stmt->close();
$conn->close();
?>