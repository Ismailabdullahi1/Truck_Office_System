<?php
// Start the session
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


// Fetch drivers list for the select dropdown
$driversQuery = "SELECT driver_id, full_name FROM drivers WHERE status = 'approved'";
$driversResult = $conn->query($driversQuery);

// Check if the query was successful and return any errors
if ($driversResult === false) {
    die("Error fetching drivers list: " . $conn->error);
}

// Initialize message variable
$message = "";
$driver_name = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $trip_no = $_POST['trip_no'];
    $driver_id = $_POST['driver_id'];
    $start_location = $_POST['start_location'];
    $start_date = $_POST['start_date'];
    $end_location = $_POST['end_location'];
    $end_date = $_POST['end_date'];
    $rate = $_POST['rate'];
    $fuel = $_POST['fuel'];
    $disparage = $_POST['disparage'];
    $expenses = $_POST['expenses'];

    // Calculate net income
    $net_income = $rate - ($fuel + $disparage + $expenses);

    // Get the driver's full name for success message
    $driverQuery = "SELECT full_name FROM drivers WHERE driver_id = ?";
    $stmt = $conn->prepare($driverQuery);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $stmt->bind_result($driver_name);
    $stmt->fetch();
    $stmt->close();
   // Check if a trip with the same details already exists
$checkTripQuery = "SELECT trip_no FROM trips WHERE driver_id = ? AND start_location = ? AND start_date = ? AND end_location = ? AND end_date = ?";
$stmt = $conn->prepare($checkTripQuery);
$stmt->bind_param("issss", $driver_id, $start_location, $start_date, $end_location, $end_date);
$stmt->execute();
$stmt->store_result();

// If a trip already exists with the same details
if ($stmt->num_rows > 0) {
    $message = "A trip with the same details already exists.";
    $stmt->close();
} else {
    // Insert new trip data
    $net_income = $rate - ($fuel + $disparage + $expenses);

    // Prepare and execute the SQL insert query
    $insertTripQuery = "INSERT INTO trips (trip_no, driver_id, start_location, start_date, end_location, end_date, rate, fuel, disparage, expenses, net_income)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertTripQuery);
    $stmt->bind_param("sissssddddd", $trip_no, $driver_id, $start_location, $start_date, $end_location, $end_date, $rate, $fuel, $disparage, $expenses, $net_income);

    if ($stmt->execute()) {
        // Success message
        $message = "Trip successfully created! Trip No: $trip_no | Driver: " . $driver_name . " | Net Income: $" . number_format($net_income, 2);
    } else {
        // Error message
        $message = "Error adding trip. Please try again.";
    }

    $stmt->close();
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/Screenshot_2024-12-22_164605-removebg-preview.png">

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
    <script>
        // Calculate Net Income dynamically
        function calculateNetIncome() {
            const rate = parseFloat(document.getElementById('rate').value) || 0;
            const fuel = parseFloat(document.getElementById('fuel').value) || 0;
            const disparage = parseFloat(document.getElementById('disparage').value) || 0;
            const expenses = parseFloat(document.getElementById('expenses').value) || 0;
            const netIncome = rate - fuel - disparage - expenses;
            document.getElementById('net_income_display').innerText = `$${netIncome.toFixed(2)}`;
        }
    </script>
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
<div class="container mt-4">
       

        <!-- Display success or error message -->
        <div id="message" class="alert alert-success">
    <?php echo $message; ?>
</div>
        




      <!-- Navigation Buttons -->
      <div class="navbar-buttons">
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add New Trip</a>
    <a href="TripReport.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Trip Reports</a>
    <a href="WeeklyReports.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Payments</a>
    <a href="Approval.php" class="btn btn-secondary"><i class="fas fa-check-circle"></i> Approval Status</a>
    <a href="DriverList.php" class="btn btn-primary"><i class="fas fa-users"></i> Drivers List</a>
    <a href="AddTrip.php" class="btn btn-success"><i class="fas fa-question-circle"></i> Issues</a>

</div>
        <!-- Add Trip Section -->
        <h3 class="mt-5">Add New Trip </h3>
        <section>
            
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="trip_no" class="form-label">Trip No:</label>
                        <input type="text" id="trip_no" name="trip_no" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="driver_id" class="form-label">Driver:</label>
                        <select class="form-select" id="driver_id" name="driver_id" required>
                    <option value="">Select Driver</option>
                    <?php if ($driversResult->num_rows > 0): ?>
                        <?php while ($driver = $driversResult->fetch_assoc()): ?>
                            <option value="<?php echo $driver['driver_id']; ?>"><?php echo $driver['full_name']; ?></option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option disabled>No approved drivers available</option>
                    <?php endif; ?>
                </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start_location" class="form-label">Start Location:</label>
                        <input type="text" id="start_location" name="start_location" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="end_location" class="form-label">End Location:</label>
                        <input type="text" id="end_location" name="end_location" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="rate" class="form-label">Rate ($):</label>
                        <input type="number" step="0.01" id="rate" name="rate" class="form-control" oninput="calculateNetIncome()" required>
                    </div>
                    <div class="col-md-3">
                        <label for="fuel" class="form-label">Fuel (-):</label>
                        <input type="number" step="0.01" id="fuel" name="fuel" class="form-control" oninput="calculateNetIncome()" required>
                    </div>
                    <div class="col-md-3">
                        <label for="disparage" class="form-label">Disparage (-):</label>
                        <input type="number" step="0.01" id="disparage" name="disparage" class="form-control" oninput="calculateNetIncome()" required>
                    </div>
                    <div class="col-md-3">
                        <label for="expenses" class="form-label">Expenses ($):</label>
                        <input type="number" step="0.01" id="expenses" name="expenses" class="form-control" oninput="calculateNetIncome()" required>
                    </div>
                </div>
                <h4>Net Income: <span id="net_income_display">$0.00</span></h4>
                <button type="submit" name="add_trip" class="btn btn-primary">Add Trip</button>
            </form>
        </section>
    </div>
    <script>
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
     // Automatically hide the message after 2 seconds
     setTimeout(function() {
        var message = document.getElementById('message');
        if (message) {
            message.style.display = 'none';
        }
    }, 2000); // 2000 milliseconds = 2 seconds
</script>
</body>
</html>
