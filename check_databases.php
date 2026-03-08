<?php
$conn = mysqli_connect('localhost', 'root', '');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$result = mysqli_query($conn, "SHOW DATABASES");
echo "Available databases:<br>";
while ($row = mysqli_fetch_array($result)) {
    echo "- " . $row[0] . "<br>";
}

mysqli_close($conn);
?>