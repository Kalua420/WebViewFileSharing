<?php
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM branch WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    
    echo json_encode($branch);
    
    $stmt->close();
} else if (isset($_GET['unassigned_only']) && $_GET['unassigned_only'] === 'true') {
    // Query to get branches that don't have any managers assigned
    $query = "SELECT * FROM branch 
              WHERE id NOT IN (
                  SELECT DISTINCT bid FROM manager 
                  WHERE bid IS NOT NULL
              )
              ORDER BY branch_name";
              
    $result = $conn->query($query);
    $branches = array();
    
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    echo json_encode($branches);
} else {
    // Get all branches
    $result = $conn->query("SELECT * FROM branch ORDER BY branch_name");
    $branches = array();
    
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    echo json_encode($branches);
}

$conn->close();
?>