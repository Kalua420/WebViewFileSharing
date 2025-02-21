<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define your admin username and password (for example)
$admin_username = "admin";  // Change this to your desired username
$admin_password = "admin123";  // Change this to your desired password

// Hash the password using PASSWORD_BCRYPT
$hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);

// Prepare the SQL query to insert the new admin
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $admin_username, $hashed_password);

// Execute the query
if ($stmt->execute()) {
    echo "Admin added successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
