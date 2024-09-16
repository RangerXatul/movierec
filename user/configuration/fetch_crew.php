<?php
$conn = mysqli_connect("localhost", "root", "", "cinemadb");

// Check the connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get search query from GET request
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Prepare SQL query based on search query
$query = empty($search)
    ? "SELECT * FROM crew ORDER BY status DESC, poster IS NOT NULL DESC, crew_id DESC "
    : "SELECT * FROM crew WHERE name LIKE '%$search%' OR crew_id LIKE '%$search%'";

// Execute the query
$result = $conn->query($query);

// Fetch all data
$crew = [];
while ($row = $result->fetch_assoc()) {
    $crew[] = $row;
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($crew);

// Close the connection
$conn->close();
?>
