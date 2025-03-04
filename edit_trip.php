<?php
session_start();
require 'db.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin_Login.php");
    exit;
}

// Initialize variables
$message = "";

// Check if edit_id is passed
if (isset($_GET['edit_id'])) {
    $trip_no = $_GET['edit_id'];

    // Fetch the existing trip data
    $tripQuery = "SELECT * FROM trips WHERE trip_no = ?";
    $stmt = $conn->prepare($tripQuery);
    $stmt->bind_param("s", $trip_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $trip = $result->fetch_assoc();

    // If trip not found, redirect
    if (!$trip) {
        header("Location: trip_reports.php");
        exit;
    }
    
    // Fetch driver list for the select dropdown
    $driversQuery = "SELECT driver_id, full_name FROM drivers WHERE status = 'approved'";
    $driversResult = $conn->query($driversQuery);
} else {
    header("Location: trip_reports.php");
    exit;
}

// Handle form submission (update trip)
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

    // Update the trip in the database
    $updateQuery = "UPDATE trips 
                    SET driver_id = ?, start_location = ?, start_date = ?, end_location = ?, end_date = ?, 
                        rate = ?, fuel = ?, disparage = ?, expenses = ?, net_income = ? 
                    WHERE trip_no = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("issssddddds", $driver_id, $start_location, $start_date, $end_location, $end_date, 
                  $rate, $fuel, $disparage, $expenses, $net_income, $trip_no);


    if ($stmt->execute()) {
        $message = "Trip updated successfully! Net Income: $" . number_format($net_income, 2);
    } else {
        $message = "Error updating trip. Please try again.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        .admin-icon, .driver-icon {
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }
        .driver-icon {
            right: 70px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1 class="mt-5">Edit Trip</h1>

        <!-- Back Button -->
        <a href="TripReport.php" class="btn btn-primary mb-4"><i class="fa fa-arrow-left"></i> Back to Trip Reports</a>

        <!-- Success/Failure message -->
        <?php if ($message != ""): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="edit_trip.php?edit_id=<?php echo $trip_no; ?>" method="POST">
            <input type="hidden" name="trip_no" value="<?php echo $trip['trip_no']; ?>">

            <div class="form-group">
                <label for="driver_id">Driver</label>
                <select class="form-control" name="driver_id" required>
                    <option value="">Select Driver</option>
                    <?php while ($driver = $driversResult->fetch_assoc()): ?>
                        <option value="<?php echo $driver['driver_id']; ?>" <?php if ($driver['driver_id'] == $trip['driver_id']) echo 'selected'; ?>>
                            <?php echo $driver['full_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_location">Start Location</label>
                <input type="text" class="form-control" name="start_location" value="<?php echo $trip['start_location']; ?>" required>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $trip['start_date']; ?>" required>
            </div>

            <div class="form-group">
                <label for="end_location">End Location</label>
                <input type="text" class="form-control" name="end_location" value="<?php echo $trip['end_location']; ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $trip['end_date']; ?>" required>
            </div>

            <div class="form-group">
                <label for="rate">Rate (USD)</label>
                <input type="number" class="form-control" name="rate" value="<?php echo $trip['rate']; ?>" required>
            </div>

            <div class="form-group">
                <label for="fuel">Fuel Cost</label>
                <input type="number" class="form-control" name="fuel" value="<?php echo $trip['fuel']; ?>" required>
            </div>

            <div class="form-group">
                <label for="disparage">Disparage Cost</label>
                <input type="number" class="form-control" name="disparage" value="<?php echo $trip['disparage']; ?>" required>
            </div>

            <div class="form-group">
                <label for="expenses">Expenses</label>
                <input type="number" class="form-control" name="expenses" value="<?php echo $trip['expenses']; ?>" required>
            </div>

            <div class="form-group">
                <label for="net_income">Net Income (USD)</label>
                <input type="number" class="form-control" name="net_income" value="<?php echo $trip['net_income']; ?>" readonly>
            </div>

            <button type="submit" class="btn btn-success">Update Trip</button>
        </form>
    </div>

    <script>
        // Calculate the net income automatically when user enters values for rate, fuel, disparage, and expenses
        document.querySelector('form').addEventListener('input', function () {
            var rate = parseFloat(document.querySelector('input[name="rate"]').value) || 0;
            var fuel = parseFloat(document.querySelector('input[name="fuel"]').value) || 0;
            var disparage = parseFloat(document.querySelector('input[name="disparage"]').value) || 0;
            var expenses = parseFloat(document.querySelector('input[name="expenses"]').value) || 0;
            
            var netIncome = rate - (fuel + disparage + expenses);
            document.querySelector('input[name="net_income"]').value = netIncome.toFixed(2);
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
