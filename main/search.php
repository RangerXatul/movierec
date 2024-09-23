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

// OMDB API key
$omdbApiKey = '47ec6d8e';

// Function to search for a movie by title using OMDB API
function searchMovie($title) {
    global $omdbApiKey;
    $omdbUrl = "http://www.omdbapi.com/?apikey=$omdbApiKey&s=" . urlencode($title);
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $omdbUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute cURL request
    $omdbResponse = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo "<p>Failed to retrieve data from OMDB: " . curl_error($ch) . "</p>";
        curl_close($ch);
        return;
    }
    
    curl_close($ch);

    $omdbData = json_decode($omdbResponse, true);

    // Check if 'Response' exists and is 'True'
    if (isset($omdbData['Response']) && $omdbData['Response'] == 'True') {
        // Check if 'Search' key exists before iterating
        if (isset($omdbData['Search']) && is_array($omdbData['Search'])) {
            echo "<div class='container11'>";
            foreach ($omdbData['Search'] as $movie) {
                echo "<div class='movie'>";
                // Use placeholder if poster is not available
                $poster = ($movie['Poster'] != "N/A") ? htmlspecialchars($movie['Poster']) : 'placeholder.jpg';
                echo "<img src='" . $poster . "' alt='" . htmlspecialchars($movie['Title']) . " Poster'>";
                echo "<h2>" . htmlspecialchars($movie['Title']) . "</h2>";
                echo "<p>Year: " . htmlspecialchars($movie['Year']) . "</p>";
                echo "<form method='GET' action='savedb.php'>";
                echo "<input type='hidden' name='imdbID' value='" . htmlspecialchars($movie['imdbID']) . "'>";
                echo "<input type='submit' name='view' value='View'>";
                echo "</form>";
                echo "</div>";
            }
            echo "</div>"; // .container11
        } else {
            echo "<p>No movies found!</p>";
        }
    } else {
        echo "<p>No movies found!</p>";
    }

    // JavaScript to log OMDB data to console
    echo "<script>";
    echo "console.log('OMDB Data:', " . json_encode($omdbData) . ");";
    echo "</script>";
}

// Check if title is set and call searchMovie
if (isset($_GET['title']) && !empty($_GET['title'])) {
    $title = htmlspecialchars($_GET['title']);
    searchMovie($title);
} else {
    echo "<p>No movie title provided!</p>";
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
