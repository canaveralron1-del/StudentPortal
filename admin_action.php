<?php
require_once __DIR__ . '/db.php';
session_start();

// Debug - log the POST data
error_log("POST data: " . print_r($_POST, true));

// Check if admin is logged in (add your authentication logic here)
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: admin_login.php');
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Not a POST request");
    header('Location: admin.php');
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['action'])) {
    error_log("Missing id or action parameter");
    error_log("ID: " . ($_POST['id'] ?? 'not set'));
    error_log("Action: " . ($_POST['action'] ?? 'not set'));
    header('Location: admin.php');
    exit;
}

$id = (int)$_POST['id'];
$action = $_POST['action'];
$return_status = isset($_POST['return_status']) ? $_POST['return_status'] : '';

error_log("Processing: id=$id, action=$action, return_status=$return_status");

try {
    if ($action === 'delete') {
    // Delete the enrollment
    error_log("Attempting to delete enrollment ID: $id");
    
    try {
        // First, delete any related messages
        $stmt1 = $mysqli->prepare("DELETE FROM messages WHERE enrollment_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        error_log("Deleted related messages for enrollment ID: $id");
        
        // Then delete the enrollment
        $stmt2 = $mysqli->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        
        error_log("Delete affected rows: " . $stmt2->affected_rows);
        
        if ($stmt2->affected_rows > 0) {
            $_SESSION['message'] = "Enrollment deleted successfully.";
        } else {
            $_SESSION['error'] = "No enrollment found with that ID.";
        }
        $stmt2->close();
    } catch (mysqli_sql_exception $e) {
        error_log("Error during delete: " . $e->getMessage());
        $_SESSION['error'] = "Cannot delete enrollment: " . $e->getMessage() . ". It may have related records.";
    }
}

    if ($action === 'accept') {
        if ($stmt = $mysqli->prepare('UPDATE enrollments SET status = ? WHERE id = ?')) {
            $s = 'accepted';
            $stmt->bind_param('si', $s, $id);
            $stmt->execute();
            $stmt->close();
        }

        // create messages table if needed and insert message
        if ($enrollment_exists && $email) {
            $mysqli->query("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enrollment_id INT,
                email VARCHAR(255),
                subject VARCHAR(255),
                body TEXT,
                status VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            if ($ins = $mysqli->prepare('INSERT INTO messages (enrollment_id, email, subject, body, status) VALUES (?, ?, ?, ?, ?)')) {
                $subject = 'Enrollment Accepted';
                $body = "Hello " . $fullname . ",\n\nYour enrollment has been accepted.\n\nRegards.";
                $st = 'accepted';
                $ins->bind_param('issss', $id, $email, $subject, $body, $st);
                $ins->execute();
                $ins->close();
            }
        }

    } elseif ($action === 'reject') {
        if ($stmt = $mysqli->prepare('UPDATE enrollments SET status = ? WHERE id = ?')) {
            $s = 'rejected';
            $stmt->bind_param('si', $s, $id);
            $stmt->execute();
            $stmt->close();
        }

        if ($enrollment_exists && $email) {
            $mysqli->query("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enrollment_id INT,
                email VARCHAR(255),
                subject VARCHAR(255),
                body TEXT,
                status VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            if ($ins = $mysqli->prepare('INSERT INTO messages (enrollment_id, email, subject, body, status) VALUES (?, ?, ?, ?, ?)')) {
                $subject = 'Enrollment Rejected';
                $body = "Hello " . $fullname . ",\n\nWe're sorry to inform you that your enrollment has been rejected.\n\nRegards.";
                $st = 'rejected';
                $ins->bind_param('issss', $id, $email, $subject, $body, $st);
                $ins->execute();
                $ins->close();
            }
        }

    } elseif ($action === 'delete') {
        if ($stmt = $mysqli->prepare('DELETE FROM enrollments WHERE id = ?')) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        // optionally delete related messages
        if ($enrollment_exists) {
            if ($stmt = $mysqli->prepare('DELETE FROM messages WHERE enrollment_id = ?')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    // log error and set admin notice for visibility
    error_log("admin_action error: " . $e->getMessage());
    $_SESSION['admin_notice'] = 'Database error while processing action: ' . $e->getMessage();
}

$redirect = 'admin.php';
if ($return_status !== '') {
    $redirect .= '?status=' . urlencode($return_status);
}
header('Location: ' . $redirect);
exit;

?>
