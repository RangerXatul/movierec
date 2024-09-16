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
        SELECT imdbid, timestamp
        FROM user_history
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCrewAndCast($movieId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT c.name, cm.crew_id, cm.role
        FROM crew_movie cm
        JOIN crew c ON cm.crew_id = c.crew_id
        WHERE cm.imdbid = :imdbid AND cm.personnel = :personnel
    ");
    $stmt->execute([
        'imdbid' => $movieId,
        'personnel' => 'cast'
    ]);
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

function getMoviesByCrewId($crewId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT cm.imdbid, m.title, cm.personnel, m.poster, m.imdbrating
        FROM crew_movie cm
        JOIN movies m ON cm.imdbid = m.imdbid
        WHERE cm.crew_id = :crew_id
    ");
    $stmt->execute(['crew_id' => $crewId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCommonCrewIds($userId, $pdo) {
    $userHistory = getUserHistory($userId, $pdo);
    $movieSets = [];
    $weights = [];

    // Retrieve crew_ids and decay weights for all movies in user history
    foreach ($userHistory as $history) {
        $movieId = $history['imdbid'];
        $timestamp = $history['timestamp'];
        $crewAndCast = getCrewAndCast($movieId, $pdo);
        $movieSets[$movieId] = array_column($crewAndCast, 'crew_id');
        $weights[$movieId] = calculateDecayWeight($timestamp);
    }

    $commonCrewIds = [];
    $crewIdWeights = [];

    // Find common crew_ids using Jaccard Similarity between all pairs of movies in user history
    foreach ($movieSets as $movieIdA => $setA) {
        foreach ($movieSets as $movieIdB => $setB) {
            if ($movieIdA !== $movieIdB) {
                $jaccardSimilarity = calculateJaccardSimilarity($setA, $setB);
                // Calculate the weighted similarity
                $weightA = $weights[$movieIdA];
                $weightB = $weights[$movieIdB];
                $similarity = $jaccardSimilarity * ($weightA + $weightB) / 2;

                // Store common crew_ids with their accumulated weights
                foreach (array_intersect($setA, $setB) as $crewId) {
                    if (!isset($crewIdWeights[$crewId])) {
                        $crewIdWeights[$crewId] = 0;
                    }
                    $crewIdWeights[$crewId] += $similarity;
                }
            }
        }
    }

    // Prepare the result array
    $result = [];
    foreach ($crewIdWeights as $crewId => $weight) {
        if ($weight > 0) {
            $result[] = [
                'crew_id' => $crewId,
                'weight' => $weight
            ];
        }
    }

    // Sort the result by weight in descending order
    usort($result, function($a, $b) {
        return $b['weight'] <=> $a['weight'];
    });

    return $result;
}

function getMoviesForCrewIds($crewIds, $pdo) {
    $moviesByCrewId = [];
    foreach ($crewIds as $crewId) {
        $movies = getMoviesByCrewId($crewId, $pdo);
        if ($movies) {
            $moviesByCrewId[$crewId] = $movies;
        }
    }
    return $moviesByCrewId;
}

function getCrewNameById($crewId, $pdo) {
    static $crewNameCache = [];
    if (isset($crewNameCache[$crewId])) {
        return $crewNameCache[$crewId];
    }

    $stmt = $pdo->prepare("
        SELECT name
        FROM crew
        WHERE crew_id = :crew_id
    ");
    $stmt->execute(['crew_id' => $crewId]);
    $crew = $stmt->fetch(PDO::FETCH_ASSOC);
    $crewNameCache[$crewId] = $crew ? $crew['name'] : 'Unknown';
    return $crewNameCache[$crewId];
}

session_start(); // Ensure session is started

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $pdo = getDatabaseConnection();
    $commonCrewIds = getCommonCrewIds($userId, $pdo);

    // Extract crew_ids from the commonCrewIds result
    $crewIds = array_column($commonCrewIds, 'crew_id');
    $moviesByCrewId = getMoviesForCrewIds($crewIds, $pdo);

    // Get user's watched movies to exclude them from the final result
    $userHistory = getUserHistory($userId, $pdo);
    $watchedMovieIds = array_column($userHistory, 'imdbid');

    // Combine crew IDs with their movies, excluding already watched movies
    $result = [];
    $processedMovies = [];

// Initialize the result array
$result = [];

// Limit the number of crew members to 6
$maxCrewMembers = 6;

foreach (array_slice($commonCrewIds, 0, $maxCrewMembers) as $crew) {
    $crewId = $crew['crew_id'];
    
    // Filter out watched or processed movies
    $filteredMovies = array_filter($moviesByCrewId[$crewId] ?? [], function($movie) use ($watchedMovieIds, &$processedMovies) {
        if (in_array($movie['imdbid'], $watchedMovieIds) || in_array($movie['imdbid'], $processedMovies)) {
            return false;
        }
        $processedMovies[] = $movie['imdbid'];
        return true;
    });

    // Reset keys in the filtered array
    $filteredMovies = array_values($filteredMovies);

    // Add the crew member details to the result array
    $result[] = [
        'crew_id' => $crewId,
        'name' => getCrewNameById($crewId, $pdo),
        'movies' => array_map(function($movie) {
            return [
                'imdbid' => $movie['imdbid'],
                'title' => $movie['title'],
                'role' => $movie['personnel'],
                'rating' => $movie['imdbrating'],
                'poster' => $movie['poster']
            ];
        }, $filteredMovies)
    ];
}

// Now $result contains only up to 6 crew members



    // Send the JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    // Send error message in JSON format
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
}
?>
