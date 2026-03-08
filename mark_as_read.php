<?php
// mark_as_read.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['UserName']) && !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'kurt';
$username = 'root'; // Change this
$password = ''; // Change this

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mark message as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = :id");
    $stmt->execute([':id' => $_POST['message_id']]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>