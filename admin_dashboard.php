<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php
session_start();
require_once 'db_connection.php';

// Fetch data for dashboard
$managers = $conn->query("SELECT m.*, b.branch_name FROM manager m LEFT JOIN branch b ON m.bid = b.id");
$branches = $conn->query("SELECT b.*, m.username FROM branch b left JOIN manager m ON b.id = m.bid ORDER BY b.id");
$users = $conn->query("SELECT u.*, b.branch_name 
        FROM users u 
        LEFT JOIN branch b ON u.bid = b.id 
        ORDER BY u.created_at DESC");
$logs = $conn->query("SELECT l.*, 
             s.id as sender_id,
             r.id as receiver_id
             FROM logs l
             LEFT JOIN users s ON l.sender_id = s.id
             LEFT JOIN users r ON l.receiver_id = r.id
             ORDER BY l.timestamp DESC");

// Count statistics
$totalManagers = $conn->query("SELECT COUNT(*) as count FROM manager")->fetch_assoc()['count'];
$totalBranches = $conn->query("SELECT COUNT(*) as count FROM branch")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$pendingUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='pending'")->fetch_assoc()['count'];
?>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #4b545c;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: #4b545c;
        }

        .sidebar-nav i {
            margin-right: 10px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2em;
            color: #007bff;
            margin-bottom: 10px;
        }

        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        /* Section Styles */
        .section {
            display: none;
            padding: 20px;
        }

        .section.active {
            display: block;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Button Styles */
        .btn-add {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .status-badge.pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-badge.active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* Side Panel Styles */
.side-panel {
    position: fixed;
    top: 0;
    right: -500px;
    width: 500px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
    transition: right 0.3s ease;
    z-index: 1000;
}

.side-panel.active {
    right: 0;
}

.panel-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.panel-header h2 {
    margin: 0;
    color: #333;
    font-size: 1.5rem;
}

.close-panel {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

/* Form Styles */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.input-group {
    flex: 1;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
}

.input-group input,
.input-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.input-group input:focus,
.input-group select:focus {
    border-color: #007bff;
    outline: none;
}

.password-hint {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    margin-top: auto;
    padding-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-cancel {
    padding: 10px 20px;
    border: 1px solid #ddd;
    background: #fff;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #f8f9fa;
}

.btn-submit {
    padding: 10px 20px;
    border: none;
    background: #007bff;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    background: #0056b3;
}

/* Overlay */
.panel-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

.panel-overlay.active {
    display: block;
}
    </style>
    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#dashboard" class="active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#managers" data-section="managers"><i class="fas fa-user-tie"></i> Managers</a></li>
                    <li><a href="#branches" data-section="branches"><i class="fas fa-building"></i> Branches</a></li>
                    <li><a href="#users" data-section="users"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="#logs" data-section="logs"><i class="fas fa-history"></i> Logs</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <h1>Dashboard Overview</h1>
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-user-tie"></i>
                        <h3>Total Managers</h3>
                        <p><?php echo $totalManagers; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-building"></i>
                        <h3>Total Branches</h3>
                        <p><?php echo $totalBranches; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3>Total Users</h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3>Pending Users</h3>
                        <p><?php echo $pendingUsers; ?></p>
                    </div>
                </div>
            </section>

            <!-- Managers Section -->
            <section id="managers" class="section">
                <div class="section-header">
                    <h1>Managers</h1>
                    <button onclick="showModal('addManagerModal')" class="btn-add">
                        <i class="fas fa-plus"></i> Add Manager
                    </button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Branch</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($manager = $managers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $manager['id']; ?></td>
                                <td><?php echo $manager['username']; ?></td>
                                <td><?php echo $manager['email']; ?></td>
                                <td><?php echo $manager['branch_name']; ?></td>
                                <td>
                                    <button onclick="editManager(<?php echo $manager['id']; ?>)" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteManager(<?php echo $manager['id']; ?>)" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Branches Section -->
            <section id="branches" class="section">
                <div class="section-header">
                    <h1>Branches</h1>
                    <button onclick="showModal('addBranchModal')" class="btn-add">
                        <i class="fas fa-plus"></i> Add Branch
                    </button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Branch Name</th>
                                <th>State</th>
                                <th>City</th>
                                <th>ZIP Code</th>
                                <th>Assigned Manager</th>
                                <th>Opening Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($branch = $branches->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $branch['id']; ?></td>
                                <td><?php echo $branch['branch_name']; ?></td>
                                <td><?php echo $branch['state']; ?></td>
                                <td><?php echo $branch['city']; ?></td>
                                <td><?php echo $branch['zip_code']; ?></td>
                                <td><?php echo $branch['username']; ?></td>
                                <td><?php echo $branch['opening_date']; ?></td>
                                <td>
                                    <button onclick="editBranch(<?php echo $branch['id']; ?>)" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteBranch(<?php echo $branch['id']; ?>)" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Users Section -->
            <section id="users" class="section">
                <h1>Users</h1>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone']; ?></td>
                                <td><?php echo $user['branch_name']; ?></td>
                                <td><span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span></td>
                                <td><?php echo $user['created_at']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Logs Section -->
            <section id="logs" class="section">
                <h1>System Logs</h1>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Sender ID</th>
                                <th>Receiver ID</th>
                                <th>Source Mac</th>
                                <th>Destination Mac</th>
                                <th>Filename</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo $log['sender_id']; ?></td>
                                <td><?php echo $log['receiver_id']; ?></td>
                                <td><?php echo $log['source_mac']; ?></td>
                                <td><?php echo $log['destination_mac']; ?></td>
                                <td><?php echo $log['filename']; ?></td>
                                <td><?php echo $log['timestamp']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Manager Modal -->
    <div id="addManagerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Manager</h2>
            <form id="managerForm" action="add_manager.php" method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Branch:</label>
                    <select name="bid" required>
                        <?php 
                        $branches->data_seek(0);
                        while($branch = $branches->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Add Manager</button>
            </form>
        </div>
    </div>

    <!-- Add Branch Modal -->
    <div id="addBranchModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Branch</h2>
            <form id="branchForm" action="add_branch.php" method="POST">
                <div class="form-group">
                    <label>Branch Name:</label>
                    <input type="text" name="branch_name" required>
                </div>
                <div class="form-group">
                    <label>State:</label>
                    <input type="text" name="state" required>
                </div>
                <div class="form-group">
                    <label>City:</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>ZIP Code:</label>
                    <input type="text" name="zip_code" required>
                </div>
                <div class="form-group">
                    <label>Opening Date:</label>
                    <input type="date" name="opening_date" required>
                </div>
                <button type="submit" class="btn-submit">Add Branch</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <!-- Slide Panel for Manager -->
<div id="managerPanel" class="side-panel">
    <div class="panel-content">
        <div class="panel-header">
            <h2 id="managerPanelTitle">Add New Manager</h2>
            <button class="close-panel">×</button>
        </div>
        <form id="managerForm" action="add_manager.php" method="POST">
            <div class="form-row">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password">
                    <small class="password-hint">Leave empty to keep current password when updating</small>
                </div>
                <div class="input-group">
                    <label>Branch</label>
                    <select name="bid" required>
                        <?php 
                        $branches->data_seek(0);
                        while($branch = $branches->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Save Manager</button>
            </div>
        </form>
    </div>
</div>

<!-- Slide Panel for Branch -->
<div id="branchPanel" class="side-panel">
    <div class="panel-content">
        <div class="panel-header">
            <h2 id="branchPanelTitle">Add New Branch</h2>
            <button class="close-panel">×</button>
        </div>
        <form id="branchForm" action="add_branch.php" method="POST">
            <div class="form-row">
                <div class="input-group">
                    <label>Branch Name</label>
                    <input type="text" name="branch_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>State</label>
                    <input type="text" name="state" required>
                </div>
                <div class="input-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>ZIP Code</label>
                    <input type="text" name="zip_code" required pattern="[0-9]{5,6}">
                </div>
                <div class="input-group">
                    <label>Opening Date</label>
                    <input type="date" name="opening_date" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Save Branch</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>