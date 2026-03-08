<?php
$conn = mysqli_connect('localhost', 'root', '');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

echo "<h3>Checking databases for 'users' table:</h3>";

$result = mysqli_query($conn, "SHOW DATABASES");
$found = false;

while ($row = mysqli_fetch_array($result)) {
    $dbname = $row[0];
    
    // Skip system databases
    if (in_array($dbname, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'test'])) {
        continue;
    }
    
    mysqli_select_db($conn, $dbname);
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    
    if (mysqli_num_rows($table_check) > 0) {
        echo "<p style='color: green;'><strong>Found users table in database: $dbname</strong></p>";
        
        // Show table structure
        echo "<h4>Table structure of 'users' in $dbname:</h4>";
        $desc = mysqli_query($conn, "DESCRIBE users");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($col = mysqli_fetch_assoc($desc)) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $found = true;
    }
}

if (!$found) {
    echo "<p style='color: red;'>No database found with 'users' table.</p>";
}

mysqli_close($conn);
?>