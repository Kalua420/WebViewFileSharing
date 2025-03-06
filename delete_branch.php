<?php
session_start();
require_once 'db_connection.php';

// Function to delete branch
function deleteBranch($branchId, $conn) {
    // Prepare the SQL query to delete the branch
    $sql = "DELETE FROM `branch` WHERE `branch`.`id` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchId);

    // Execute the query and check for success
    if ($stmt->execute()) {
        $_SESSION['delete_success'] = 'Branch deleted successfully!';
    } else {
        $_SESSION['delete_error'] = 'Failed to delete branch. Please try again.';
    }
}

// Check if branch ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $branchId = $_GET['id'];
    deleteBranch($branchId, $conn); // Call the delete function
} else {
    $_SESSION['delete_error'] = 'No branch ID provided.';
}

header("Location: admin_dashboard.php"); // Redirect back to dashboard after deletion
exit();
?>
