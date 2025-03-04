<?php
// Include your database connection
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

// Function to mark all trips of a driver as paid
function markAsPaid($conn, $trip_id) {
    $paid_time = date("l, Y-m-d H:i:s"); // Includes day of the week, date, and time

    // First, get the driver_id of the selected trip
    $driverQuery = "SELECT driver_id FROM trips WHERE trip_id = ?";
    $stmt = $conn->prepare($driverQuery);
    $stmt->bind_param("i", $trip_id);  // Bind the trip_id to get the driver_id
    $stmt->execute();
    $result = $stmt->get_result();
    $driver = $result->fetch_assoc();
    $driver_id = $driver['driver_id'];  // Get the driver's ID

    // Now update all trips of this driver
    $updateQuery = "UPDATE trips SET paid = 1, paid_time = ? WHERE driver_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $paid_time, $driver_id);  // Bind the paid time and driver_id
    if ($stmt->execute()) {
        echo "<div id='success-message' class='alert alert-success'>All trips for this driver marked as paid.</div>";
        echo "<script>setTimeout(function() { document.getElementById('success-message').style.display = 'none'; }, 3000);</script>";
    } else {
        echo "<div id='error-message' class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}


// Handle the 'Mark as Paid' action
if (isset($_GET['mark_paid'])) {
    $trip_id = $_GET['mark_paid'];
    markAsPaid($conn, $trip_id);
}

// Display unpaid trips with status 'finished'
function displayUnpaidTrips($conn) {
    $query = "SELECT t.trip_id, t.trip_no, d.full_name, t.driver_id, COUNT(t.trip_id) AS num_trips, SUM(t.net_income) AS total_net_income
              FROM trips t
              JOIN drivers d ON t.driver_id = d.driver_id
              WHERE YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1) 
              AND t.status = 'finished' 
              AND t.paid = 0
              GROUP BY t.driver_id
              ORDER BY total_net_income DESC";

    $result = $conn->query($query);

    // Display the table container
    echo "<div class='table-container'>";

    if ($result->num_rows > 0) {
        echo "<h2>This Week's Unpaid Trips</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead>
                <tr>
                    <th>Trip No</th>
                    <th>Driver Name</th>
                    <th>Number of Trips</th>
                    <th>Total Net Income</th>
                    <th>Action</th>
                </tr>
              </thead>
              <tbody>";

        while ($row = $result->fetch_assoc()) {
            $driver_id = $row['driver_id'];
            echo "<tr>";
            echo "<td>" . $row['trip_no'] . "</td>";
            echo "<td>" . $row['full_name'] . "</td>";
            echo "<td>
                    <a href='#' class='details-link' data-driver-id='$driver_id'>" . $row['num_trips'] . " trips</a>
                    <div class='details-box' id='details-$driver_id' style='display: none;'>
                        <button class='close-btn' data-driver-id='$driver_id'>&times;</button>
                        <ul>";

            // Fetch individual trips for the driver
            $tripQuery = "SELECT trip_no, net_income FROM trips WHERE driver_id = $driver_id AND status = 'finished' AND paid = 0";
            $tripResult = $conn->query($tripQuery);
            while ($trip = $tripResult->fetch_assoc()) {
                echo "<li>Trip No: " . $trip['trip_no'] . ", Income: $" . number_format($trip['net_income'], 2) . "</li>";
            }

            echo "      </ul>
                    </div>
                  </td>";
            echo "<td>$" . number_format($row['total_net_income'], 2) . "</td>";
            echo "<td><a href='?mark_paid=" . $row['trip_id'] . "' class='btn btn-success'>Mark as Paid</a></td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<table class='table table-bordered'>
                <thead>
                    <tr>
                        <th colspan='5'>No unpaid trips for this week.</th>
                    </tr>
                </thead>
              </table>";
    }

    echo "</div>"; // Close table container
}


// Display paid trips with driver full name and number of trips
function displayPaidTrips($conn) {
    $query = "SELECT t.trip_no, t.driver_id, SUM(t.net_income) AS total_net_income, t.paid_time, d.full_name, COUNT(t.trip_id) AS num_trips 
              FROM trips t
              JOIN drivers d ON t.driver_id = d.driver_id
              WHERE t.paid = 1 
              GROUP BY t.driver_id
              ORDER BY t.paid_time DESC";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo "<h2>Paid Trips</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Number of Trips</th>
                    <th>Total Paid </th>
                    <th>Paid Time</th>
                    <th>Details</th>
                </tr>
              </thead>
              <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['full_name'] . "</td>";
            echo "<td>" . $row['num_trips'] . "</td>";
            echo "<td>$" . number_format($row['total_net_income'], 2) . "</td>";
            echo "<td>" . $row['paid_time'] . "</td>";
            echo "<td><a href='DriverDetails.php?driver_id=" . $row['driver_id'] . "' class='btn btn-info'>View Report</a></td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p >No paid trips to display.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="taple.css">
    <link rel="stylesheet" href="Adminnnn.css">
    
    <style>
         body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            margin: 20px auto;
            max-width: 1000px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 20px;
        }
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

     
        .details-box {
            border: 1px solid #ddd;
            background: #f9f9f9;
            padding: 15px;
            margin-top: 10px;
            border-radius: 8px;
            max-width: 300px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .details-box ul {
            list-style: none;
            padding-left: 0;
        }
        .details-box li {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        .details-box li:last-child {
            border-bottom: none;
        }
        .details-box:hover {
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
        }
        .close-btn {
            float: right;
            background: transparent;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #333;
        }
        .details-link {
            text-decoration: underline;
            color: #007bff;
            cursor: pointer;
        }
        .details-link:hover {
            color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            border: none;
        }
        .btn-success:hover {
            background-color: #218838;
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
        <h3 class="mt-5">Paymnets </h3>
<div class="table-container">
<div class="table table-striped table-responsive-md">
    <!-- Unpaid Trips Section -->
    <?php displayUnpaidTrips($conn); ?>

    <!-- Paid Trips Section -->
    <?php displayPaidTrips($conn); ?>
</div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script>
    $(document).ready(function () {
        // Show details box
        $(".details-link").click(function (e) {
            e.preventDefault();
            let driverId = $(this).data('driver-id');
            $("#details-" + driverId).slideToggle();
        });

        // Close details box
        $(".close-btn").click(function () {
            let driverId = $(this).data('driver-id');
            $("#details-" + driverId).slideUp();
        });
    });
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
</script>

</body>
</html>
