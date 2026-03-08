<?php
session_start();
$conn = new mysqli("localhost", "root", "", "kurt");
$username = $_SESSION['username'] ?? 'test';

echo "<h2>Debug: Checking Enrollments</h2>";
echo "<p>Current Username: " . $username . "</p>";

// Check all enrollments
$sql = "SELECT id, fullname, email, username, subjects FROM enrollments";
$result = $conn->query($sql);

echo "<h3>All Enrollments in Database:</h3>";
echo "<table border='1'><tr><th>ID</th><th>Fullname</th><th>Email</th><th>Username</th><th>Subjects</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['fullname'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . ($row['username'] ?: 'NULL') . "</td>";
    echo "<td>" . substr($row['subjects'], 0, 50) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific user's enrollments
$user_sql = "SELECT * FROM enrollments WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();

echo "<h3>Enrollments for user '$username':</h3>";
if ($user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "<p>No enrollments found for this username.</p>";
}
?>