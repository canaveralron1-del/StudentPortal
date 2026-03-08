<?php
// Database configuration - update credentials if different for your XAMPP setup
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'kurt';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Database connection failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit;
}
$mysqli->set_charset('utf8mb4');

// helper to close connection on script end
register_shutdown_function(function() use ($mysqli) {
    if ($mysqli) $mysqli->close();
});

?>
