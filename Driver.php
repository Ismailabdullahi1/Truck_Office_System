<?php
// Database configuration
$host = 'localhost';  // Database host
$dbname = 'users';    // Database name
$username = 'root';   // Default MySQL username in XAMPP
$password = 'drivers';       // Default password for MySQL in XAMPP

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$errorMsg = $successMsg = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($confirm_password)) {
        $errorMsg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errorMsg = "Passwords do not match.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if the email already exists
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $errorMsg = "Email is already registered.";
        } else {
            // Insert the new user into the database
            $sql = "INSERT INTO users (full_name, email, phone_number, password, role) VALUES ('$full_name', '$email', '$phone_number', '$hashed_password', 'driver')";
            if ($conn->query($sql) === TRUE) {
                $successMsg = "Registration successful!";
            } else {
                $errorMsg = "Error: " . $conn->error;
            }
        }
    }
}
$conn->close();
?>
