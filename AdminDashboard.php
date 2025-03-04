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
// Fetch total trips of the week
$currentWeekStart = date("Y-m-d", strtotime("last sunday midnight"));
$currentWeekEnd = date("Y-m-d", strtotime("next saturday midnight"));

// Get the total trips this week
$tripQuery = "SELECT COUNT(*) AS total_trips FROM trips WHERE trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
$tripResult = $conn->query($tripQuery);
$totalTrips = $tripResult->fetch_assoc()['total_trips'] ?? 0;

// Get the total paid this week
function getTotalPaidThisWeek($conn, $currentWeekStart, $currentWeekEnd) {
    $query = "SELECT SUM(t.net_income) AS total_paid 
              FROM trips t 
              WHERE t.paid = 1 
              AND t.trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_paid'];
    } else {
        return 0; // If no paid trips this week
    }
}

// Get the total paid this week
$totalPaidThisWeek = getTotalPaidThisWeek($conn, $currentWeekStart, $currentWeekEnd);

// Get the total unpaid income this week
function getTotalUnpaidIncomeThisWeek($conn, $currentWeekStart, $currentWeekEnd) {
    $query = "SELECT SUM(t.net_income) AS total_unpaid_income 
              FROM trips t 
              WHERE t.paid = 0 
              AND t.trip_date BETWEEN '$currentWeekStart' AND '$currentWeekEnd'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_unpaid_income'];
    } else {
        return 0; // If no unpaid trips this week
    }
}

// Get the total unpaid income this week
$totalUnpaidIncomeThisWeek = getTotalUnpaidIncomeThisWeek($conn, $currentWeekStart, $currentWeekEnd);
// Fetch Driver List
$driverQuery = "SELECT driver_id, full_name, phone_number, address, status FROM drivers";
$driverResult = $conn->query($driverQuery);

// Fetch Best Drivers
$bestDriversQuery = "SELECT drivers.driver_id, drivers.full_name, COUNT(trips.trip_no) AS trip_count, SUM(trips.net_income) AS total_net_income
                     FROM trips
                     JOIN drivers ON trips.driver_id = drivers.driver_id
                     GROUP BY drivers.driver_id
                     ORDER BY total_net_income DESC LIMIT 5";
$bestDriversResult = $conn->query($bestDriversQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/Screenshot_2024-12-22_164605-removebg-preview.png">

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
.driver-table th, .driver-table td {
            text-align: center;
        }
        .status-active {
            color: green;
        }
        .status-inactive {
            color: red;
        }
        .chart-container {
            width: 80%;
            margin: 30px auto;
        }
        .print-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        .print-btn:hover {
            background-color: #0056b3;
        }
        .company-logo {
            max-width: 150px;
        }
        
        
        .header-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .date-time {
            font-size: 14px;
            color: gray;
            text-align: right;
        }
        h3.mt-4 {
    text-align: center; /* Center the text */
    font-size: 32px; /* Set the font size */
    font-weight: bold; /* Make the text bold */
    color: #007bff; /* Use a primary color for the text */
    margin-top: 50px; /* Add top margin to space out the heading */
    font-family: 'Arial', sans-serif; /* Use a clean, modern font */
    letter-spacing: 1px; /* Slight letter spacing for a stylish effect */
    transition: all 0.3s ease; /* Smooth transition for hover effects */
}
h3.mt-5 {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin-top: 50px;
            font-family: 'Arial', sans-serif;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

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



<div class="navbar-buttons">
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add New Trip</a>
    <a href="TripReport.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Trip Reports</a>
    <a href="WeeklyReports.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="Approval.php" class="btn btn-secondary"><i class="fas fa-check-circle"></i> Approval Status</a>
    <a href="DriverList.php" class="btn btn-primary"><i class="fas fa-users"></i> Drivers List</a>
    <a href="adminIssue.php" class="btn btn-success"><i class="fas fa-question-circle"></i> Issues</a>

</div>

<div class="dashboard-container">
    <div class="dashboard-box">
        <div class="icon"><i class="fa fa-truck"></i></div>
        <h3>Total Trips This Week</h3>
        <div class="value"><?php echo $totalTrips; ?></div>
    </div>
    <div class="dashboard-box">
        <div class="icon"><i class="fa fa-dollar-sign"></i></div>
        <h3>Total Paid This Week</h3>
        <div class="value">$<?php echo number_format($totalPaidThisWeek, 2); ?></div>
    </div>
    <div class="dashboard-box">
        <div class="icon"><i class="fa fa-hourglass-half"></i></div>
        <h3>Total Unpaid This Week</h3>
        <div class="value">$<?php echo number_format($totalUnpaidIncomeThisWeek, 2); ?></div>
    </div>
</div>
   <!-- Best Drivers Chart -->
   <h3 class="mt-5">Top 5 Best Drivers</h3>
    <div class="chart-container">
        <canvas id="bestDriversChart"></canvas>
    </div>

   
   
    

    <script>
    // Prepare data for chart
    <?php
    $driverNames = [];
    $tripCounts = [];
    $netIncomes = [];
    while ($driver = $bestDriversResult->fetch_assoc()) {
        $driverNames[] = $driver['full_name'];
        $tripCounts[] = $driver['trip_count'];
        $netIncomes[] = $driver['total_net_income'];
    }
    ?>

    var ctx = document.getElementById('bestDriversChart').getContext('2d');
    var bestDriversChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($driverNames); ?>,
            datasets: [{
                label: 'Total Net Income ($)',
                data: <?php echo json_encode($netIncomes); ?>,
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '$' + value; }
                    }
                }
            }
        }
    });

    // Display current date, time, minutes, and seconds
    function updateDateTime() {
        var date = new Date();
        var formattedDate = date.toLocaleString();
        document.getElementById('date-time').textContent = formattedDate;
    }

    setInterval(updateDateTime, 1000);  // Update every second

    // Print Report Function
    function printReport() {
        var printWindow = window.open('', '', 'height=800,width=800');
        printWindow.document.write('<html><head><title>Driver List Report</title><style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
        printWindow.document.write('.company-logo { max-width: 150px; }');
        printWindow.document.write('.table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
        printWindow.document.write('.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('.table th { background-color: #f2f2f2; }');
        printWindow.document.write('.date-time { font-size: 14px; color: gray; text-align: right; }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<img src="images/company_logo.png" class="company-logo" alt="Company Logo">');
        printWindow.document.write('<h2>Driver List Report</h2>');
        printWindow.document.write('<p class="date-time">' + document.getElementById('date-time').textContent + '</p>');
        printWindow.document.write(document.querySelector('.container').innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>
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
</body>
</html>
