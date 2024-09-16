<?php
// Recommendation as per movie view history

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

function getMovieDetails($movieId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT m.title, g.genre_id, g.name AS genre_name, m.imdbrating, m.poster
        FROM movies m
        JOIN movie_genres mg ON m.imdbid = mg.imdbid
        JOIN genres g ON mg.genre_id = g.genre_id
        WHERE m.imdbid = :imdbid
    ");
    $stmt->execute(['imdbid' => $movieId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllMovies($pdo) {
    $stmt = $pdo->prepare("SELECT imdbid FROM movies");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function calculateDecayWeight($timestamp) {
    $now = new DateTime();
    $movieTime = new DateTime($timestamp);
    $interval = $now->diff($movieTime);
    $days = $interval->days;
    return exp(-$days / 30);  // Adjust the decay rate as needed
}

function calculateWeightedSimilarity($movieA, $movieB, $weight) {
    $intersect = array_intersect($movieA, $movieB);
    $union = array_unique(array_merge($movieA, $movieB));
    $unionCount = count($union);
    $similarity = $unionCount > 0 ? count($intersect) / $unionCount : 0;  // Jaccard Similarity
    return $similarity * $weight;
}

function getRecommendations($userId, $pdo) {
    $userHistory = getUserHistory($userId, $pdo);
    $movieDetails = [];
    
    foreach ($userHistory as $history) {
        $movieId = $history['imdbid'];
        $timestamp = $history['timestamp'];
        
        if (!isset($movieDetails[$movieId])) {
            $movieDetails[$movieId] = [
                'details' => getMovieDetails($movieId, $pdo),
                'weight' => calculateDecayWeight($timestamp)
            ];
        }
    }

    $allMovies = getAllMovies($pdo);
    $recommendations = [];

    foreach ($allMovies as $movieId) {
        if (!array_key_exists($movieId, $movieDetails)) {
            $details = getMovieDetails($movieId, $pdo);
            $totalSimilarity = 0;
            $decayScores = [];
            $jaccardScores = [];

            foreach ($movieDetails as $userMovie) {
                $userDetails = array_column($userMovie['details'], 'genre_id');
                $detailsList = array_column($details, 'genre_id');
                $weight = $userMovie['weight'];
                $similarity = calculateWeightedSimilarity($userDetails, $detailsList, $weight);
                $totalSimilarity += $similarity;
                $decayScores[] = $weight;
                $jaccardScores[] = $similarity / $weight;
            }

            $ratingWeight = isset($details[0]) ? 1 + ($details[0]['imdbrating'] / 10) : 1; // Default value if no details
            $totalSimilarity *= $ratingWeight;

            $recommendations[$movieId] = [
                'similarity' => $totalSimilarity / (count($userHistory) ?: 1),
                'decayScores' => $decayScores,
                'jaccardScores' => $jaccardScores
            ];
        }
    }

    uasort($recommendations, function ($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    return array_slice($recommendations, 0, 12, true);
}

header('Content-Type: application/json');

session_start(); // Ensure session is started
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $pdo = getDatabaseConnection();
    $recommendations = getRecommendations($userId, $pdo);

    $formattedRecommendations = [];
    foreach ($recommendations as $imdbId => $data) {
        $details = getMovieDetails($imdbId, $pdo);
        if ($details) {
            $formattedRecommendations[] = [
                'imdbid' => $imdbId,
                'title' => $details[0]['title'],
                'poster' => $details[0]['poster'],
                'genres' => array_column($details, 'genre_name'),
                'rating' => $details[0]['imdbrating'],
                'similarity' => number_format($data['similarity'], 2),
                'decayScores' => array_map('number_format', $data['decayScores']),
                'jaccardScores' => array_map('number_format', $data['jaccardScores'])
            ];
        } else {
            error_log("No details found for movie ID: $imdbId");
        }
    }

    echo json_encode($formattedRecommendations);
} else {
    echo json_encode(['error' => 'User not logged in']);
}
?>
