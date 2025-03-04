<?php
session_start();
require 'db.php';

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

// Fetch all trip records from the database
$query = "SELECT trips.trip_no, drivers.full_name, trips.start_location, trips.start_date, trips.end_location, 
                 trips.end_date, trips.rate, trips.fuel, trips.disparage, trips.expenses, trips.net_income, 
                 trips.created_at
          FROM trips
          INNER JOIN drivers ON trips.driver_id = drivers.driver_id
          ORDER BY trips.created_at DESC";

$result = $conn->query($query);
$activeTripsQuery = "SELECT trips.trip_no, drivers.full_name, trips.start_location, trips.start_date, trips.end_location, 
                            trips.end_date, trips.rate, trips.fuel, trips.disparage, trips.expenses, trips.net_income, 
                            trips.created_at
                     FROM trips
                     INNER JOIN drivers ON trips.driver_id = drivers.driver_id
                     WHERE trips.status = 'active'
                     ORDER BY trips.created_at DESC";

$finishedTripsQuery = "SELECT trips.trip_no, drivers.full_name, trips.start_location, trips.start_date, trips.end_location, 
                              trips.end_date, trips.rate, trips.fuel, trips.disparage, trips.expenses, trips.net_income, 
                              trips.created_at
                       FROM trips
                       INNER JOIN drivers ON trips.driver_id = drivers.driver_id
                       WHERE trips.status = 'finished'
                       ORDER BY trips.created_at DESC";

$activeTripsResult = $conn->query($activeTripsQuery);
$finishedTripsResult = $conn->query($finishedTripsQuery);

// Mark trip as done
if (isset($_GET['mark_done_id'])) {
    $tripNo = $_GET['mark_done_id'];
    $updateStatusQuery = "UPDATE trips SET status = 'finished' WHERE trip_no = ?";
    $stmt = $conn->prepare($updateStatusQuery);
    $stmt->bind_param('i', $tripNo);
    $stmt->execute();
    header("Location: TripReport.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Reports</title>
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

        .finished-trips-title {
            margin-top: 50px;
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
h3.mt-5 {
    text-align: center; /* Center the text */
    font-size: 32px; /* Set the font size */
    font-weight: bold; /* Make the text bold */
    color: #007bff; /* Use a primary color for the text */
    margin-top: 50px; /* Add top margin to space out the heading */
    font-family: 'Arial', sans-serif; /* Use a clean, modern font */
    letter-spacing: 1px; /* Slight letter spacing for a stylish effect */
    transition: all 0.3s ease; /* Smooth transition for hover effects */
}

       
   
    </style>
</head>
<body>
     <!-- Display success or error messages -->
     <?php if (isset($message)) { echo "<div class='alert alert-success'>$message</div>"; } ?>
    <!-- User Icon at the Top Right Corner -->
    </div>
    <div class="header">
    <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" alt="Company Logo">
    <a href="AdminDashboard.php" style="text-decoration: none; color: inherit;">
    <h1>Admin Dashboard</h1>
</a>

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
 




      <!-- Navigation Buttons -->
      <div class="navbar-buttons">
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add New Trip</a>
    <a href="TripReport.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Trip Reports</a>
    <a href="WeeklyReports.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="Approval.php" class="btn btn-secondary"><i class="fas fa-check-circle"></i> Approval Status</a>
    <a href="DriverList.php" class="btn btn-primary"><i class="fas fa-users"></i> Drivers List</a>
    <a href="adminIssue.php" class="btn btn-success"><i class="fas fa-question-circle"></i> Issues</a>

</div>
        <h3 class="mt-5">Trip Reports</h3>
     <div class="table-container">
    
        <!-- Active Trips Table -->
        <div class="table-container">
            <h3 class="mt-5" >Active Trips</h3>
            <table class="table table-striped table-responsive-md">
                <thead>
                    <tr>
                        <th>Trip No</th>
                        <th>Driver Name</th>
                        <th>Start Location</th>
                        <th>Start Date</th>
                        <th>End Location</th>
                        <th>End Date</th>
                        <th>Rate</th>
                        <th>Fuel </th>
                        <th>Disparage</th>
                        <th>Expenses</th>
                        <th>Net Total </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activeTripsResult->num_rows > 0): ?>
                        <?php while ($row = $activeTripsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['trip_no']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td><?php echo $row['start_location']; ?></td>
                                <td><?php echo $row['start_date']; ?></td>
                                <td><?php echo $row['end_location']; ?></td>
                                <td><?php echo $row['end_date']; ?></td>
                                <td>$<?php echo number_format($row['rate'], 2); ?></td>
                                <td>$<?php echo number_format($row['fuel'], 2); ?></td>
                                <td>$<?php echo number_format($row['disparage'], 2); ?></td>
                                <td>$<?php echo number_format($row['expenses'], 2); ?></td>
                                <td>$<?php echo number_format($row['net_income'], 2); ?></td>
                                <td class="table-actions">
                                    <a href="edit_trip.php?edit_id=<?php echo $row['trip_no']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete_trip.php?delete_id=<?php echo $row['trip_no']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this trip?');">Delete</a>
                                    <a href="?mark_done_id=<?php echo $row['trip_no']; ?>" class="btn btn-success btn-sm">Done</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center">No active trips found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

     <!-- Finished Trips Table -->
     <div class="table-container">
            <h3 class="mt-5">Finished Trips</h3>
            <table class="table table-striped table-responsive-md">
                <thead>
                    <tr>
                        <th>Trip No</th>
                        <th>Driver Name</th>
                        <th>Start Location</th>
                        <th>Start Date</th>
                        <th>End Location</th>
                        <th>End Date</th>
                        <th>Rate (USD)</th>
                        <th>Fuel Cost</th>
                        <th>Disparage</th>
                        <th>Expenses</th>
                        <th>Net Income</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($finishedTripsResult->num_rows > 0): ?>
                        <?php while ($row = $finishedTripsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['trip_no']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td><?php echo $row['start_location']; ?></td>
                                <td><?php echo $row['start_date']; ?></td>
                                <td><?php echo $row['end_location']; ?></td>
                                <td><?php echo $row['end_date']; ?></td>
                                <td>$<?php echo number_format($row['rate'], 2); ?></td>
                                <td>$<?php echo number_format($row['fuel'], 2); ?></td>
                                <td>$<?php echo number_format($row['disparage'], 2); ?></td>
                                <td>$<?php echo number_format($row['expenses'], 2); ?></td>
                                <td>$<?php echo number_format($row['net_income'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No finished trips found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
    
</script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
