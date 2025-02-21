<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_GET['id'];
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $bid = (int)$_POST['bid'];

    $sql = "UPDATE manager SET username=?, email=?, bid=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $username, $email, $bid, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Manager updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating manager: " . $conn->error;
    }

    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}