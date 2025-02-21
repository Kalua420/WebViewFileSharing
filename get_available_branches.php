<?php
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM branch WHERE id NOT IN (SELECT DISTINCT bid FROM manager WHERE bid IS NOT NULL) ORDER BY branch_name");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $manager = $result->fetch_assoc();
    
    echo json_encode($manager);
    
    $stmt->close();
}