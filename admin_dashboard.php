<?php
// Start the session at the very beginning
session_start();
require_once 'db_connection.php';

// Success and error messages
if (isset($_SESSION['success'])): ?>
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
require_once 'db_connection.php';

// Fetch data for dashboard
$managers = $conn->query("SELECT m.*, b.branch_name FROM manager m LEFT JOIN branch b ON m.bid = b.id");
$branches = $conn->query("SELECT b.*, m.username FROM branch b left JOIN manager m ON b.id = m.bid ORDER BY b.id");
$users = $conn->query("SELECT u.*, b.branch_name FROM users u LEFT JOIN branch b ON u.bid = b.id ORDER BY u.created_at DESC");
$logs = $conn->query("SELECT l.*,  s.id as sender_id, r.id as receiver_id FROM logs l LEFT JOIN users s ON l.sender_id = s.id LEFT JOIN users r ON l.receiver_id = r.id ORDER BY l.timestamp DESC");

// Count statistics
$totalManagers = $conn->query("SELECT COUNT(*) as count FROM manager")->fetch_assoc()['count'];
$totalBranches = $conn->query("SELECT COUNT(*) as count FROM branch")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$pendingUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='pending'")->fetch_assoc()['count'];
?>
    
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
                        <!-- Edit button (optional, you can implement it later) -->
                        <a href="edit_manager.php?id=<?php echo $manager['id']; ?>" class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <!-- Delete button with confirmation -->
                        <a href="delete_manager.php?id=<?php echo $manager['id']; ?>" onclick="return confirm('Are you sure you want to delete this manager?');">
                            <button class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </a>
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
                        <!-- Edit button (optional, can be implemented later) -->
                        <a href="edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <!-- Delete button with confirmation -->
                        <a href="delete_branch.php?id=<?php echo $branch['id']; ?>" onclick="return confirm('Are you sure you want to delete this branch?');">
                            <button class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </a>
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

            <!-- Logs Section with Search and Report Features -->
<section id="logs" class="section">
    <h1>System Logs</h1>
    
    <!-- Search Form -->
    <div class="search-container">
        <form id="logSearchForm" method="GET" action="#logs">
            <div class="search-row">
                <div class="search-group">
                    <label for="filename">Search by Filename:</label>
                    <input type="text" id="filename" name="filename" value="<?php echo isset($_GET['filename']) ? htmlspecialchars($_GET['filename']) : ''; ?>">
                </div>
                <div class="search-buttons">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                    <button type="button" onclick="clearSearch()" class="btn-clear"><i class="fas fa-times"></i> Clear</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php
    // Handle search functionality
    $searchConditions = [];
    $searchParams = [];
    
    if (isset($_GET['filename']) && !empty($_GET['filename'])) {
        $searchConditions[] = "l.filename LIKE ?";
        $searchParams[] = "%" . $_GET['filename'] . "%";
    }
    
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
        $searchConditions[] = "DATE(l.timestamp) >= ?";
        $searchParams[] = $_GET['date_from'];
    }
    
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
        $searchConditions[] = "DATE(l.timestamp) <= ?";
        $searchParams[] = $_GET['date_to'];
    }
    
    // Build the query
    $sql = "SELECT l.*, 
             s.id as sender_id, s.email as sender_email,
             r.id as receiver_id, r.email as receiver_email
             FROM logs l
             LEFT JOIN users s ON l.sender_id = s.id
             LEFT JOIN users r ON l.receiver_id = r.id";
    
    if (!empty($searchConditions)) {
        $sql .= " WHERE " . implode(" AND ", $searchConditions);
    }
    
    $sql .= " ORDER BY l.timestamp DESC";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($searchParams)) {
        $types = str_repeat("s", count($searchParams)); // Assuming all parameters are strings
        $stmt->bind_param($types, ...$searchParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we have results to display
    if ($result->num_rows > 0 && isset($_GET['filename'])):
    ?>
    
    <!-- Search Results -->
    <div class="results-header">
        <h2>Search Results</h2>
        <?php if (!empty($_GET['filename'])): ?>
        <form action="generate_report.php" method="POST" target="_blank">
            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($_GET['filename']); ?>">
            <button type="submit" class="btn-report"><i class="fas fa-file-export"></i> Generate Report</button>
        </form>
        <?php endif; ?>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Source Mac</th>
                    <th>Destination Mac</th>
                    <th>Filename</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while($log = $result->fetch_assoc()): ?>
                <tr>
                    <td style="color: red;"><?php echo $log['log_id']; ?></td>
                    <td><?php echo $log['sender_id'] . ' (' . $log['sender_email'] . ')'; ?></td>
                    <td><?php echo $log['receiver_id'] . ' (' . $log['receiver_email'] . ')'; ?></td>
                    <td style="color: lightgreen;"><?php echo $log['source_mac']; ?></td>
                    <td style="color: #0ef;"><?php echo $log['destination_mac']; ?></td>
                    <td><?php echo $log['filename']; ?></td>
                    <td><?php echo $log['timestamp']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <?php elseif (isset($_GET['filename'])): ?>
    <div class="no-results">
        <p>No logs found matching your search criteria.</p>
    </div>
    <?php else: ?>
    
    <!-- Default Logs Table (when no search performed) -->
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
                <?php 
                $logs = $conn->query("SELECT l.*, 
                        s.id as sender_id,
                        r.id as receiver_id
                        FROM logs l
                        LEFT JOIN users s ON l.sender_id = s.id
                        LEFT JOIN users r ON l.receiver_id = r.id
                        ORDER BY l.timestamp DESC LIMIT 20");
                
                while($log = $logs->fetch_assoc()): 
                ?>
                <tr>
                    <td style="color: red;"><?php echo $log['log_id']; ?></td>
                    <td><?php echo $log['sender_id']; ?></td>
                    <td><?php echo $log['receiver_id']; ?></td>
                    <td style="color: lightgreen;"><?php echo $log['source_mac']; ?></td>
                    <td style="color: #0ef;"><?php echo $log['destination_mac']; ?></td>
                    <td><?php echo $log['filename']; ?></td>
                    <td><?php echo $log['timestamp']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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