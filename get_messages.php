<?php
// get_messages.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['UserName']) && !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Please log in to view messages']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'kurt';
$username = 'root'; // Change this to your MySQL username
$password = ''; // Change this to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user email from session
    $user_email = $_SESSION['email'] ?? $_SESSION['UserName'];
    
    // Get latest enrollment for the user
    $enrollment_stmt = $pdo->prepare("
        SELECT * FROM enrollments 
        WHERE email = :email OR username = :username 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $enrollment_stmt->execute([
        ':email' => $user_email,
        ':username' => $_SESSION['UserName'] ?? ''
    ]);
    
    $enrollment = $enrollment_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get messages for the user
    $messages_stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE email = :email 
        ORDER BY created_at DESC
    ");
    $messages_stmt->execute([':email' => $user_email]);
    
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no messages found with email, try enrollment_id
    if (empty($messages) && $enrollment) {
        $messages_stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE enrollment_id = :enrollment_id 
            ORDER BY created_at DESC
        ");
        $messages_stmt->execute([':enrollment_id' => $enrollment['id']]);
        $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'enrollment' => $enrollment,
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>