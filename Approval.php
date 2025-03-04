<?php
session_start();
require 'db.php';  // Assuming this file contains your database connection

// Ensure the admin is logged in
// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin_Login.php");
    exit;
}
// Fetch admin's name from the database
$admin_id = $_SESSION['admin_id'];
// Assuming you have a database connection already set up
$query = "SELECT full_name FROM admins WHERE admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_name = $admin['full_name'];
// Handle approval/rejection of pending users
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    $type = $_GET['type']; // 'driver' or 'admin'

    // Update status to 'approved'
    if ($type == 'driver') {
        $query = "UPDATE drivers SET status = 'approved' WHERE driver_id = ?";
    } else if ($type == 'admin') {
        $query = "UPDATE admins SET status = 'approved' WHERE admin_id = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: Approval.php"); // Redirect to the dashboard to refresh the table
    exit;
}

if (isset($_GET['reject_id'])) {
    $id = $_GET['reject_id'];
    $type = $_GET['type']; // 'driver' or 'admin'

    // Update status to 'rejected'
    if ($type == 'driver') {
        $query = "UPDATE drivers SET status = 'rejected' WHERE driver_id = ?";
    } else if ($type == 'admin') {
        $query = "UPDATE admins SET status = 'rejected' WHERE admin_id = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: Approval.php"); // Redirect to the dashboard to refresh the table
    exit;
}

// Fetch drivers data
$driversQuery = "SELECT driver_id, full_name, phone_number, address, email, created_at, status FROM drivers";
$driversResult = $conn->query($driversQuery);

// Fetch admins data
$adminsQuery = "SELECT admin_id, full_name, phone_number, address, email, created_at, status FROM admins";
$adminsResult = $conn->query($adminsQuery);

// Fetch pending drivers for approval
$pendingDriversQuery = "SELECT driver_id, full_name, phone_number, address, email, created_at, status FROM drivers WHERE status = 'pending'";
$pendingDriversResult = $conn->query($pendingDriversQuery);

// Fetch pending admins for approval
$pendingAdminsQuery = "SELECT admin_id, full_name, phone_number, address, email, created_at, status FROM admins WHERE status = 'pending'";
$pendingAdminsResult = $conn->query($pendingAdminsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="Adminnnn.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .navbar-buttons {
            margin: 20px 0;
            text-align: center;
        }

        .navbar-buttons .btn {
            margin: 5px;
        }

        .table-container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 40px;
        }

        .table thead {
            background-color: #007bff;
            color: #fff;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .table-actions .btn {
            display: inline-block;
            padding: 5px 10px;
        }

        @media (max-width: 768px) {
            .navbar-buttons {
                flex-direction: column;
            }

            .navbar-buttons .btn {
                margin: 10px 0;
            }
        }
        
        .user-icon-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999;
        }

        /* Dropdown Menu */
        #userMenu {
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            position: absolute;
            top: 40px;  /* Adjust top to fit below the button */
            left: auto;
            right: 0;
            width: 150px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            white-space: nowrap;
        }

        #userMenu .dropdown-item {
            padding: 8px 15px;
            color: #333;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
        }

        #userMenu .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .action-icons {
            display: flex;
            justify-content: center;
        }

        .action-icons i {
            margin: 0 10px;
            cursor: pointer;
            font-size: 20px;
        }

        .action-icons i.approve {
            color: green;
        }

        .action-icons i.reject {
            color: red;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" alt="Company Logo">
    <h1>Admin Dashboard</h1>

    <!-- Dropdown menu for user options -->
    <div class="dropdown">
        <button class="btn btn-link" type="button" id="userMenuToggle">
            <i class="fa fa-user-circle" style="font-size: 30px; color: #007bff;"></i>
        </button>
        <div id="userMenu" class="dropdown-menu">
            <!-- Display the admin's name at the top -->
            <span class="dropdown-item disabled" style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($admin_name); ?></span>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="change_password.php">Change Password</a>
            <a class="dropdown-item" href="logout.php">Logout</a>
        </div>
    </div>
</div>





      <!-- Navigation Buttons -->
      <div class="navbar-buttons">
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add New Trip</a>
    <a href="TripReport.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Trip Reports</a>
    <a href="WeeklyReports.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="Approval.php" class="btn btn-secondary"><i class="fas fa-check-circle"></i> Approval Status</a>
    <a href="DriverList.php" class="btn btn-primary"><i class="fas fa-users"></i> Drivers List</a>
    <a href="adminIssue.php" class="btn btn-success"><i class="fas fa-question-circle"></i> Issues</a>

</div>
    <!-- Pending Admins for Approval -->
    <div class="container table-container">
        <h2>Admins Pending Approval</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Created Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pendingAdminsResult->num_rows > 0): ?>
                    <?php while ($row = $pendingAdminsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['admin_id']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td class="action-icons">
                                <a href="?approve_id=<?php echo $row['admin_id']; ?>&type=admin"><i class="fa fa-check-circle approve" title="Approve"></i></a>
                                <a href="?reject_id=<?php echo $row['admin_id']; ?>&type=admin"><i class="fa fa-times-circle reject" title="Reject"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No pending admins found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pending Drivers for Approval -->
        <h2>Drivers Pending Approval</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Created Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pendingDriversResult->num_rows > 0): ?>
                    <?php while ($row = $pendingDriversResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['driver_id']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td class="action-icons">
                                <a href="?approve_id=<?php echo $row['driver_id']; ?>&type=driver"><i class="fa fa-check-circle approve" title="Approve"></i></a>
                                <a href="?reject_id=<?php echo $row['driver_id']; ?>&type=driver"><i class="fa fa-times-circle reject" title="Reject"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No pending drivers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Approved Admins Table -->
    <div class="container table-container">
        <h2>Approved Admins</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Created Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($adminsResult->num_rows > 0): ?>
                    <?php while ($row = $adminsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['admin_id']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No approved admins found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Approved Drivers Table -->
    <div class="container table-container">
        <h2>Approved Drivers</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Created Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($driversResult->num_rows > 0): ?>
                    <?php while ($row = $driversResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['driver_id']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No approved drivers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Toggle user menu
        document.getElementById('userMenuToggle').addEventListener('click', function() {
            var userMenu = document.getElementById('userMenu');
            userMenu.style.display = userMenu.style.display === 'block' ? 'none' : 'block';
        });
    </script>
</body>
</html>
