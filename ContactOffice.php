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

// Fetch driver's full name
$query = "SELECT full_name FROM drivers WHERE driver_id = '$driver_id'";
$result = $conn->query($query);
$driver_name = ($result->num_rows > 0) ? $result->fetch_assoc()['full_name'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $issue_type = $_POST['issue_type'];
    $issue_description = $_POST['issue_description'];

    // Check if the same issue has already been submitted
    $check_query = "SELECT * FROM driver_issues WHERE driver_id = '$driver_id' AND issue_type = '$issue_type' AND status = 'open'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        // If the issue has been already submitted, set the message
        $message = "You have already submitted this issue. Please wait for a response.";
    } else {
        // Insert the issue into the database
        $stmt = $conn->prepare("INSERT INTO driver_issues (driver_id, full_name, issue_type, issue_description, status) VALUES (?, ?, ?, ?, 'open')");
        $stmt->bind_param("isss", $driver_id, $driver_name, $issue_type, $issue_description);
        $stmt->execute();
        $stmt->close();
        $message = "Thank you for submitting your issue. Please wait for a response.";
    }
}

// Fetch all issues submitted by the driver
$issues_query = "SELECT * FROM driver_issues WHERE driver_id = '$driver_id' ORDER BY submitted_at DESC";
$issues_result = $conn->query($issues_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver - Contact</title>
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
        .form-container {
            margin: 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
<!-- Issues Table -->
<!-- Issues Table -->
<div class="table-container">
    <h3>Your Submitted Issues</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Issue Type</th>
                <th>Issue Description</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Admin Comment</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            if ($issues_result->num_rows > 0) {
                while ($row = $issues_result->fetch_assoc()) {
                    $statusClass = ($row['status'] == 'open') ? 'status-open' : 'status-closed';
                    $admin_comment = $row['admin_comment'] ? $row['admin_comment'] : 'No comment yet.';
                    echo "<tr>
                        <td>{$row['issue_type']}</td>
                        <td>{$row['issue_description']}</td>
                        <td class='$statusClass'>{$row['status']}</td>
                        <td>{$row['submitted_at']}</td>
                        <td>{$admin_comment}</td>
                       
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>No issues submitted yet.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>


<!-- Contact Form -->
<div class="form-container">
    <h3>Submit an Issue</h3>
    <?php if (isset($message)) { echo "<div class='alert alert-success' id='successMessage'>$message</div>"; } ?>
    <form action="ContactOffice.php" method="POST" id="issueForm">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $driver_name; ?>" disabled>
        </div>
        <div class="form-group">
            <label for="issue_type">Issue Type</label>
            <select class="form-control" id="issue_type" name="issue_type">
                <option value="I did not receive the payment">I did not receive the payment</option>
                <option value="I finished the trip and status is not updated">I finished the trip and status is not updated</option>
                <option value="New trip is not added still">New trip is not added still</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label for="issue_description">Issue Description</label>
            <textarea class="form-control" id="issue_description" name="issue_description" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
    </form>
</div>
<script>
    document.getElementById("userMenuToggle").addEventListener("click", function() {
        var menu = document.getElementById("userMenu");
        // Toggle the display of the menu
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
    
</script>
<script>
    // Function to hide the success message after 3 seconds
    function hideMessage() {
        setTimeout(function() {
            document.getElementById("successMessage").style.display = "none";
        }, 3000); // 3 seconds
    }

    <?php if (isset($message)) { echo "hideMessage();"; } ?>

    // Prevent submitting the same issue
    document.getElementById('issueForm').addEventListener('submit', function(event) {
        var issueType = document.getElementById('issue_type').value;
        var submitBtn = document.getElementById('submitBtn');
        
        // Disable the submit button temporarily to prevent multiple submissions
        submitBtn.disabled = true;
        
        // Check if the issue type has already been submitted
        <?php if (isset($check_result) && $check_result->num_rows > 0) { ?>
            alert("You have already submitted this issue. Please wait for a response.");
            event.preventDefault(); // Prevent form submission
        <?php } ?>
    });
</script>

</body>
</html>
