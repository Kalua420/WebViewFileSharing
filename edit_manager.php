<?php
session_start();
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $managerId = $_GET['id'];

    // Fetch manager data
    $sql = "SELECT * FROM manager WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $managerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $manager = $result->fetch_assoc();
    $stmt->close();

    // Fetch branches that do not have any manager assigned
    $branchesSql = "SELECT * FROM branch WHERE id NOT IN (SELECT bid FROM manager WHERE bid IS NOT NULL)";
    $branches = $conn->query($branchesSql);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $bid = $_POST['bid'];

        // If no branch is selected, keep the current branch
        if (empty($bid)) {
            $bid = $manager['bid']; // Retain the current branch if no new branch is selected
        }

        // Set the password hash (if new password provided)
        if (!empty($password)) {
            // Hash the password only if provided
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        } else {
            // Use existing password if none is provided
            $hashedPassword = $manager['password']; // Assuming we retain the original password if not changed
        }

        // Base SQL query
        $updateSql = "UPDATE manager SET username = ?, email = ?, bid = ?, password = ? WHERE id = ?";

        // Prepare and bind parameters for SQL
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssiss", $username, $email, $bid, $hashedPassword, $managerId);

        // Execute the query
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Manager updated successfully!';
            header('Location: admin_dashboard.php'); // Redirect back to the dashboard
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update manager.';
        }
        $stmt->close();
    }
} else {
    $_SESSION['error'] = 'Manager ID is missing.';
    header('Location: admin_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Manager</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, select:focus {
            border-color: #007bff;
            outline: none;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 10px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Manager</h2>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <form action="edit_manager.php?id=<?php echo $manager['id']; ?>" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" value="<?php echo $manager['username']; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo $manager['email']; ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password (Leave empty to keep current password):</label>
                <input type="password" name="password">
                <small>Leave this field empty if you don't want to change the password.</small>
            </div>
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select name="bid">
                    <option value="">Leave unchanged</option> <!-- Option to leave unchanged -->
                    <?php while ($branch = $branches->fetch_assoc()): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php if ($branch['id'] == $manager['bid']) echo 'selected'; ?>>
                            <?php echo $branch['branch_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit">Update Manager</button>
        </form>
    </div>
</body>
</html>
