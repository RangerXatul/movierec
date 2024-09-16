<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location:../process/login.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = mysqli_connect("localhost", "root", "", "cinemadb");

function getUserDetails($user_id) {
    // Replace this with your actual database connection and query logic
    $conn = mysqli_connect("localhost", "root", "", "cinemadb");

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql = "SELECT * FROM users WHERE user_id='$user_id'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row;
    } else {
        return false;
        
    }
    
}
mysqli_close($conn);


// Example usage to fetch more user details
$user_details = getUserDetails($user_id);
if($user_details==false){
    header("Location:../process/logout.php"); 
}
$_SESSION["user_id"] = $user_id
?>