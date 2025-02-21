<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['manager_id'])) {
   header("Location: manager_login.php");
   exit();
}

$manager_id = $_SESSION['manager_id'];
$manager_query = "SELECT m.*, b.branch_name 
                 FROM manager m 
                 LEFT JOIN branch b ON m.bid = b.id 
                 WHERE m.id = '$manager_id'";
$manager_result = $conn->query($manager_query);
$manager = $manager_result->fetch_assoc();

if (isset($_FILES['profile_pic'])) {
   $target_dir = "uploads/";
   $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
   $new_filename = "manager_" . $manager_id . "." . $file_extension;
   $target_file = $target_dir . $new_filename;
   
   if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
       $sql = "UPDATE manager SET profile_pic = '$new_filename' WHERE id = '$manager_id'";
       $conn->query($sql);
       header("Location: manager_dashboard.php");
       exit();
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
   $user_id = $conn->real_escape_string($_POST['user_id']);
   $status = $conn->real_escape_string($_POST['status']);
   
   $sql = "UPDATE users SET status = '$status' WHERE id = '$user_id'";
   $conn->query($sql);
   header("Location: manager_dashboard.php");
   exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'users';
$manager_branch_id = $manager['bid'];
// Modified SQL query to join with branch table
$sql = "SELECT u.*, b.branch_name FROM users u LEFT JOIN branch b ON u.bid = b.id WHERE u.bid = $manager_branch_id ORDER BY u.created_at DESC";
$result = $conn->query($sql);
$users = [];
while ($row = $result->fetch_assoc()) {
   $users[] = $row;
}

// Updated logs query to use manager's branch ID
$logs_query = "SELECT l.*, 
               sender.email as sender_email,
               receiver.email as receiver_email
               FROM logs l 
               JOIN users sender ON l.sender_id = sender.id 
               LEFT JOIN users receiver ON l.receiver_id = receiver.id
               WHERE sender.bid = '$manager_branch_id' 
               OR receiver.bid = '$manager_branch_id'
               ORDER BY l.timestamp DESC";
$logs_result = $conn->query($logs_query);
$logs = [];
while ($row = $logs_result->fetch_assoc()) {
   $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Manager Dashboard</title>
   <style>
       body { 
           font-family: Arial; 
           background: #f4f6f8; 
           margin: 0; 
           padding: 20px; 
       }
       .container {
           max-width: 1200px;
           margin: 0 auto;
           background: white;
           padding: 20px;
           border-radius: 8px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       .profile-section {
           display: flex;
           align-items: center;
           gap: 20px;
           padding: 20px;
           background: #f8f9fa;
           border-radius: 8px;
           margin-bottom: 20px;
       }
       .profile-pic {
           width: 100px;
           height: 100px;
           border-radius: 50%;
           object-fit: cover;
           border: 3px solid #fff;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       .profile-info h3 {
           margin: 0;
           color: #333;
       }
       .profile-info p {
           margin: 5px 0;
           color: #666;
       }
       .upload-form {
           margin-top: 10px;
       }
       .upload-btn {
           background: #007bff;
           color: white;
           padding: 6px 12px;
           border: none;
           border-radius: 4px;
           cursor: pointer;
       }
       table {
           width: 100%;
           border-collapse: collapse;
           margin-top: 20px;
       }
       th, td {
           padding: 12px;
           text-align: left;
           border-bottom: 1px solid #ddd;
           max-width: 200px;
           overflow: hidden;
           text-overflow: ellipsis;
           white-space: nowrap;
       }
       .status-pending { color: orange; }
       .status-approved { color: green; }
       .status-rejected { color: red; }
       .action-btn {
           padding: 6px 12px;
           border: none;
           border-radius: 4px;
           cursor: pointer;
           margin-right: 5px;
       }
       .approve-btn { background: #28a745; color: white; }
       .reject-btn { background: #dc3545; color: white; }
       .header {
           display: flex;
           justify-content: space-between;
           align-items: center;
           margin-bottom: 20px;
       }
       .logout-btn {
           padding: 8px 16px;
           background: #dc3545;
           color: white;
           text-decoration: none;
           border-radius: 4px;
       }
       .tab-btn {
           padding: 8px 16px;
           background: #007bff;
           color: white;
           text-decoration: none;
           border-radius: 4px;
           margin-right: 10px;
       }
       .tab-btn.active {
           background: #0056b3;
       }
   </style>
</head>
<body>
   <div class="container">
       <div class="header">
           <h2>Manager Dashboard</h2>
           <div>
               <a href="?view=users" class="tab-btn <?php echo $view === 'users' ? 'active' : ''; ?>">Users</a>
               <a href="?view=logs" class="tab-btn <?php echo $view === 'logs' ? 'active' : ''; ?>">Logs</a>
               <a href="logout.php" class="logout-btn">Logout</a>
           </div>
       </div>

       <div class="profile-section">
           <img src="<?php echo isset($manager['profile_pic']) ? 'uploads/' . $manager['profile_pic'] : 'uploads/default.png'; ?>" 
                alt="Profile Picture" 
                class="profile-pic">
           <div class="profile-info">
               <h3><?php echo htmlspecialchars($manager['username']); ?></h3>
               <p>Email: <?php echo htmlspecialchars($manager['email']); ?></p>
               <p>Branch: <?php echo htmlspecialchars($manager['branch_name']); ?></p>
               <form method="POST" enctype="multipart/form-data" class="upload-form">
                   <input type="file" name="profile_pic" accept="image/*">
                   <button type="submit" class="upload-btn">Update Profile Picture</button>
               </form>
           </div>
       </div>
       
       <?php if ($view === 'users'): ?>
           <table>
               <thead>
                   <tr>
                       <th>ID</th>
                       <th>Phone</th>
                       <th>Email</th>
                       <th>Branch</th>
                       <th>Aadhar</th>
                       <th>Address</th>
                       <th>Status</th>
                       <th>Created At</th>
                       <th>Actions</th>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach ($users as $user): ?>
                       <tr>
                           <td><?php echo htmlspecialchars($user['id']); ?></td>
                           <td><?php echo htmlspecialchars($user['phone']); ?></td>
                           <td><?php echo htmlspecialchars($user['email']); ?></td>
                           <td><?php echo htmlspecialchars($user['branch_name']); ?></td>
                           <td><?php echo htmlspecialchars($user['aadhar']); ?></td>
                           <td><?php echo htmlspecialchars($user['address']); ?></td>
                           <td class="status-<?php echo $user['status']; ?>">
                               <?php echo ucfirst($user['status']); ?>
                           </td>
                           <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                           <td>
                               <?php if ($user['status'] === 'pending'): ?>
                                   <form method="POST" style="display: inline;">
                                       <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                       <button type="submit" name="status" value="approved" class="action-btn approve-btn">
                                           Approve
                                       </button>
                                       <button type="submit" name="status" value="rejected" class="action-btn reject-btn">
                                           Reject
                                       </button>
                                   </form>
                               <?php endif; ?>
                           </td>
                       </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
       <?php else: ?>
           <table>
               <thead>
                   <tr>
                       <th>Log ID</th>
                       <th>Sender</th>
                       <th>Receiver</th>
                       <th>Source MAC</th>
                       <th>Dest MAC</th>
                       <th>Filename</th>
                       <th>Timestamp</th>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach ($logs as $log): ?>
                       <tr>
                           <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                           <td><?php echo htmlspecialchars($log['sender_id']); ?></td>
                           <td><?php echo htmlspecialchars($log['receiver_id']); ?></td>
                           <td><?php echo htmlspecialchars($log['source_mac']); ?></td>
                           <td><?php echo htmlspecialchars($log['destination_mac']); ?></td>
                           <td><?php echo htmlspecialchars($log['filename']); ?></td>
                           <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                       </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
       <?php endif; ?>
   </div>
</body>
</html>