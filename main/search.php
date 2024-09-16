<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Search Results</title>
    <link rel="stylesheet" href="searchpage.css">
</head>
<body>
<?php
include('main-nav.php');
include('searchbox.php');

// Check if title is set
if (isset($_GET['title'])) {
    $title = $_GET['title'];
    searchMovie($title);
}

// OMDB and TMDB API keys
$omdbApiKey = '47ec6d8e';
$tmdbBearerToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';

// Function to search for a movie by title using OMDB API
function searchMovie($title) {
    $omdbApiKey = '47ec6d8e';
    $omdbUrl = "http://www.omdbapi.com/?apikey=$omdbApiKey&s=" . urlencode($title);
    $omdbResponse = file_get_contents($omdbUrl);
    $omdbData = json_decode($omdbResponse, true);

    if ($omdbData['Response'] == 'True') {
        echo "<div class='container11'>";
        foreach ($omdbData['Search'] as $movie) {
            echo "<div class='movie'>";
            echo "<img src='" . $movie['Poster'] . "' alt='Movie Poster'>";
echo "<h2>" . $movie['Title'] . "</h2>";
echo "<p>Year: " . $movie['Year'] . "</p>";
echo "<form method='GET' action='savedb.php'>";
echo "<input type='hidden' name='imdbID' value='" . $movie['imdbID'] . "'>";
echo "<input type='submit' name='view' value='View'>";
echo "</form>";
echo "</div>";
        }
        echo "</div>"; // .container
    } else {
        echo "<p>No movies found!</p>";
    }

    // JavaScript to log OMDB data to console
    echo "<script>";
    echo "console.log('OMDB Data:', " . json_encode($omdbData) . ");";
    echo "</script>";
}
?>

<!-- Loading Screen -->
<div id="loadingScreen" class="loading-screen">
    <div class="spinner"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const forms = document.querySelectorAll("form");

    forms.forEach(function(form) {
        form.addEventListener("submit", function() {
            document.getElementById("loadingScreen").style.display = "flex";
        });
    });
});
</script>
</body>
</html>