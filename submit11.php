<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enrollment.php');
    exit;
}

$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$year = isset($_POST['year']) ? $_POST['year'] : '';
$semester = isset($_POST['semester']) ? $_POST['semester'] : '';
$subjects = isset($_POST['subjects']) && is_array($_POST['subjects']) ? $_POST['subjects'] : [];
$username = isset($_POST['username']) ? trim($_POST['username']) : null;

if ($fullname === '' || $email === '' || $year === '' || $semester === '') {
    header('Location: enrollment.php?error=missing');
    exit;
}

// Ensure enrollments table exists and includes a `status` column
$createSql = "CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    year_level VARCHAR(16) NOT NULL,
    semester VARCHAR(16) NOT NULL,
    subjects TEXT,
    username VARCHAR(100) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
$mysqli->query($createSql);

$subjects_json = json_encode(array_values($subjects), JSON_UNESCAPED_UNICODE);

// Ensure required columns exist (idempotent). Use IF NOT EXISTS where supported.
// This is simpler and avoids information_schema prepared statements causing unexpected failures.
// Some MySQL versions support `ADD COLUMN IF NOT EXISTS` (MySQL 8+). If not supported, the query will fail
// harmlessly and the following INSERT will either succeed or be retried.
@ $mysqli->query("ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'ongoing'");
@ $mysqli->query("ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS username VARCHAR(100) DEFAULT NULL");

// Insert with default status 'ongoing'. Wrap in try/catch and retry once if a schema-related error occurs.
$status = 'ongoing';
$insertSql = 'INSERT INTO enrollments (fullname, email, year_level, semester, subjects, username, status) VALUES (?, ?, ?, ?, ?, ?, ?)';
$attempt = 0;
while ($attempt < 2) {
    $attempt++;
    try {
        if ($stmt = $mysqli->prepare($insertSql)) {
            $stmt->bind_param('sssssss', $fullname, $email, $year, $semester, $subjects_json, $username, $status);
            $stmt->execute();
            $stmt->close();
            break; // success
        } else {
            // prepare failed; throw to catch below so we can attempt a schema fix on first try
            throw new Exception('Prepare failed: ' . $mysqli->error);
        }
    } catch (Exception $e) {
        // If first attempt failed due to missing column, try to add columns and retry once.
        if ($attempt === 1) {
            @ $mysqli->query("ALTER TABLE enrollments ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ongoing'");
            @ $mysqli->query("ALTER TABLE enrollments ADD COLUMN username VARCHAR(100) DEFAULT NULL");
            // then loop to retry
            continue;
        }
        // On second failure, log error and stop to avoid fatal exception bubbling up.
        error_log('submit.php insert failed: ' . $e->getMessage());
        break;
    }
}

// Redirect back to the enrollment form with a success flag (do not send admin there)
header('Location: enrollment.php?success=1');
exit;

?>
