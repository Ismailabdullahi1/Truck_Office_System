<?php
session_start(); // Start the session at the beginning of the file
require 'db.php'; // Include database connection

// Check if the driver is logged in
if (!isset($_SESSION['driver_id'])) {
    // Session is not set, so redirect to login page
    header("Location: Driver_Login.php");
    exit; // Ensure no further script execution happens
}
// Fetch driver's name from the database
$driver_id = $_SESSION['driver_id']; // Assuming driver's ID is stored in the session
// Assuming you have a database connection already set up
$query = "SELECT full_name FROM drivers WHERE driver_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();
$driver_name = $driver['full_name']; // Fetch the driver's full name

// Fetch total trips, paid, and unpaid for the driver
$driver_id = $_SESSION['driver_id'];

// Current week date range
$currentWeekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
$currentWeekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));

// Fetch total trips for the driver this week
$tripQuery = "SELECT COUNT(*) AS total_trips FROM trips WHERE driver_id = '$driver_id' AND trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
$tripResult = $conn->query($tripQuery);
$totalTrips = $tripResult->fetch_assoc()['total_trips'] ?? 0;

// Fetch total paid this week
$paidQuery = "SELECT SUM(t.net_income) AS total_paid FROM trips t WHERE t.driver_id = '$driver_id' AND t.paid = 1 AND t.trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
$paidResult = $conn->query($paidQuery);
$totalPaid = $paidResult->fetch_assoc()['total_paid'] ?? 0;

// Fetch total unpaid this week
$unpaidQuery = "SELECT SUM(t.net_income) AS total_unpaid FROM trips t WHERE t.driver_id = '$driver_id' AND t.paid = 0 AND t.trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
$unpaidResult = $conn->query($unpaidQuery);
$totalUnpaid = $unpaidResult->fetch_assoc()['total_unpaid'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Adminnnn.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/Screenshot_2024-12-22_164605-removebg-preview.png">
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
        }
        .header img {
            max-width: 100px;
            margin-right: 15px;
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
        /* Responsive styles for small and large displays */
        @media (max-width: 767px) {
            .dashboard-container {
                flex-direction: column;
                align-items: center;
            }
            .dashboard-box {
                width: 90%;
            }
        }
        /* Large screen display */
        @media (min-width: 768px) {
            .dashboard-box {
                width: 30%;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" alt="Company Logo">
    <a href="DriverDahsboard.php" style="text-decoration: none; color: inherit;">
    <h1>Driver  Dashboard</h1>
</a>

    <!-- Dropdown menu for user options -->
    <div class="dropdown">
        <button class="btn btn-link" type="button" id="userMenuToggle">
            <i class="fa fa-user-circle" style="font-size: 30px; color: #007bff;"></i>
        </button>
        <div id="userMenu" class="dropdown-menu">
            <!-- Display the admin's name at the top -->
            <span class="dropdown-item disabled" style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($driver_name); ?></span>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="DriverDahsboard.php">Home</a>
            <a class="dropdown-item" href="DriverChangeP.php">Change Password</a>
            <a class="dropdown-item" href="Driver.logout.php">Logout</a>
        </div>
    </div>
</div>
<div class="navbar-buttons">
    <a href="DriverTrips.php" class="btn btn-success"><i class="fas fa-car"></i> Trips</a>
    <a href="DriverPayment.php" class="btn btn-primary"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="ContactOffice.php" class="btn btn-info"><i class="fas fa-phone-alt"></i> Contact Office</a>
</div>
<!-- Dashboard Boxes -->
<div class="dashboard-container">
    <div class="dashboard-box">
        <div class="icon"><i class="fas fa-car"></i></div>
        <h3>Total Trips</h3>
        <div class="value"><?php echo $totalTrips; ?></div>
    </div>
    <div class="dashboard-box">
        <div class="icon"><i class="fas fa-dollar-sign"></i></div>
        <h3>Total Paid</h3>
        <div class="value">$<?php echo number_format($totalPaid, 2); ?></div>
    </div>
    <div class="dashboard-box">
        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        <h3>Total Unpaid</h3>
        <div class="value">$<?php echo number_format($totalUnpaid, 2); ?></div>
    </div>
</div>
<script>
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
    
</script>
</body>
</html>
