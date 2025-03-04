<?php
session_start();
require 'db.php';

// Form submission handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation
    if (empty($email) || empty($password)) {
        $errorMsg = "All fields are required!";
    } else {
        // Prepare and execute query to check if the driver exists
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If user is found, check password
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the password using password_verify
            if (password_verify($password, $user['password'])) {
                // Store session data after successful login
                $_SESSION['driver_id'] = $user['driver_id']; // Store driver's ID
                $_SESSION['full_name'] = $user['full_name']; // Store driver's full name
                header("Location: DriverDahsboard.php"); // Redirect to driver dashboard
                exit;
            } else {
                $errorMsg = "Incorrect password!";
            }
        } else {
            $errorMsg = "User not found!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahal Trucks - Driver Login</title>
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
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#help">Help</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div id="loginBox" class="signup-box">
    <h2>Driver Login</h2>
    <form id="loginForm" method="post" action="Driver_Login.php">
        <input type="email" id="emailInput" name="email" placeholder="Email" required>
        <input type="password" id="passwordInput" name="password" placeholder="Password" required>
        <button id="loginButton" type="submit">Log In</button>

        <!-- Dynamic message area -->
        <div class="message">
            <?php
            if (!empty($errorMsg)) {
                echo "<p class='error'>$errorMsg</p>";
            }
            ?>
        </div>
    </form>
    <div class="small-text">
        Don't have an account? <a href="driver_SingUp.php">Sign Up</a>
    </div>
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
                    <li><a href="Driver.html"><i class="fas fa-home"></i> Home</a></li>
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
