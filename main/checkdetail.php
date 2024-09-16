<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cinemadb";
$tmdb_bearer_token = "eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0"; // Add your TMDB Bearer Token here

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to check the status
function check_status($conn, $crew_id) {
    $stmt = $conn->prepare("SELECT status FROM crew WHERE crew_id = ?");
    $stmt->bind_param("i", $crew_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();
    return $status;
}

// Function to load data from TMDB
function load_from_tmdb($crew_id, $tmdb_bearer_token) {
    $url = "https://api.themoviedb.org/3/person/" . urlencode($crew_id);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tmdb_bearer_token,
        'accept: application/json',
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Function to save the data from TMDB to the database
function save_data_from_tmdb_to_db($conn, $crew_id, $data) {
    // Example: Assuming your_table has columns name, biography, etc.
    $stmt = $conn->prepare("UPDATE crew SET biography = ?, birthday = ?, deathday = ?, imdbcrew = ?, birthplace = ?, poster = ?,  status = 1 WHERE crew_id = ?");
    $stmt->bind_param("ssssssi", $data['biography'], $data['birthday'], $data['deathday'], $data['imdb_id'], $data['place_of_birth'], $data['profile_path'], $crew_id);
    $stmt->execute();
    $stmt->close();
}

// Check if crew_id is set in GET request
if (isset($_GET['crew_id'])) {
    $crew_id = $_GET['crew_id'];

    // Main logic
    if (check_status($conn, $crew_id) == 0) {
        $data = load_from_tmdb($crew_id, $tmdb_bearer_token);
        save_data_from_tmdb_to_db($conn, $crew_id, $data);
        header('Location: crewdetail.php?crew_id=' . $crew_id);
        exit();
    } else {
        header('Location: crewdetail.php?crew_id=' . $crew_id);
        exit();
    }
} else {
    echo "crew_id not set.";
}

$conn->close();
?>
