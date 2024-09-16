<?php
session_start();

// Check if user is already logged in, redirect to dashboard if true
if(isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Handle login form submission
if(isset($_POST['login'])) {
    // Get input data
    $usernameOrEmail = $_POST['usernameOrEmail'];
    $password = $_POST['password'];

    // Connect to database (replace with your database connection details)
    $conn = new mysqli("localhost", "root", "", "cinemadb");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare SQL statement to fetch user data
    $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // User found, verify password
        $stmt->bind_result($user_id, $password_hash);
        $stmt->fetch();
        if (password_verify($password, $password_hash)) {
            // Password correct, start session and set session variables
            $_SESSION['user_id'] = $user_id;

            // Redirect to dashboard
            header("Location: ../main/home.php");
            exit();
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "User not found.";
    }

    // Close statement and database connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="process.css">
</head>
<body>
    <?php include 'process-nav.php'; ?>
    <div class="container1">
        <h2>User Login</h2>
        <form action="login.php" method="POST">
            <?php
            if(isset($error_message)) {
                echo '<p>' . $error_message . '</p>';
            }
            ?>
            <div class="form-group">
                <label for="usernameOrEmail">Username or Email:</label>
                <input type="text" id="usernameOrEmail" name="usernameOrEmail" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
