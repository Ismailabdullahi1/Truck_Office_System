<?php
session_start();
require 'db.php'; // Include database connection

// Ensure the driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header("Location: Driver_Login.php");
    exit;
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
// Get the driver's ID
$driver_id = $_SESSION['driver_id'];

// Fetch all payment details for the driver from the trips table (assuming payment is based on the trip status)
$query = "SELECT t.trip_no, t.start_location, t.end_location, t.net_income, t.paid, t.paid_time, t.receipt_file 
          FROM trips t 
          WHERE t.driver_id = '$driver_id' 
          ORDER BY t.trip_date DESC";
$result = $conn->query($query);

// Initialize variables for total calculations
$totalNetIncome = 0;
$totalPaid = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver - Payments</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Adminnnn.css" rel="stylesheet">
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
        .status-paid {
            color: green;
            font-weight: bold;
        }
        .status-unpaid {
            color: red;
            font-weight: bold;
        }
        .btn-view-receipt {
            color: #007bff;
            text-decoration: none;
        }
        .total-row {
            font-weight: bold;
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

<!-- Payment Table -->
<div class="table-container">
    <h3 class="text-center">Your Payments</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Trip No</th>
                <th>Start Location</th>
                <th>End Location</th>
                <th>Total Net Income</th> <!-- Updated to use $ instead of USD -->
                <th>Status</th>
                <th>Paid Time</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = ($row['paid'] == 1) ? 'Paid' : 'Unpaid'; // 1 means paid, 0 means unpaid
                    $statusClass = ($row['paid'] == 1) ? 'status-paid' : 'status-unpaid';
                    $receiptFile = $row['receipt_file']; // File path for the receipt

                    // Check if there's a receipt file (PDF or image)
                    $receiptLink = ($receiptFile) ? "<a href='uploads/$receiptFile' class='btn-view-receipt' target='_blank'>View Receipt</a>" : 'No Receipt';
                    
                    // Update total calculations
                    $totalNetIncome += $row['net_income'];
                    if ($row['paid'] == 1) {
                        $totalPaid += $row['net_income']; // Only add to total paid if the trip is paid
                    }

                    // Display the trip details
                    echo "<tr>
                        <td>{$row['trip_no']}</td>
                        <td>{$row['start_location']}</td>
                        <td>{$row['end_location']}</td>
                        <td>$" . number_format($row['net_income'], 2) . " </td> <!-- Display Net Income with $ symbol -->
                        <td class='{$statusClass}'>{$status}</td>
                        <td>{$row['paid_time']}</td>
                        <td>{$receiptLink}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>No payment records available.</td></tr>";
            }
            ?>
            <tr class="total-row">
                <td colspan="4" class="text-right">Total Net Income</td>
                <td colspan="3">$<?php echo number_format($totalNetIncome, 2); ?></td> <!-- Display Total with $ symbol -->
            </tr>
            <tr class="total-row">
                <td colspan="4" class="text-right">Total Paid</td>
                <td colspan="3">$<?php echo number_format($totalPaid, 2); ?> </td> <!-- Display Total with $ symbol -->
            </tr>
        </tbody>
    </table>
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
