<?php
session_start();
include('db.php');

// Redirect to login page if the driver is not logged in
if (!isset($_SESSION['driver_id'])) {
    header('Location: Driver_Login.php');
    exit();
}

$driver_id = $_SESSION['driver_id']; // Get the driver_id from the session

// Initialize error and success messages
$error_msg = "";
$success_msg = "";

// Initialize full_name variable
$full_name = "";

// Fetch driver data based on driver_id
$query = "SELECT full_name FROM drivers WHERE driver_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $full_name = $row['full_name']; // Fetch full_name from the database
} else {
    $error_msg = "Driver not found.";
}

// Handle form submission for password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the current password from the database
    $query = "SELECT password FROM drivers WHERE driver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password']; // Fetching current password hash

        if (password_verify($current_password, $hashed_password)) {
            // Check if new password and confirm password match
            if ($new_password == $confirm_password) {
                // Hash the new password and update the database
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE drivers SET password = ? WHERE driver_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('si', $new_hashed_password, $driver_id);
                if ($update_stmt->execute()) {
                    $success_msg = "Password updated successfully!";
                } else {
                    $error_msg = "Failed to update password. Please try again.";
                }
            } else {
                $error_msg = "New password and confirmation do not match.";
            }
        } else {
            $error_msg = "Current password is incorrect.";
        }
    } else {
        $error_msg = "Driver not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            width: 50%;
            margin-top: 50px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .company-logo {
            max-width: 150px;
        }
        .back-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .back-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header with Company Logo and Name -->
    <div class="text-center">
        <img src="images/company_logo.png" class="company-logo" alt="Company Logo">
        <h2>Truck Office System</h2>
    </div>

    <!-- Display success or error message -->
    <?php if ($error_msg != "") { ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php } ?>
    <?php if ($success_msg != "") { ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php } ?>

    <!-- Change Password Form -->
    <h3>Change Password</h3>
    <form method="POST" action="">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <!-- Display full name dynamically from database -->
            <input type="text" class="form-control" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="driver_id">Driver ID</label>
            <input type="text" class="form-control" id="driver_id" value="<?php echo $driver_id; ?>" readonly>
        </div>
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>

    <!-- Back Button -->
    <a href="javascript:history.back()" class="btn back-btn">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

</body>
</html>
