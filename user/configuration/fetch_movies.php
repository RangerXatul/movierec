<?php
$conn = mysqli_connect("localhost", "root", "", "cinemadb");

// Get search query from GET request
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Prepare SQL query based on search query
$query = empty($search)
    ? "SELECT * FROM movies ORDER BY (poster IS NOT NULL) ASC"
    : "SELECT * FROM movies WHERE title LIKE '%$search%' OR imdbid LIKE '%$search%' OR tmdbid LIKE '%$search%'";

// Execute the query
$result = $conn->query($query);

// Fetch all data
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($movies);

// Close the connection
$conn->close();
?>
