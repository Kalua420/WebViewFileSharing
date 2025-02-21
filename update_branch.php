<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_GET['id'];
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $state = $conn->real_escape_string($_POST['state']);
    $city = $conn->real_escape_string($_POST['city']);
    $zip_code = $conn->real_escape_string($_POST['zip_code']);
    $opening_date = $conn->real_escape_string($_POST['opening_date']);

    $sql = "UPDATE branch SET branch_name=?, state=?, city=?, zip_code=?, opening_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $branch_name, $state, $city, $zip_code, $opening_date, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Branch updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating branch: " . $conn->error;
    }

    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}