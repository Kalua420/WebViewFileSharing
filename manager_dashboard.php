<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['manager_id'])) {
   header("Location: login.php");
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
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
       :root {
           --primary: #2563eb;
           --primary-dark: #1d4ed8;
           --secondary: #64748b;
           --success: #10b981;
           --danger: #ef4444;
           --warning: #f59e0b;
           --light: #f8fafc;
           --dark: #1e293b;
           --white: #ffffff;
           --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
           --radius: 8px;
           --radius-sm: 4px;
           --transition: all 0.3s ease;
       }
       
       * {
           margin: 0;
           padding: 0;
           box-sizing: border-box;
       }
       
       body { 
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           background: #f1f5f9;
           color: var(--dark);
           line-height: 1.6;
       }
       
       .container {
           max-width: 1280px;
           margin: 30px auto;
           background: var(--white);
           border-radius: var(--radius);
           box-shadow: var(--shadow);
           overflow: hidden;
       }
       
       /* Header Styles */
       .header {
           padding: 20px 30px;
           background: var(--white);
           display: flex;
           justify-content: space-between;
           align-items: center;
           border-bottom: 1px solid #e2e8f0;
       }
       
       .header h2 {
           font-size: 1.8rem;
           font-weight: 600;
           color: var(--primary);
       }
       
       .nav-tabs {
           display: flex;
           gap: 10px;
       }
       
       .tab-btn {
           padding: 8px 16px;
           background: var(--secondary);
           color: var(--white);
           text-decoration: none;
           border-radius: var(--radius-sm);
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .tab-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       .tab-btn.active {
           background: var(--primary);
       }
       
       .logout-btn {
           padding: 8px 16px;
           background: var(--danger);
           color: var(--white);
           text-decoration: none;
           border-radius: var(--radius-sm);
           transition: var(--transition);
           display: flex;
           align-items: center;
           gap: 5px;
           font-weight: 600;
       }
       
       .logout-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       /* Profile Section Styles */
       .profile-section {
           display: flex;
           align-items: center;
           gap: 30px;
           padding: 30px;
           background: linear-gradient(to right, #f1f5f9, #e2e8f0);
           position: relative;
       }
       
       .profile-pic-container {
           position: relative;
       }
       
       .profile-pic {
           width: 120px;
           height: 120px;
           border-radius: 50%;
           object-fit: cover;
           border: 4px solid var(--white);
           box-shadow: var(--shadow);
           transition: var(--transition);
       }
       
       .profile-pic:hover {
           transform: scale(1.05);
       }
       
       .profile-info {
           flex: 1;
       }
       
       .profile-info h3 {
           font-size: 1.6rem;
           margin-bottom: 10px;
           color: var(--primary-dark);
       }
       
       .profile-info p {
           margin: 8px 0;
           color: var(--secondary);
           font-size: 1.1rem;
       }
       
       .profile-info p i {
           margin-right: 8px;
           color: var(--primary);
       }
       
       .upload-form {
           margin-top: 15px;
           display: flex;
           align-items: center;
           gap: 10px;
       }
       
       .file-input-wrapper {
           position: relative;
           overflow: hidden;
           display: inline-block;
       }
       
       .file-input-wrapper input[type=file] {
           font-size: 100px;
           position: absolute;
           left: 0;
           top: 0;
           opacity: 0;
           cursor: pointer;
       }
       
       .file-input-trigger {
           padding: 8px 16px;
           background: var(--white);
           color: var(--primary);
           border: 1px solid var(--primary);
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .file-input-trigger:hover {
           background: #f8fafc;
       }
       
       .upload-btn {
           padding: 8px 16px;
           background: var(--primary);
           color: var(--white);
           border: none;
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .upload-btn:hover {
           background: var(--primary-dark);
           transform: translateY(-2px);
       }
       
       /* Content Section */
       .content-section {
           padding: 30px;
       }
       
       /* Table Styles */
       .table-wrapper {
           overflow-x: auto;
           border-radius: var(--radius);
           box-shadow: 0 2px 3px rgba(0,0,0,0.1);
       }
       
       table {
           width: 100%;
           border-collapse: collapse;
           white-space: nowrap;
       }
       
       thead tr {
           background: var(--primary);
           color: var(--white);
       }
       
       th {
           padding: 12px 18px;
           text-align: left;
           font-weight: 600;
       }
       
       td {
           padding: 12px 18px;
           border-bottom: 1px solid #e2e8f0;
           max-width: 200px;
           overflow: hidden;
           text-overflow: ellipsis;
       }
       
       tbody tr {
           transition: var(--transition);
       }
       
       tbody tr:hover {
           background: #f8fafc;
       }
       
       tbody tr:last-child td {
           border-bottom: none;
       }
       
       /* Status Styles */
       .status {
           padding: 4px 8px;
           border-radius: 20px;
           font-size: 0.9rem;
           font-weight: 600;
           display: inline-block;
           text-align: center;
           min-width: 100px;
       }
       
       .status-pending {
           background: #fef3c7;
           color: var(--warning);
       }
       
       .status-approved {
           background: #d1fae5;
           color: var(--success);
       }
       
       .status-rejected {
           background: #fee2e2;
           color: var(--danger);
       }
       
       /* Action Buttons */
       .actions {
           display: flex;
           gap: 5px;
       }
       
       .action-btn {
           padding: 6px 12px;
           border: none;
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           justify-content: center;
           gap: 5px;
       }
       
       .approve-btn {
           background: var(--success);
           color: var(--white);
       }
       
       .approve-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       .reject-btn {
           background: var(--danger);
           color: var(--white);
       }
       
       .reject-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       /* Empty State */
       .empty-state {
           padding: 50px 20px;
           text-align: center;
           color: var(--secondary);
       }
       
       .empty-state i {
           font-size: 3rem;
           margin-bottom: 15px;
           color: var(--primary);
       }
       
       /* Responsive Adjustments */
       @media (max-width: 768px) {
           .container {
               margin: 10px;
           }
           
           .header {
               flex-direction: column;
               gap: 15px;
               padding: 15px;
           }
           
           .profile-section {
               flex-direction: column;
               text-align: center;
               padding: 20px;
           }
           
           .profile-info p {
               font-size: 1rem;
           }
           
           .nav-tabs {
               width: 100%;
               justify-content: space-between;
           }
           
           .tab-btn, .logout-btn {
               padding: 6px 12px;
               font-size: 0.9rem;
           }
           
           th, td {
               padding: 8px 12px;
           }
       }
   </style>
</head>
<body>
   <div class="container">
       <div class="header">
           <h2><i class="fas fa-tachometer-alt"></i> Manager Dashboard</h2>
           <div class="nav-tabs">
               <a href="?view=users" class="tab-btn <?php echo $view === 'users' ? 'active' : ''; ?>">
                   <i class="fas fa-users"></i> Users
               </a>
               <a href="?view=logs" class="tab-btn <?php echo $view === 'logs' ? 'active' : ''; ?>">
                   <i class="fas fa-list-alt"></i> Logs
               </a>
               <a href="logout.php" class="logout-btn">
                   <i class="fas fa-sign-out-alt"></i> Logout
               </a>
           </div>
       </div>

       <div class="profile-section">
           <div class="profile-pic-container">
               <img src="<?php echo isset($manager['profile_pic']) ? 'uploads/' . $manager['profile_pic'] : 'uploads/default.png'; ?>" 
                    alt="Profile Picture" 
                    class="profile-pic">
           </div>
           <div class="profile-info">
               <h3><?php echo htmlspecialchars($manager['username']); ?></h3>
               <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($manager['email']); ?></p>
               <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($manager['branch_name']); ?></p>
               <form method="POST" enctype="multipart/form-data" class="upload-form">
                   <div class="file-input-wrapper">
                       <span class="file-input-trigger"><i class="fas fa-camera"></i> Choose Image</span>
                       <input type="file" name="profile_pic" accept="image/*">
                   </div>
                   <button type="submit" class="upload-btn">
                       <i class="fas fa-upload"></i> Update Profile
                   </button>
               </form>
           </div>
       </div>
       
       <div class="content-section">
           <?php if ($view === 'users'): ?>
               <div class="table-wrapper">
                   <?php if (count($users) > 0): ?>
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
                                       <td>
                                           <span class="status status-<?php echo $user['status']; ?>">
                                               <?php echo ucfirst($user['status']); ?>
                                           </span>
                                       </td>
                                       <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                       <td>
                                           <?php if ($user['status'] === 'pending'): ?>
                                               <form method="POST" class="actions">
                                                   <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                   <button type="submit" name="status" value="approved" class="action-btn approve-btn">
                                                       <i class="fas fa-check"></i> Approve
                                                   </button>
                                                   <button type="submit" name="status" value="rejected" class="action-btn reject-btn">
                                                       <i class="fas fa-times"></i> Reject
                                                   </button>
                                               </form>
                                           <?php endif; ?>
                                       </td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-users-slash"></i>
                           <h3>No Users Found</h3>
                           <p>There are no users assigned to your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           <?php else: ?>
               <div class="table-wrapper">
                   <?php if (count($logs) > 0): ?>
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
                                       <td><?php echo htmlspecialchars($log['sender_email']); ?></td>
                                       <td><?php echo htmlspecialchars($log['receiver_email'] ?? 'N/A'); ?></td>
                                       <td><?php echo htmlspecialchars($log['source_mac']); ?></td>
                                       <td><?php echo htmlspecialchars($log['destination_mac']); ?></td>
                                       <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                       <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-clipboard-list"></i>
                           <h3>No Logs Found</h3>
                           <p>There are no activity logs for your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           <?php endif; ?>
       </div>
   </div>
</body>
</html>