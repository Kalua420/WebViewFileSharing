<?php
session_start();
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $branchId = $_GET['id'];

    // Fetch branch data
    $sql = "SELECT * FROM branch WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $branch_name = $_POST['branch_name'];
        $state = $_POST['state'];
        $city = $_POST['city'];
        $zip_code = $_POST['zip_code'];
        $opening_date = $_POST['opening_date'];

        // Update query
        $updateSql = "UPDATE branch SET branch_name = ?, state = ?, city = ?, zip_code = ?, opening_date = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("sssssi", $branch_name, $state, $city, $zip_code, $opening_date, $branchId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Branch updated successfully!';
            header('Location: admin_dashboard.php'); // Redirect back to the dashboard
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update branch.';
        }
        $stmt->close();
    }
} else {
    $_SESSION['error'] = 'Branch ID is missing.';
    header('Location: admin_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Branch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 50px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            color: #555;
        }
        input[type="text"]:focus, input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }
        button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert > a {
            color: inherit;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Branch</h2>

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

        <form action="edit_branch.php?id=<?php echo $branch['id']; ?>" method="POST">
            <div class="form-group">
                <label for="branch_name">Branch Name:</label>
                <input type="text" name="branch_name" value="<?php echo $branch['branch_name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="state">State:</label>
                <input type="text" name="state" value="<?php echo $branch['state']; ?>" required>
            </div>
            <div class="form-group">
                <label for="city">City:</label>
                <input type="text" name="city" value="<?php echo $branch['city']; ?>" required>
            </div>
            <div class="form-group">
                <label for="zip_code">ZIP Code:</label>
                <input type="text" name="zip_code" value="<?php echo $branch['zip_code']; ?>" required>
            </div>
            <div class="form-group">
                <label for="opening_date">Opening Date:</label>
                <input type="date" name="opening_date" value="<?php echo $branch['opening_date']; ?>" required>
            </div>
            <button type="submit">Update Branch</button>
        </form>
    </div>
</body>
</html>