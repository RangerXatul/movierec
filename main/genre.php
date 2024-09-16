<?php
function getDatabaseConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cinemadb";
    
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

function fetchGenres() {
    $conn = getDatabaseConnection();
    $result = $conn->query("SELECT DISTINCT name FROM genres");
    $genres = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $genres[] = $row['name'];
        }
    }

    $conn->close();
    return $genres;
}

function fetchMoviesByGenre($genre) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("
    SELECT m.title, m.year, m.imdbrating, m.poster, m.imdbid
    FROM movies AS m
    JOIN movie_genres AS mg ON m.tmdbid = mg.tmdbid
    JOIN genres AS g ON mg.genre_id = g.genre_id
    WHERE g.name = ?
    ");
    $stmt->bind_param("s", $genre);
    $stmt->execute();
    $result = $stmt->get_result();
    $movies = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
    }

    $stmt->close();
    $conn->close();
    return $movies;
}

$selected_genre = isset($_GET['selected_genre']) ? $_GET['selected_genre'] : '';
if ($selected_genre) {
    $movies = fetchMoviesByGenre($selected_genre);
} else {
    $conn = getDatabaseConnection();
    $result = $conn->query("SELECT m.title, m.year, m.imdbrating, m.poster, m.imdbid FROM movies AS m");
    $movies = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
    }

    $conn->close();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaDB - Genre</title>
    <style>
body {
    background-color: #1c1c1c;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.sidebar {
    width: 150px;
    position: fixed;
    height: 500px;
    overflow-y: auto;
    margin-left:25px;
}


.sidebar .card {
    display: block;
    width: 100%;
    margin-bottom: 10px;
}

.sidebar::-webkit-scrollbar {
    display: none; /* Hide the scrollbar for WebKit browsers */
}

.card{
    background-color: #1c1c1c;
    color: white; 
}

.content {
    margin-left: 250px; /* Adjust margin to the width of the sidebar */
    padding: 10px;
    min-height:530px;
 }

 .content h1 {
    color: white;
 }

 .movie {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    padding: 20px;
    max-width: 1200px;
    margin: 0px auto; 
}

 .card1{
    background-color: #1e1e1e;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    margin:10px;
    width: 225px;
    text-align: center;
}

.card1 img {
    width: 100%;
    height: 320px;
    border-radius:10px;
}

.card1-info {
    padding: 10px;
}

.card1-info h3{
height:85px;
color:white;

}

.card1-info .rating {
    color: #bbbbbb;
}

.card1-info .year {
    color: #bbbbbb;
}

.card1-info h3, .card1-info p {
    margin: 5px 0;
}
.card1-info button {
    background-color: #ff6f00;
    color: white;
    border: none;
    padding: 10px 20px;
    margin: 10px 0;
    cursor: pointer;
    border-radius: 5px;
}
.card1-info button:hover {
    background-color: #ff8f00;
}

footer {
    background-color: #333;
    color: #fff;
    padding: 20px 0;
    text-align: center;
}

footer .container2 {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

footer p {
    margin: 0;
    font-size: 14px;
    color: #dcdcdc;
}

.emptyData{
    color:white;
}

@media (max-width: 768px) {
    footer .container2 {
        padding: 0 10px;
    }

    footer p {
        font-size: 12px;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
    }
}

    </style>
</head>
<body>
    <?php include("main-nav.php"); ?>
    <?php include("searchbox.php"); ?>

    <div class="sidebar">
        <?php
        $genres = fetchGenres();
        foreach ($genres as $genre) {
            echo "
            <form method='GET' style='display: block;'>
                <input type='hidden' name='selected_genre' value='" . htmlspecialchars($genre) . "'>
                <button type='submit' class='card'>" . htmlspecialchars($genre) . "</button>
            </form>
            ";
        }
        ?>
    </div>

    <div class="content">
        <h1>Movies <?php echo $selected_genre ? "in " . htmlspecialchars($selected_genre) : ''; ?></h1>
        <div class="movie">
            <?php if (empty($movies)): ?>
                <p class='emptyData'>No movies found.</p>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <div class="card1">
    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
    <div class="card1-info">
    <h3><?php echo htmlspecialchars($movie['title']); ?></h3> <!-- Corrected closing tag from h3 to h2 -->
    <p class='year'><?php echo htmlspecialchars($movie['year']); ?></p>
    <p class='rating'><?php echo htmlspecialchars($movie['imdbrating']); ?></p>
    <form method='GET' action='moviedetails.php'>
        <input type='hidden' name='imdbID' value='<?php echo htmlspecialchars($movie['imdbid']); ?>'>
        <button type='submit'>View</button>
    </form>
    </div>
    
</div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div></div>

        
    </div>

    <footer>
        <div class="container2">
            <p>&copy; 2024 CinemaDB. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
