<?php
require_once __DIR__ . '/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: new.php');
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    $_SESSION['register_error'] = 'Please provide a username and password.';
    header('Location: new.php');
    exit;
}

// Basic username validation
if (strlen($username) > 20) {
    $_SESSION['register_error'] = 'Username must be 20 characters or fewer.';
    header('Location: new.php');
    exit;
}

// Check if user exists
if ($stmt = $mysqli->prepare('SELECT UserName FROM student WHERE UserName = ?')) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = 'Username already exists.';
        $stmt->close();
        header('Location: new.php');
        exit;
    }
    $stmt->close();
}

// Insert user with hashed password
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($stmt = $mysqli->prepare('INSERT INTO student (UserName, Password) VALUES (?, ?)')) {
    $stmt->bind_param('ss', $username, $hash);
    if ($stmt->execute()) {
        $_SESSION['register_success'] = 'Account created — please log in.';
        $stmt->close();
        header('Location: NewL.php');
        exit;
    }
    $stmt->close();
}

// fallback
$_SESSION['register_error'] = 'Could not create account. Try again.';
header('Location: new.php');
exit;

?>
