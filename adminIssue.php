<?php
session_start();
require 'db.php'; // Include database connection

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
// Fetch all issues (open and closed)
$query = "SELECT * FROM driver_issues ORDER BY submitted_at DESC";
$issues_result = $conn->query($query);

// Handle status update and comment addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $issue_id = $_POST['issue_id'];
        $status = $_POST['status'];
        $admin_comment = $_POST['admin_comment'];

        // Update the issue's status and admin comment
        $stmt = $conn->prepare("UPDATE driver_issues SET status = ?, admin_comment = ? WHERE issue_id = ?");
        $stmt->bind_param("ssi", $status, $admin_comment, $issue_id);
        $stmt->execute();
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Issues</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
         body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .header {
    display: flex;
    align-items: center;
    padding: 15px;
    background-color: #343a40;
    color: white;
    position: relative; /* Ensure the dropdown is positioned relative to the header */
}

.header img {
    max-width: 100px;
    margin-right: 15px;
}

.header h1 {
    flex-grow: 1;
}

.dropdown {
    position: absolute;
    top: 15px;
    right: 20px;
}

.dropdown-menu {
    display: none;
    min-width: 150px;
}

.btn-link {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
}

        .navbar-buttons {
            margin: 20px;
            text-align: center;
        }
        .navbar-buttons a {
            margin: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .dashboard-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 30px auto;
        }
        .dashboard-box {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            width: 300px;
            margin: 10px;
            transition: transform 0.3s ease;
        }
        .dashboard-box:hover {
            transform: scale(1.05);
        }
        .dashboard-box h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #007bff;
        }
        .dashboard-box .value {
            font-size: 48px;
            font-weight: bold;
            color: #28a745;
        }
        .dashboard-box .icon {
            font-size: 50px;
            color: #6c757d;
        }
        .dashboard-box .unpaid {
            color: #dc3545;
        }
           /* Style the user icon */
#userMenuToggle {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
}

/* Dropdown Menu */
#userMenu {
    background-color: white;
    border: 1px solid #ddd;
   
    border-radius: 5px;
    position: absolute;
    top: 40px;  /* Adjust top to fit below the button */
    left: auto;  /* Remove any right-side alignment */
    right: 0;    /* Align to the left of the icon */
    width: 150px; /* Set a fixed width */
    max-height: 300px; /* Limit the height of the dropdown */
    overflow-y: auto; /* Allow scrolling when content overflows */
    display: none;
    z-index: 1000;
    white-space: nowrap; /* Prevent horizontal overflow */
}

/* Styling the dropdown items */
#userMenu .dropdown-item {
    padding: 8px 15px;
    color: #333;
    font-size: 14px;
    text-decoration: none;
    white-space: nowrap; /* Prevent text from wrapping */
}

#userMenu .dropdown-item:hover {
    background-color: #f5f5f5;
}

/* Position the icon at the top right */
.user-icon-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 999;
}
        .table-container {
            margin: 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        td {
            background-color: #f9f9f9;
        }
        .status-open {
            color: red;
            font-weight: bold;
        }
        .status-closed {
            color: green;
            font-weight: bold;
        }
        .form-container {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<!-- Header -->

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
            <a class="dropdown-item" href="AdminDashboard.php">Home</a>
            <a class="dropdown-item" href="change_password.php">Change Password</a>
            <a class="dropdown-item" href="logout.php">Logout</a>
        </div>
    </div>
</div>


<!-- Navbar Buttons -->
<div class="navbar-buttons">
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add New Trip</a>
    <a href="TripReport.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Trip Reports</a>
    <a href="WeeklyReports.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="Approval.php" class="btn btn-secondary"><i class="fas fa-check-circle"></i> Approval Status</a>
    <a href="DriverList.php" class="btn btn-primary"><i class="fas fa-users"></i> Drivers List</a>
    <a href="adminIssue.php" class="btn btn-success"><i class="fas fa-question-circle"></i> Issues</a>

</div>

<!-- Issues Management Table -->
<div class="table-container">
    <h3>Manage Issues</h3>
    <form action="adminIssue.php" method="POST" class="form-inline mb-3">
        <label for="status_filter" class="mr-2">Filter by Status:</label>
        <select class="form-control mr-2" id="status_filter" name="status_filter">
            <option value="open">Open</option>
            <option value="closed">Closed</option>
            <option value="all">All</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    
    <table class="table">
        <thead>
            <tr>
                <th>Issue Type</th>
                <th>Driver</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Filter by status if requested
            if (isset($_POST['status_filter']) && $_POST['status_filter'] != 'all') {
                $status_filter = $_POST['status_filter'];
                $query = "SELECT * FROM driver_issues WHERE status = '$status_filter' ORDER BY submitted_at DESC";
                $issues_result = $conn->query($query);
            }

            if ($issues_result->num_rows > 0) {
                while ($row = $issues_result->fetch_assoc()) {
                    $statusClass = ($row['status'] == 'open') ? 'status-open' : 'status-closed';
                    echo "<tr>
                        <td>{$row['issue_type']}</td>
                        <td>{$row['full_name']}</td>
                        <td class='$statusClass'>{$row['status']}</td>
                        <td>{$row['submitted_at']}</td>
                        <td>
                            <button class='btn btn-primary' data-toggle='modal' data-target='#updateModal{$row['issue_id']}'>Update</button>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No issues found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Modal for Issue Update -->
<?php
// Show modals for each issue
$issues_result->data_seek(0);  // Reset result pointer
while ($row = $issues_result->fetch_assoc()) {
    echo "<div class='modal fade' id='updateModal{$row['issue_id']}' tabindex='-1' role='dialog' aria-labelledby='updateModalLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='updateModalLabel'>Update Issue Status</h5>
                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>
                    <div class='modal-body'>
                        <form action='adminIssue.php' method='POST'>
                            <input type='hidden' name='issue_id' value='{$row['issue_id']}'>
                            <div class='form-group'>
                                <label for='status'>Status</label>
                                <select class='form-control' id='status' name='status'>
                                    <option value='open' ".($row['status'] == 'open' ? 'selected' : '').">Open</option>
                                    <option value='closed' ".($row['status'] == 'closed' ? 'selected' : '').">Closed</option>
                                </select>
                            </div>
                            <div class='form-group'>
                                <label for='admin_comment'>Admin Comment/Reason</label>
                                <textarea class='form-control' id='admin_comment' name='admin_comment' rows='4'>{$row['admin_comment']}</textarea>
                            </div>
                            <button type='submit' name='update_status' class='btn btn-primary'>Update Issue</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>";
}
?>

<!-- Bootstrap JS -->
<script>
    document.getElementById('userMenuToggle').addEventListener('click', function() {
    var menu = document.getElementById('userMenu');
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
});

</script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
