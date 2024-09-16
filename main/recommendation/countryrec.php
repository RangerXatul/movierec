<?php
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=cinemadb", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

function getUserHistory($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT uh.imdbid, m.country, uh.timestamp
        FROM user_history uh
        JOIN movies m ON uh.imdbid = m.imdbid
        WHERE uh.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateDecayWeight($timestamp) {
    $now = new DateTime();
    $movieTime = new DateTime($timestamp);
    $interval = $now->diff($movieTime);
    $totalSeconds = ($interval->days * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

    // Decay rate in seconds (e.g., 1000 seconds = ~16.67 minutes)
    $decayRate = 10;  // Adjust this value for faster decay

    return exp(-$totalSeconds / $decayRate);
}

function calculateJaccardSimilarity($setA, $setB) {
    $intersection = count(array_intersect($setA, $setB));
    $union = count(array_unique(array_merge($setA, $setB)));
    return $union > 0 ? $intersection / $union : 0;
}

function getMoviesByCountry($country, $pdo) {
    $stmt = $pdo->prepare("
        SELECT imdbid, title, poster, imdbrating
        FROM movies
        WHERE FIND_IN_SET(:country, country)
        AND imdbrating IS NOT NULL
    ");
    $stmt->execute(['country' => $country]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

session_start(); // Ensure session is started

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $pdo = getDatabaseConnection();

    // Get user's watched movies to exclude them from the final result
    $userHistory = getUserHistory($userId, $pdo);
    $watchedMovieIds = array_column($userHistory, 'imdbid');
    
    // Calculate country weights based on user's history
    $countryWeights = [];
    foreach ($userHistory as $history) {
        $countries = explode(', ', $history['country']);
        $timestamp = $history['timestamp'];
        $weight = calculateDecayWeight($timestamp);
        foreach ($countries as $country) {
            if (!isset($countryWeights[$country])) {
                $countryWeights[$country] = 0;
            }
            $countryWeights[$country] += $weight;
        }
    }

    // Sort countries by their weights
    arsort($countryWeights);
    $topCountries = array_keys(array_slice($countryWeights, 0, 6, true));

    // Fetch movies for top countries and calculate Jaccard Similarity
    $result = [];
    $processedMovies = [];
    $counter = 1;

    foreach ($topCountries as $country) {
        $countryMovies = getMoviesByCountry($country, $pdo);
        
        $filteredMovies = [];
        foreach ($countryMovies as $movie) {
            if (!in_array($movie['imdbid'], $watchedMovieIds) && !in_array($movie['imdbid'], $processedMovies)) {
                $filteredMovies[] = $movie;
                $processedMovies[] = $movie['imdbid'];
                if (count($filteredMovies) >= 7) {
                    break;
                }
            }
        }

        if (!empty($filteredMovies)) {
            $result[] = [
                'number' => $counter,
                'country' => $country,
                'movies' => array_map(function($movie) {
                    return [
                        'imdbid' => $movie['imdbid'],
                        'title' => $movie['title'],
                        'rating' => $movie['imdbrating'],
                        'poster' => $movie['poster']
                    ];
                }, $filteredMovies)
            ];
            $counter++;
        }
    }

    // Send the JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    // Send error message in JSON format
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
}
?>
