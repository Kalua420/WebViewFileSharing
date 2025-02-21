<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_GET['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First update any managers associated with this branch
        $stmt = $conn->prepare("UPDATE manager SET bid = NULL WHERE bid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Then delete the branch
        $stmt = $conn->prepare("DELETE FROM branch WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $stmt->close();
    exit();
}