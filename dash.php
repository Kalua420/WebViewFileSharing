<?php
// admin_dashboard.php
require_once 'auth_check.php'; // Include authentication check

// Rest of your existing database connection code...

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Previous head content remains the same -->
    <style>
        /* Add these styles to your existing CSS */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout-button {
            padding: 8px 16px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .logout-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Admin Dashboard</h1>
                <a href="logout.php" class="logout-button">Logout</a>
            </div>
        </div>
    </div>

    <!-- Rest of your existing dashboard content -->
</body>
</html>