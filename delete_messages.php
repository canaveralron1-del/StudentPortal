<?php
require_once __DIR__ . '/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [ (int) $_POST['id'] ];
}
$sessionUser = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';

// Early exit if nothing selected - do this before any DB queries to avoid unintended SQL errors
if (count($ids) === 0) {
    $_SESSION['delete_notice'] = 'No messages selected.';
    header('Location: messages.php');
    exit;
}

try {
    if ($sessionUser === '') {
        // Do not allow deletion without login for now
        $_SESSION['delete_notice'] = 'You must be logged in to delete messages.';
        header('Location: messages.php');
        exit;
    }

    // Ensure `username` column exists on enrollments to avoid SQL errors on older schemas
    @ $mysqli->query("ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS username VARCHAR(100) DEFAULT NULL");

    // Build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // We will delete only messages whose enrollment_id belongs to the logged-in user
    // Prepare statement to select matching message ids first (safety)
    $selectSql = "SELECT m.id FROM messages m INNER JOIN enrollments e ON m.enrollment_id = e.id WHERE e.username = ? AND m.id IN ($placeholders)";
    $stmt = $mysqli->prepare($selectSql);
    if (! $stmt) {
        // If prepare fails (older MySQL or other schema problem), abort with a helpful message
        throw new Exception('Prepare failed: ' . $mysqli->error);
    }

    // bind params (1 string + N ints)
    $types = 's' . str_repeat('i', count($ids));
    $params = array_merge([$sessionUser], $ids);
    // call_user_func_array requires references
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $res = $stmt->get_result();
    $deletable = [];
    while ($r = $res->fetch_assoc()) {
        $deletable[] = (int)$r['id'];
    }
    $stmt->close();

    if (count($deletable) === 0) {
        $_SESSION['delete_notice'] = 'No messages matched for deletion.';
        header('Location: messages.php');
        exit;
    }

    // Delete the matched messages
    $ph = implode(',', array_fill(0, count($deletable), '?'));
    $delSql = "DELETE FROM messages WHERE id IN ($ph)";
    $dstmt = $mysqli->prepare($delSql);
    if (! $dstmt) throw new Exception('Prepare delete failed: ' . $mysqli->error);
    $dtypes = str_repeat('i', count($deletable));
    $dbind = [];
    $dbind[] = $dtypes;
    for ($i = 0; $i < count($deletable); $i++) {
        $name = 'd' . $i;
        $$name = $deletable[$i];
        $dbind[] = &$$name;
    }
    call_user_func_array([$dstmt, 'bind_param'], $dbind);
    $dstmt->execute();
    $dstmt->close();

    $_SESSION['delete_notice'] = count($deletable) . ' message(s) deleted.';
} catch (Exception $e) {
    error_log('delete_messages error: ' . $e->getMessage());
    $_SESSION['delete_notice'] = 'Failed to delete messages.';
}

header('Location: messages.php');
exit;
?>