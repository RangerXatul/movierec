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
    <title>CinemaDB - Home</title>
    <link rel="stylesheet" href="essential/index.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>CinemaDB</h1>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Movies</a></li>
                    <li><a href="#">Genres</a></li>
                    <li><a href="#">Trending</a></li>
                    <li><a href="process/login.php">Login</a></li>
                    <li><a href="process/signup.php">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container2">
        <form id="searchForm" action="" method="GET">
            <input type="text" name="query" placeholder="Search movies, cast, directors..." required>
            <button type="button" onclick="showLoginModal()">Search</button>
        </form>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>You must login first</h2>
            <button onclick="window.location.href='process/login.php'">Login</button>
            <button onclick="window.location.href='process/signup.php'">Sign Up</button>
        </div>
    </div>

    <div class="content">
        <div class="navigation">
            <a href="index.php?term=day" class="<?= $term === 'day' ? 'active' : '' ?>">Today</a>
            <a href="index.php?term=week" class="<?= $term === 'week' ? 'active' : '' ?>">Week</a>
        </div>
        <div class="movie">
            <?php if ($trendingMovies && isset($trendingMovies['results'])): ?>
                <?php foreach ($trendingMovies['results'] as $movie): ?>
                    <div class="card">
                        <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                        <div class="card-info">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                            <p class="rating">Rating: <?= htmlspecialchars(number_format($movie['vote_average'], 1)) ?></p>
                            <form action="" method="get">
                                <input type="hidden" name="tmdbid" value="<?= $movie['id'] ?>">
                                <button type="button" onclick="showLoginModal()">View</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending movies found.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 CinemaDB. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Function to show login modal
        function showLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        // Close the modal when clicking on the "x" or outside the modal
        var modal = document.getElementById("loginModal");
        var span = document.getElementsByClassName("close")[0];

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
