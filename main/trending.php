<?php
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

function fetchTrendingMovie($term, $token) {
    $url = "https://api.themoviedb.org/3/trending/movie/" . urlencode($term);
    return fetchApiData($url, $token);
}

// Assuming you have your API token stored securely
$apiToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';

// Get the term from the URL or default to 'day'
$term = isset($_GET['term']) && in_array($_GET['term'], ['day', 'week']) ? $_GET['term'] : 'day';
$trendingMovies = fetchTrendingMovie($term, $apiToken);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaDB - Trending Movies</title>
    <link rel="stylesheet" href="trending.css">
</head>
<body>
    <?php include("main-nav.php"); include("searchbox.php"); ?>
    <div class="content">
        <div class="navigation">
            <h2>Trending</h2>
            <a href="trending.php?term=day" class="<?= $term === 'day' ? 'active' : '' ?>">Today</a>
            <a href="trending.php?term=week" class="<?= $term === 'week' ? 'active' : '' ?>">Week</a>
        </div>
        <div class="movie">
            <?php if ($trendingMovies && isset($trendingMovies['results'])): ?>
                <?php foreach ($trendingMovies['results'] as $movie): ?>
                    <div class="card"><img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" alt="<?= htmlspecialchars($movie['title']) ?>"><?php
                    echo '<div class="card-info">';
                    echo '<h3>' . htmlspecialchars($movie['title']) . '</h3>';
                    echo '<p class="rating">Rating: ' . htmlspecialchars($movie['vote_average']) . '</p>';
                    echo '<form action="converttoimdb.php" method="get">';
                    echo '<input type="hidden" name="tmdbid" value="' . $movie['id'] . '">';
                    echo '<button type="submit">View</button>';
                    echo '</form>';
        
            echo '</div>';
            echo '</div>';
            ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending movies found.</p>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        <div class="container2">
            <p>&copy; 2024 CinemaDB. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
