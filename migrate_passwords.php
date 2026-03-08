<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

$updated = 0;
if ($res = $mysqli->query('SELECT UserName, Password FROM student')) {
    while ($row = $res->fetch_assoc()) {
        $user = $row['UserName'];
        $pw = $row['Password'];
        // if not a hash
        if (password_get_info($pw)['algo'] === 0) {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            if ($stmt = $mysqli->prepare('UPDATE student SET Password = ? WHERE UserName = ?')) {
                $stmt->bind_param('ss', $hash, $user);
                if ($stmt->execute()) $updated++;
                $stmt->close();
            }
        }
    }
    $res->free();
    echo "Migration complete. Passwords updated: $updated\n";
} else {
    echo "Error reading student table: " . $mysqli->error . "\n";
}

?>
