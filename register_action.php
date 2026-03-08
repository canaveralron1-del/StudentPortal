<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: new.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kurt";

// Collect form data
$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$user_password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Store form data in session in case of error
$_SESSION['form_data'] = $_POST;

// Basic validation
if (empty($first_name) || empty($last_name) || empty($user_password) || empty($email)) {
    $_SESSION['register_error'] = "First name, last name, email, and password are required.";
    header("Location: new.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Please enter a valid email address.";
    header("Location: new.php");
    exit();
}

if ($user_password !== $confirm_password) {
    $_SESSION['register_error'] = "Passwords do not match.";
    header("Location: new.php");
    exit();
}

// Generate username from first name + last name
$generated_username = strtolower($first_name . $last_name . rand(100, 999));

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ========== STUDENT ID GENERATION FUNCTION ==========
function generateStudentID($conn) {
    $year = date('Y');
    $prefix = $year . "-BSIT-"; // Format: YYYY-BSIT-XXXX
    
    // Get the next available number
    $sql = "SELECT MAX(Student_ID) as last_id FROM student 
            WHERE Student_ID LIKE '$prefix%' 
            AND Student_ID REGEXP '^[0-9]{4}-BSIT-[0-9]{4}$'";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $last_id = $row['last_id'];
        if ($last_id) {
            $last_num = intval(substr($last_id, -4)); // Get last 4 digits
            $next_num = $last_num + 1;
        } else {
            $next_num = 1;
        }
    } else {
        $next_num = 1;
    }
    
    // Format: 2025-BSIT-0001
    $formatted_num = str_pad($next_num, 4, '0', STR_PAD_LEFT);
    return $prefix . $formatted_num;
}
// ========== END FUNCTION ==========

// ========== CHECK FOR DUPLICATE EMAIL ==========
$check_email_sql = "SELECT UserName FROM student WHERE email = ?";
$check_email_stmt = $conn->prepare($check_email_sql);
$check_email_stmt->bind_param("s", $email);
$check_email_stmt->execute();
$email_result = $check_email_stmt->get_result();

if ($email_result->num_rows > 0) {
    $_SESSION['register_error'] = "Email '$email' is already registered. Please use a different email or login.";
    $check_email_stmt->close();
    $conn->close();
    header("Location: new.php");
    exit();
}
$check_email_stmt->close();
// ========== END EMAIL CHECK ==========

// ========== STUDENT ID HANDLING ==========
// If student provided an ID, check for duplicates
if (!empty($student_id)) {
    $check_id_sql = "SELECT UserName FROM student WHERE Student_ID = ?";
    $check_id_stmt = $conn->prepare($check_id_sql);
    $check_id_stmt->bind_param("s", $student_id);
    $check_id_stmt->execute();
    $id_result = $check_id_stmt->get_result();
    
    if ($id_result->num_rows > 0) {
        $_SESSION['register_error'] = "Student ID '$student_id' is already registered.";
        $check_id_stmt->close();
        $conn->close();
        header("Location: new.php");
        exit();
    }
    $check_id_stmt->close();
    $final_student_id = $student_id; // Use the provided ID
} else {
    // ========== AUTO-GENERATE STUDENT ID ==========
    // Generate the Student ID
    $final_student_id = generateStudentID($conn);
    
    // Double-check it doesn't exist (just in case)
    $check_sql = "SELECT UserName FROM student WHERE Student_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $final_student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // If by some miracle it exists, generate another
    if ($check_result->num_rows > 0) {
        $final_student_id = generateStudentID($conn);
    }
    $check_stmt->close();
}
// ========== END STUDENT ID HANDLING ==========

// 🔥 CHECK: Check if username already exists
$check_user_sql = "SELECT UserName FROM student WHERE UserName = ?";
$check_user_stmt = $conn->prepare($check_user_sql);
$check_user_stmt->bind_param("s", $generated_username);
$check_user_stmt->execute();
$user_result = $check_user_stmt->get_result();

if ($user_result->num_rows > 0) {
    // If username exists, add more random numbers
    $generated_username = strtolower($first_name . $last_name . rand(1000, 9999));
    
    // Check again
    $check_user_stmt->bind_param("s", $generated_username);
    $check_user_stmt->execute();
    $user_result = $check_user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $_SESSION['register_error'] = "Username generation failed. Please try again.";
        $check_user_stmt->close();
        $conn->close();
        header("Location: new.php");
        exit();
    }
}
$check_user_stmt->close();

// Hash password
$hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

// Insert into database - NOW INCLUDING EMAIL
$sql = "INSERT INTO student (UserName, Password, First_Name, Middle_Name, Last_Name, email, Student_ID) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $generated_username, $hashed_password, $first_name, $middle_name, $last_name, $email, $final_student_id);

if ($stmt->execute()) {
    // Determine if ID was auto-generated or user-provided
    $id_source = empty($student_id) ? "auto-generated" : "provided";
    
    $_SESSION['register_success'] = "
        ✅ Account created successfully!<br><br>
        <strong>Your Login Details:</strong><br>
        <strong>Username:</strong> <span style='background:#d1fae5; padding:4px 8px; border-radius:4px; font-family: monospace;'>$generated_username</span><br>
        <strong>Email:</strong> $email<br>
        <strong>Password:</strong> What you entered<br>
        <strong>Student ID:</strong> <span style='background:#dbeafe; padding:4px 8px; border-radius:4px; font-family: monospace;'>$final_student_id</span><br>
        <small>(This ID was $id_source)</small><br><br>
        <div style='background:#f0f9ff; padding:12px; border-radius:8px; border-left:4px solid #0ea5a4; margin-top:10px;'>
        <strong>⚠️ Important:</strong> Save your username and Student ID!<br>
        <strong>To Login:</strong> Go to login page and enter your username and password.
        </div>
    ";
    unset($_SESSION['form_data']);
} else {
    $_SESSION['register_error'] = "Registration failed: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: new.php");
exit();
?>