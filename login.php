<?php
// admin_panel.php

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $admin_username = $_POST['username'];
    $admin_password = $_POST['password'];

    // Prepared statement for admin login
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($admin_password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin_username;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // Admin is not logged in, show the login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($error_message)) {
        echo "<script>alert('$error_message');</script>";
    }
    echo '
        <html>
        <head>
            <title>Admin Login</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f6f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .login-container { background: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 300px; }
                .form-group { margin-bottom: 15px; }
                .form-group label { display: block; margin-bottom: 5px; }
                .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
                .submit-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.3s ease; width: 100%; }
                .submit-btn:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>Admin Login</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button type="submit" name="login" class="submit-btn">Login</button>
                </form>
            </div>
        </body>
        </html>
    ';
    exit();
}

// Admin dashboard logic
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'branches';

// Fetch branches
$branchQuery = "SELECT * FROM branch ORDER BY id DESC";
$branchResult = $conn->query($branchQuery);
$branches = $branchResult->fetch_all(MYSQLI_ASSOC);

// Fetch managers
$managerQuery = "SELECT m.*, b.branch_name FROM manager m LEFT JOIN branch b ON m.bid = b.id ORDER BY m.id DESC";
$managerResult = $conn->query($managerQuery);
$managers = $managerResult->fetch_all(MYSQLI_ASSOC);

// Fetch users
$usersQuery = "SELECT * FROM users ORDER BY created_at DESC";
$usersResult = $conn->query($usersQuery);
$users = $usersResult->fetch_all(MYSQLI_ASSOC);

// Fetch logs
$logsQuery = "SELECT l.*, u.email as user_email FROM logs l JOIN users u ON l.sender_id = u.id ORDER BY l.timestamp DESC";
$logsResult = $conn->query($logsQuery);
$logs = $logsResult->fetch_all(MYSQLI_ASSOC);

// Handle branch addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $state = $conn->real_escape_string($_POST['state']);
    $city = $conn->real_escape_string($_POST['city']);
    $zip_code = $conn->real_escape_string($_POST['zip_code']);
    $opening_date = $conn->real_escape_string($_POST['opening_date']);
    
    $stmt = $conn->prepare("INSERT INTO branch (branch_name, state, city, zip_code, opening_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $branch_name, $state, $city, $zip_code, $opening_date);
    $stmt->execute();
    header("Location: admin_panel.php?tab=branches");
    exit();
}

// Handle manager addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manager'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $bid = $conn->real_escape_string($_POST['bid']);
    
    $stmt = $conn->prepare("INSERT INTO manager (username, email, password, bid) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password, $bid);
    $stmt->execute();
    header("Location: admin_panel.php?tab=managers");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f6f8; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background-color: #fff; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; font-size: 24px; font-weight: bold; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
        .tab-button { padding: 12px 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #333; font-weight: bold; }
        .tab-button.active { background-color: #007bff; color: #fff; border-color: #007bff; }
        .tab-content { background-color: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .form-container { background: #fff; padding: 20px; border-radius: 4px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .submit-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.3s ease; }
        .submit-btn:hover { background: #0056b3; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .tabs { flex-direction: column; align-items: stretch; }
            .tab-button { padding: 12px; font-size: 14px; }
            table { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Admin Dashboard</h1>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <a href="?tab=branches" class="tab-button <?php echo $activeTab === 'branches' ? 'active' : ''; ?>">Branches</a>
            <a href="?tab=managers" class="tab-button <?php echo $activeTab === 'managers' ? 'active' : ''; ?>">Managers</a>
            <a href="?tab=users" class="tab-button <?php echo $activeTab === 'users' ? 'active' : ''; ?>">Users</a>
            <a href="?tab=logs" class="tab-button <?php echo $activeTab === 'logs' ? 'active' : ''; ?>">File Transfer Logs</a>
        </div>

        <div class="tab-content">
            <?php if ($activeTab === 'branches'): ?>
                <div class="form-container">
                    <h3>Add New Branch</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Branch Name</label>
                            <input type="text" name="branch_name" required>
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>Zip Code</label>
                            <input type="text" name="zip_code" required>
                        </div>
                        <div class="form-group">
                            <label>Opening Date</label>
                            <input type="date" name="opening_date" required>
                        </div>
                        <button type="submit" name="add_branch" class="submit-btn">Add Branch</button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>State</th>
                            <th>City</th>
                            <th>Zip Code</th>
                            <th>Opening Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?php echo $branch['branch_name']; ?></td>
                                <td><?php echo $branch['state']; ?></td>
                                <td><?php echo $branch['city']; ?></td>
                                <td><?php echo $branch['zip_code']; ?></td>
                                <td><?php echo $branch['opening_date']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($activeTab === 'managers'): ?>
                <div class="form-container">
                    <h3>Add New Manager</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Manager Username</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Branch</label>
                            <select name="bid" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_manager" class="submit-btn">Add Manager</button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managers as $manager): ?>
                            <tr>
                                <td><?php echo $manager['username']; ?></td>
                                <td><?php echo $manager['email']; ?></td>
                                <td><?php echo $manager['branch_name']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($activeTab === 'users'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['aadhar']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['status']; ?></td>
                                <td><?php echo $user['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($activeTab === 'logs'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Filename</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['user_email']; ?></td>
                                <td><?php echo $log['destination_mac']; ?></td>
                                <td><?php echo $log['filename']; ?></td>
                                <td><?php echo $log['timestamp']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

