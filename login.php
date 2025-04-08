<?php
// login.php

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

$error_message = "";
$login_type = isset($_GET['type']) ? $_GET['type'] : 'admin'; // Default to admin login

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'];
    $type = $_POST['login_type'];
    
    if ($type === 'admin') {
        // Admin login logic
        $username = $_POST['username'];
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        // Manager login logic with email instead of username
        $email = $_POST['email']; // Changed from username to email
        $stmt = $conn->prepare("SELECT * FROM manager WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $manager = $result->fetch_assoc();
            if (password_verify($password, $manager['password'])) {
                $_SESSION['manager_logged_in'] = true;
                $_SESSION['manager_username'] = $manager['username']; // Still store username in session
                $_SESSION['manager_email'] = $email; // Also store email
                $_SESSION['manager_id'] = $manager['id'];
                $_SESSION['branch_id'] = $manager['bid'];
                header("Location: manager_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid email or password.";
                $login_type = 'manager'; // Set the login type for error display
            }
        } else {
            $error_message = "Invalid email or password.";
            $login_type = 'manager'; // Set the login type for error display
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($login_type); ?> Login</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f6f8; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-container { 
            background: #fff; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: 350px; 
        }
        .tab-container {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            font-weight: bold;
        }
        .tab.active {
            border-bottom: 2px solid #007bff;
            color: #007bff;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500;
        }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .submit-btn { 
            background: #007bff; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background 0.3s ease; 
            width: 100%;
            font-size: 16px;
            font-weight: 500;
        }
        .submit-btn:hover { 
            background: #0056b3; 
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Account Login</h2>
        
        <div class="tab-container">
            <div class="tab <?php echo ($login_type === 'admin') ? 'active' : ''; ?>" onclick="window.location.href='?type=admin'">Admin</div>
            <div class="tab <?php echo ($login_type === 'manager') ? 'active' : ''; ?>" onclick="window.location.href='?type=manager'">Manager</div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="login_type" value="<?php echo $login_type; ?>">
            
            <?php if ($login_type === 'admin'): ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <button type="submit" name="login" class="submit-btn">
                Login as <?php echo ucfirst($login_type); ?>
            </button>
        </form>
    </div>
</body>
</html>