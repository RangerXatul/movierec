<?php
include("../user_session.php");

// Restrict access to user_id 1
if ($user_details['user_id'] != 1) {
    header("Location:../dashboard.php");
    exit();
}

if (isset($_GET['crew_id'])) {
    $crewID = $_GET['crew_id'];

    // Fetch crew details from TMDb and insert or update the database
    fetchAndUpdateCrewDetails($crewID);
    echo "Crew data updated successfully.";

    // Redirect to the configuration page
    header("Location: ../configuration.php");
    exit();
}

function fetchAndUpdateCrewDetails($crewID) {
    $tmdbBearerToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';

    $conn = new mysqli("localhost", "root", "", "cinemadb");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch crew details from TMDb
    $crewDetails = fetchCrewDetailsFromTMDb($crewID, $tmdbBearerToken);

    if ($crewDetails) {
        // Prepare and execute the statement for inserting or updating crew details
        $stmt = $conn->prepare("
            INSERT INTO crew (crew_id, name, biography, birthday, deathday, imdbcrew, birthplace, poster, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                biography = VALUES(biography),
                birthday = VALUES(birthday),
                deathday = VALUES(deathday),
                imdbcrew = VALUES(imdbcrew),
                birthplace = VALUES(birthplace),
                poster = VALUES(poster),
                status = VALUES(status)
        ");
        
        // Bind parameters
        $stmt->bind_param("ssssssssi", 
            $crewID, 
            $crewDetails['name'], 
            $crewDetails['biography'], 
            $crewDetails['birthday'], 
            $crewDetails['deathday'], 
            $crewDetails['imdb_id'], 
            $crewDetails['place_of_birth'], 
            $crewDetails['profile_path'], 
            $status
        );
        
        // Set status to 0 before update
        $status = 0;
        $stmt->execute();

        // Update status to 1 after the data is successfully inserted/updated
        $stmt->close();
        
        // Update status to 1
        $stmtStatus = $conn->prepare("
            UPDATE crew 
            SET status = 1 
            WHERE crew_id = ?
        ");
        $stmtStatus->bind_param("s", $crewID);
        $stmtStatus->execute();
        $stmtStatus->close();
    }

    $conn->close();
}

function fetchCrewDetailsFromTMDb($crewID, $token) {
    $url = "https://api.themoviedb.org/3/person/" . urlencode($crewID);
    return fetchApiData($url, $token);
}

function fetchApiData($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'accept: application/json',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
