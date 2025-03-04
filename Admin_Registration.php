<?php
session_start();
require 'db.php';


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phoneNumber = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Form validation
    if (empty($fullName) || empty($email) || empty($phoneNumber) || empty($address) || empty($password) || empty($confirmPassword)) {
        $errorMsg = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format!";
    } elseif ($password !== $confirmPassword) {
        $errorMsg = "Passwords do not match!";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO admins (full_name, email, phone_number, address, password, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssss", $fullName, $email, $phoneNumber, $address, $hashedPassword);

        if ($stmt->execute()) {
            $successMsg = "You are waiting for approval. You will receive an email for the approval.";
        } else {
            $errorMsg = "Error: " . $stmt->error;
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
    <title>Sahal Trucks - Admin Registration </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Main.css">
</head>
<body>
<header class="header">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="Driver.html">
                <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" alt="Sahal Trucks Logo" style="max-height: 40px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="Driver.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#help">Help</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div id="loginBox" class="signup-box">
    <h2>Admin Registration</h2>
    <form id="registrationForm" method="post" action="Admin_Registration.php">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone_number" placeholder="Phone Number" required>
        <input type="text" name="address" placeholder="Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button id="registerButton" type="submit">Submit</button>
        

        <!-- Display success or error messages -->
        <div class="message">
            <?php
            if (!empty($successMsg)) {
                echo "<p class='success'>$successMsg</p>";
            }
            if (!empty($errorMsg)) {
                echo "<p class='error'>$errorMsg</p>";
            }
            ?>
        </div>
        <div class="small-text">
        Already have an account? <a href="Driver_Login.php">Login</a>
    </div>
    </form>
</div>

<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- Logo Section -->
            <div class="col-md-3 col-sm-12">
                <div class="footer-logo">
                    <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" alt="Sahal Trucks Logo" class="img-fluid footer-logo-img">
                </div>
            </div>

            <!-- About Section -->
           

            <!-- Quick Links Section -->
            <div class="col-md-3 col-sm-12">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="#"><i class="fas fa-phone-alt"></i> Contact</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> Help</a></li>
                </ul>
            </div>

            <!-- Contact and Location Section -->
            <div class="col-md-3 col-sm-12">
                <h5>Contact & Location</h5>
                <p><i class="fas fa-map-marker-alt"></i> 123 Trucking St, City, Country</p>
                <p><i class="fas fa-envelope"></i> support@sahaltrucks.com</p>
                <p><i class="fas fa-phone"></i> +123 456 7890</p>
            </div>
        </div>

        <div class="row">
            <!-- Social Media Section -->
            <div class="col-12 text-center mt-4">
                <h5>Follow Us</h5>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

