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

// Fetch all trips for the driver, ordering by status (Active first) and then by trip date
$query = "SELECT * FROM trips WHERE driver_id = '$driver_id' ORDER BY status DESC, created_at DESC";
$result = $conn->query($query);

// Fetch the driver's name
$queryDriver = "SELECT full_name FROM drivers WHERE driver_id = '$driver_id'";
$driverResult = $conn->query($queryDriver);
$driverName = $driverResult->fetch_assoc()['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver - Trips</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Adminnnn.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/x-icon" href="images/Screenshot_2024-12-22_164605-removebg-preview.png">
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
        .status-finished {
            color: green;
            font-weight: bold;
        }
        .status-active {
            color: red;
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

<!-- Navbar Buttons -->
<div class="navbar-buttons">
    <a href="DriverTrips.php" class="btn btn-success"><i class="fas fa-car"></i> Trips</a>
    <a href="DriverPayment.php" class="btn btn-primary"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="ContactOffice.php" class="btn btn-info"><i class="fas fa-phone-alt"></i> Contact Office</a>
</div>

<!-- Print Report Button -->
<div class="text-center">
    <button class="btn btn-primary" onclick="printReport()"><i class="fas fa-print"></i> Print Report</button>
</div>

<!-- Trips Table -->
<div class="table-container">
    <h3 class="text-center">Your Trips</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Trip No</th>
                <th>Start Location</th>
                <th>Start Date</th>
                <th>End Location</th>
                <th>End Date</th>
                <th>Rate</th>
                <th>Fuel</th>
                <th>Disparage</th>
                <th>Expenses</th>
                <th>Net Income</th>
                <th>Paid</th>
                <th>Paid Time </th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalNetIncome = 0;
            $totalPaid = 0;
            $totalUnpaid = 0;

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = ($row['status'] == 'Active') ? 'Active' : 'Finished';
                    $statusClass = ($row['status'] == 'Active') ? 'status-active' : 'status-finished';
                    $paidStatus = $row['paid'] ? 'Paid' : 'Unpaid';

                    $totalNetIncome += $row['net_income'];
                    if ($row['paid']) {
                        $totalPaid += $row['net_income'];
                    } else {
                        $totalUnpaid += $row['net_income'];
                    }

                    echo "<tr>
                        <td>{$row['trip_no']}</td>
                        <td>{$row['start_location']}</td>
                        <td>{$row['start_date']}</td>
                        <td>{$row['end_location']}</td>
                        <td>{$row['end_date']}</td>
                        <td>{$row['rate']}</td>
                        <td>{$row['fuel']}</td>
                        <td>{$row['disparage']}</td>
                        <td>{$row['expenses']}</td>
                        <td>{$row['net_income']}</td>
                        <td>{$paidStatus}</td>
                        <td>{$row['paid_time']}</td>
                        <td class='{$statusClass}'>{$status}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='13' class='text-center'>No trips available.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Total Calculations -->
    <div class="text-right">
        <p><strong>Total Net Income:</strong> $<?php echo number_format($totalNetIncome, 2); ?></p>
        <p><strong>Total Paid:</strong> $<?php echo number_format($totalPaid, 2); ?></p>
        <p><strong>Total Unpaid:</strong> $<?php echo number_format($totalUnpaid, 2); ?></p>
    </div>
</div>
<script>
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
    
</script>
<script>
    function printReport() {
        var printWindow = window.open('', '', 'height=800,width=800');
        var currentDate = new Date();
        var day = currentDate.toLocaleString('default', { weekday: 'long' });
        var date = currentDate.toLocaleDateString();
        var time = currentDate.toLocaleTimeString();

        printWindow.document.write('<html><head><title>Official Report</title><style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
        printWindow.document.write('.company-logo { max-width: 150px; }');
        printWindow.document.write('.table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
        printWindow.document.write('.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('.table th { background-color: #f2f2f2; }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" class="company-logo" alt="Company Logo">');
        printWindow.document.write('<h2>Driver Report for ' + "<?php echo $driverName; ?>" + '</h2>');
        printWindow.document.write('<p><strong>Report Generated on:</strong> ' + day + ', ' + date + ' at ' + time + '</p>');
        printWindow.document.write('<p>This is a Computer Generated Report</p>');
        printWindow.document.write(document.querySelector('.table-container').innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>

</body>
</html>
