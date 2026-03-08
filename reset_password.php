<?php
require_once __DIR__ . '/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';

if ($username === '' || $password === '' || $confirm === '') {
    $_SESSION['reset_error'] = 'Please fill out all fields.';
    header('Location: forgot_password.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['reset_error'] = 'Passwords do not match.';
    header('Location: forgot_password.php');
    exit;
}

// Basic password strength check (minimum length)
if (strlen($password) < 6) {
    $_SESSION['reset_error'] = 'Password must be at least 6 characters.';
    header('Location: forgot_password.php');
    exit;
}

// Check that the user exists
if ($stmt = $mysqli->prepare('SELECT UserName FROM student WHERE UserName = ?')) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $_SESSION['reset_error'] = 'Username not found.';
        $stmt->close();
        header('Location: forgot_password.php');
        exit;
    }
    $stmt->close();
} else {
    error_log('reset_password: failed prepare select: ' . $mysqli->error);
    $_SESSION['reset_error'] = 'Server error. Try again later.';
    header('Location: forgot_password.php');
    exit;
}

// Update password (hash)
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($stmt = $mysqli->prepare('UPDATE student SET Password = ? WHERE UserName = ?')) {
    $stmt->bind_param('ss', $hash, $username);
    if (! $stmt->execute()) {
        error_log('reset_password: update failed: ' . $stmt->error);
        $_SESSION['reset_error'] = 'Could not update password. Try again.';
        $stmt->close();
        header('Location: forgot_password.php');
        exit;
    }
    $stmt->close();
} else {
    error_log('reset_password: failed prepare update: ' . $mysqli->error);
    $_SESSION['reset_error'] = 'Server error. Try again later.';
    header('Location: forgot_password.php');
    exit;
}

// Success — redirect to the login page requested by the user
header('Location: logIn.html');
exit;
?>