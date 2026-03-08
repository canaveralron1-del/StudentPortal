<?php
require_once __DIR__ . '/db.php';
// Upgrade `Password` column to VARCHAR(255) so password_hash() fits
header('Content-Type: text/plain; charset=utf-8');
try {
    $sql = "ALTER TABLE `student` MODIFY `Password` VARCHAR(255) NOT NULL";
    if ($mysqli->query($sql) === TRUE) {
        echo "Success: password column altered to VARCHAR(255)\n";
    } else {
        echo "Error altering column: " . $mysqli->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\nYou can now run migrate_passwords.php to re-hash existing plaintext passwords.\n";

?>
