<?php
require_once 'db_connection.php';

// Check if the section parameter is set
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    
    // Based on the section, fetch the corresponding data
    switch ($section) {
        case 'managers':
            $managers = $conn->query("SELECT m.*, b.branch_name FROM manager m LEFT JOIN branch b ON m.bid = b.id");
            while ($manager = $managers->fetch_assoc()) {
                echo "<tr>
                        <td>{$manager['id']}</td>
                        <td>{$manager['username']}</td>
                        <td>{$manager['email']}</td>
                        <td>{$manager['branch_name']}</td>
                        <td>
                            <a href='edit_manager.php?id={$manager['id']}' class='btn-edit'><i class='fas fa-edit'></i></a>
                            <a href='delete_manager.php?id={$manager['id']}' onclick='return confirm(\"Are you sure you want to delete this manager?\");'>
                                <button class='btn-delete'><i class='fas fa-trash'></i></button>
                            </a>
                        </td>
                      </tr>";
            }
            break;

        case 'branches':
            $branches = $conn->query("SELECT b.*, m.username FROM branch b LEFT JOIN manager m ON b.id = m.bid ORDER BY b.id");
            while ($branch = $branches->fetch_assoc()) {
                echo "<tr>
                        <td>{$branch['id']}</td>
                        <td>{$branch['branch_name']}</td>
                        <td>{$branch['state']}</td>
                        <td>{$branch['city']}</td>
                        <td>{$branch['zip_code']}</td>
                        <td>{$branch['username']}</td>
                        <td>{$branch['opening_date']}</td>
                        <td>
                            <a href='edit_branch.php?id={$branch['id']}' class='btn-edit'><i class='fas fa-edit'></i></a>
                            <a href='delete_branch.php?id={$branch['id']}' onclick='return confirm(\"Are you sure you want to delete this branch?\");'>
                                <button class='btn-delete'><i class='fas fa-trash'></i></button>
                            </a>
                        </td>
                      </tr>";
            }
            break;

        case 'users':
            $users = $conn->query("SELECT u.*, b.branch_name FROM users u LEFT JOIN branch b ON u.bid = b.id ORDER BY u.created_at DESC");
            while ($user = $users->fetch_assoc()) {
                echo "<tr>
                        <td>{$user['id']}</td>
                        <td>{$user['email']}</td>
                        <td>{$user['phone']}</td>
                        <td>{$user['branch_name']}</td>
                        <td><span class='status-badge {$user['status']}'>{$user['status']}</span></td>
                        <td>{$user['created_at']}</td>
                      </tr>";
            }
            break;

        case 'logs':
            $logs = $conn->query("SELECT l.*, s.id as sender_id, r.id as receiver_id FROM logs l LEFT JOIN users s ON l.sender_id = s.id LEFT JOIN users r ON l.receiver_id = r.id ORDER BY l.timestamp DESC");
            while ($log = $logs->fetch_assoc()) {
                echo "<tr>
                        <td style='color: red;'>{$log['log_id']}</td>
                        <td>{$log['sender_id']} ({$log['sender_email']})</td>
                        <td>{$log['receiver_id']} ({$log['receiver_email']})</td>
                        <td style='color: lightgreen;'>{$log['source_mac']}</td>
                        <td style='color: #0ef;'>{$log['destination_mac']}</td>
                        <td>{$log['filename']}</td>
                        <td>{$log['timestamp']}</td>
                      </tr>";
            }
            break;
    }
}
?>
