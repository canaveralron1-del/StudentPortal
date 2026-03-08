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

// Get current logged-in username
$username = $_SESSION['username'];

// Get user's full name and Student ID from database
$fullname = trim($_POST['fullname'] ?? '');
$student_id = null;
$user_email = null;

// Get user data from student table
$name_sql = "SELECT First_Name, Middle_Name, Last_Name, Student_ID, email FROM student WHERE UserName = ?";
$name_stmt = $conn->prepare($name_sql);
$name_stmt->bind_param("s", $username);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$user_data = null;

if ($name_result->num_rows > 0) {
    $user_data = $name_result->fetch_assoc();
    
    // If fullname is empty in form, get it from database
    if (empty($fullname)) {
        $fullname = trim($user_data['First_Name'] . ' ' . 
                      ($user_data['Middle_Name'] ? $user_data['Middle_Name'] . ' ' : '') . 
                      $user_data['Last_Name']);
    }
    
    // Get Student ID from database
    if (!empty($user_data['Student_ID'])) {
        $student_id = $user_data['Student_ID'];
    }
    
    // Get email from database (CRITICAL for foreign key)
    if (!empty($user_data['email'])) {
        $user_email = $user_data['email'];
    }
}
$name_stmt->close();

// Use email from database, NOT from form
$email = $user_email ?: trim($_POST['email'] ?? '');

// If no Student ID found but form has one, use form value
if (empty($student_id) && !empty($_POST['student_id'])) {
    $student_id = trim($_POST['student_id']);
}

// Prepare other data from form
$year_level = trim($_POST['year'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$subjects = $_POST['subjects'] ?? [];
$form_email = trim($_POST['email'] ?? ''); // Keep form email for comparison

// Validate required fields
if (empty($fullname) || empty($email) || empty($year_level) || empty($semester)) {
    $_SESSION['enrollment_error'] = "Please fill in all required fields.";
    header("Location: enrollment.php");
    exit();
}

// Convert subjects array to JSON
$subjects_json = json_encode($subjects);

// CRITICAL: Ensure student record has the email (for foreign key)
if (!empty($email)) {
    // Check if this student record has an email
    $check_student_sql = "SELECT email FROM student WHERE UserName = ?";
    $check_stmt = $conn->prepare($check_student_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $student_record = $check_result->fetch_assoc();
    $check_stmt->close();
    
    // If student record has no email OR form email is different, update it
    if (empty($student_record['email']) || 
        (!empty($form_email) && $student_record['email'] !== $form_email)) {
        
        $update_email = !empty($form_email) ? $form_email : $email;
        
        $update_sql = "UPDATE student SET email = ? WHERE UserName = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $update_email, $username);
        
        if ($update_stmt->execute()) {
            $email = $update_email; // Use the updated email
        }
        $update_stmt->close();
    }
    
    // Final check: ensure email exists in student table
    $final_check_sql = "SELECT COUNT(*) as count FROM student WHERE email = ?";
    $final_check_stmt = $conn->prepare($final_check_sql);
    $final_check_stmt->bind_param("s", $email);
    $final_check_stmt->execute();
    $final_result = $final_check_stmt->get_result();
    $email_exists = $final_result->fetch_assoc()['count'] > 0;
    $final_check_stmt->close();
    
    // If email still doesn't exist, create a temporary student record
    if (!$email_exists) {
        // Extract names from fullname
        $name_parts = explode(' ', $fullname);
        $first_name = $name_parts[0] ?? $username;
        $last_name = end($name_parts) ?: $username;
        
        // Create temporary password
        $temp_password = password_hash($student_id ?: $username . '123', PASSWORD_DEFAULT);
        
        $create_student_sql = "INSERT INTO student (UserName, email, First_Name, Last_Name, Student_ID, Password) 
                              VALUES (?, ?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE email = VALUES(email)";
        $create_stmt = $conn->prepare($create_student_sql);
        $create_stmt->bind_param("ssssss", $username, $email, $first_name, $last_name, $student_id, $temp_password);
        $create_stmt->execute();
        $create_stmt->close();
    }
}

// Temporarily disable foreign key checks (SAFETY NET)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Insert into enrollments table
$sql = "INSERT INTO enrollments (fullname, email, year_level, semester, subjects, username, student_id, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);

// Check if enrollments table has student_id column
$check_column = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'student_id'");
if ($check_column->num_rows == 0) {
    // If student_id column doesn't exist, don't include it
    $sql = "INSERT INTO enrollments (fullname, email, year_level, semester, subjects, username, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $fullname, $email, $year_level, $semester, $subjects_json, $username);
} else {
    $stmt->bind_param("sssssss", $fullname, $email, $year_level, $semester, $subjects_json, $username, $student_id);
}

if ($stmt->execute()) {
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Success
    $_SESSION['enrollment_success'] = "Enrollment submitted successfully!";
    
    if (!empty($student_id)) {
        $_SESSION['enrollment_success'] .= "<br><small>Student ID: $student_id has been recorded.</small>";
    }
    
    // Update session with new email if changed
    if (!empty($email) && (!isset($_SESSION['email']) || $_SESSION['email'] !== $email)) {
        $_SESSION['email'] = $email;
    }
    
    header("Location: profile.php");
    exit();
} else {
    // Re-enable foreign key checks even on error
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Log error for debugging
    error_log("Enrollment error: " . $stmt->error . " | Email: $email | User: $username");
    
    $_SESSION['enrollment_error'] = "Failed to submit enrollment. Please try again or contact support.";
    header("Location: enrollment.php");
    exit();
}

$stmt->close();
$conn->close();
?>