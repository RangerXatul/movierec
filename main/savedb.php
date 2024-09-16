<?php

if (isset($_GET['view'])) {
    $imdbID = $_GET['imdbID'];
    if (checkItemdb($imdbID)) {
       header("Location: moviedetails.php?imdbID=$imdbID");  
    } else {
        viewMoreDetails($imdbID);
        header("Location: moviedetails.php?imdbID=$imdbID");
    }
    exit();
}

function checkItemdb($imdbID){
    $conn = new mysqli("localhost", "root", "", "cinemadb");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $query = "SELECT * FROM movies WHERE imdbid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $imdbID);
    $stmt->execute();
    $results = $stmt->get_result();
    $exists = ($results->num_rows > 0);

    $stmt->close();
    $conn->close();

    return $exists;
}

function viewMoreDetails($imdbID) {
    $omdbApiKey = '47ec6d8e';
    $tmdbBearerToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';

    $conn = new mysqli("localhost", "root", "", "cinemadb");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $omdbData = fetchOmdbData($imdbID, $omdbApiKey);
    if ($omdbData['Response'] == 'True') {
        $tmdbData = fetchTMDbData($imdbID, $tmdbBearerToken);
        if (!empty($tmdbData['movie_results'])) {
            $tmdbID = $tmdbData['movie_results'][0]['id'];
            $tmdbCredits = fetchTMDbCredits($tmdbID, $tmdbBearerToken);
            $tmdbMovie = fetchTMDbMovie($tmdbID, $tmdbBearerToken);

            if (!empty($tmdbCredits)) {
                insertMovieDetails($conn, $imdbID, $tmdbID, $omdbData, $tmdbMovie);
                insertCrewDetails($conn, $imdbID, $tmdbID, $tmdbCredits, $tmdbBearerToken, 'crew', 10);
                insertCrewDetails($conn, $imdbID, $tmdbID, $tmdbCredits, $tmdbBearerToken, 'cast', 20);
                insertGenres($conn, $imdbID, $tmdbID, $tmdbMovie['genres']);
            }
        }
    }

    $conn->close();
}

function fetchOmdbData($imdbID, $apiKey) {
    $url = "http://www.omdbapi.com/?apikey=$apiKey&i=" . urlencode($imdbID);
    return json_decode(file_get_contents($url), true);
}

function fetchTMDbData($imdbID, $token) {
    $url = "https://api.themoviedb.org/3/find/" . urlencode($imdbID) . "?external_source=imdb_id";
    return fetchApiData($url, $token);
}

function fetchTMDbCredits($tmdbID, $token) {
    $url = "https://api.themoviedb.org/3/movie/" . urlencode($tmdbID) . "/credits";
    return fetchApiData($url, $token);
}

function fetchTMDbMovie($tmdbID, $token) {
    $url = "https://api.themoviedb.org/3/movie/" . urlencode($tmdbID);
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


function insertMovieDetails($conn, $imdbID, $tmdbID, $omdbData, $tmdbMovie) {
    $stmt = $conn->prepare("INSERT INTO movies (imdbid, tmdbid, title, year, plot, poster, runtime, director, country, imdbrating, numvote) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Convert imdbRating and imdbVotes to appropriate types
    $imdbRating = floatval($omdbData['imdbRating']);
    $numVotes = intval(str_replace(',', '', $omdbData['imdbVotes']));

    // Bind the parameters correctly
    $stmt->bind_param("ssssssissdi", $imdbID, $tmdbID, $omdbData['Title'], $omdbData['Year'], $omdbData['Plot'], $omdbData['Poster'], $omdbData['Runtime'], $omdbData['Director'], $omdbData['Country'], $imdbRating, $numVotes);
    
    $stmt->execute();
    $stmt->close();
}


function insertCrewDetails($conn, $imdbID, $tmdbID, $tmdbCredits, $token, $role, $limit) {
    $stmtCrew = $conn->prepare("INSERT INTO crew (crew_id, name, status) VALUES (?, ?, ?)");
    $stmtCrewMovie = $conn->prepare("INSERT INTO crew_movie (crew_id, imdbid, tmdbid, role, point, personnel) VALUES (?, ?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($tmdbCredits[$role] as $member) {
        if ($count == $limit) break;
        
        $memberId = $member['id'];
        $memberName = $member['name'];
        $memberRole = $role == 'crew' ? $member['job'] : $member['character'];

        if (!checkCrewIdExists($conn, $memberId)) {
            $details = fetchCrew($memberId, $token);
            $status = 0;
            $stmtCrew->bind_param("ssi", $memberId, $memberName, $status);
            $stmtCrew->execute();
        }

        $stmtCrewMovie->bind_param("ssssis", $memberId, $imdbID, $tmdbID, $memberRole, $count, $role);
        $stmtCrewMovie->execute();
        $count++;
    }

    $stmtCrew->close();
    $stmtCrewMovie->close();
}

function fetchCrew($crewID, $token) {
    $url = "https://api.themoviedb.org/3/person/" . urlencode($crewID);
    return fetchApiData($url, $token);
}

function checkCrewIdExists($conn, $crewId) {
    $query = "SELECT crew_id FROM crew WHERE crew_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $crewId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function insertGenres($conn, $imdbID, $tmdbID, $genres) {
    foreach ($genres as $genre) {
        $genreID = getOrCreateGenre($conn, $genre['id'], $genre['name']);
            associateMovieGenre($conn, $imdbID, $tmdbID, $genre['id']);
        }
    }

function getOrCreateGenre($conn, $genreID, $genreName) {
    $stmt = $conn->prepare("SELECT genre_id FROM genres WHERE genre_id = ?");
    $stmt->bind_param('i', $genreID);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($existingGenreID);
        $stmt->fetch();
        $stmt->close();
        return $existingGenreID;
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO genres (genre_id, name) VALUES (?, ?)");
        $stmt->bind_param('is', $genreID, $genreName);
        if ($stmt->execute()) {
            return $conn->insert_id;
        } else {
            return false; // Return false on failure
        }
    }
}

function associateMovieGenre($conn, $imdbID, $tmdbID, $genreID) {
    $stmt = $conn->prepare("INSERT INTO movie_genres (imdbid, tmdbid, genre_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $imdbID, $tmdbID, $genreID);
    if (!$stmt->execute()) {
        // Handle error if execute fails
        echo "Error associating movie with genre: " . $stmt->error;
    }
    $stmt->close();
}
?>