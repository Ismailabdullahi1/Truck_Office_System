<?php
session_start();
require 'db.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin_Login.php");
    exit;
}

// Check if the delete action is triggered
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Delete the trip from the database
    $deleteQuery = "DELETE FROM trips WHERE trip_no = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("s", $deleteId);

    if ($stmt->execute()) {
        header("Location: trip_reports.php"); // Redirect to the trip reports page after deletion
        exit;
    } else {
        echo "Error deleting the trip. Please try again.";
    }

    $stmt->close();
} else {
    echo "No trip ID specified.";
}
?>
