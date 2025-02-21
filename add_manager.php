<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $bid = (int)$_POST['bid'];

    $stmt = $conn->prepare("INSERT INTO manager (username, email, password, bid) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password, $bid);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Manager added successfully!";
    } else {
        $_SESSION['error'] = "Error adding manager: " . $conn->error;
    }

    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}