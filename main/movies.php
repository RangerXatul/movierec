<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1c1c1c;
        }
        .container77 {
            padding: 20px;
            max-width: 1200px;
            margin: 0px auto; 
        }
        .movie-card {
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin: 10px;
            width: 225px;
            text-align: center;
        }
        .movie-card img {
            width: 100%;
            height: 320px;
            border-radius:10px;
        }
        .movie-info {
            padding: 10px;
        }
        .movie-info h3 {
            height: 85px;
            color: white;
        }
        .movie-info h3, .movie-info p {
            margin: 5px 0;
        }
        .movie-info .year {
            color: #bbbbbb;
        }
        .movie-info button {
            background-color: #ff6f00;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 5px;
        }
        .movie-info button:hover {
            background-color: #ff8f00;
        }
        .section-title {
            color: white;
            text-align: center;
            margin-top: 20px;
            width: 100%;
            text-align: left;
            margin-left: 50px;
        }

        .alphabet-nav {
    position: fixed;
    right: 0;
    top: 520px;
    transform: translateY(-50%);
    background-color: rgb(33 30 30 / 0%);
    padding: 10px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    white-space: nowrap;
    width: 50px; /* Adjust the width as needed */
    height: 710px; /* Adjust the height as needed */
}

.alphabet-nav::-webkit-scrollbar {
    display: none;
}

.alphabet-nav a {
    display: inline-block;
    color: white;
    padding: 5px;
    text-align: center;
    text-decoration: none;
    font-size: 16px;
    margin-right: 5px; /* Space between letters */
}
        .alphabet-section {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            width: fit-content;
        }
    </style>
</head>
<body>
    <?php
    include('main-nav.php');
    include('searchbox.php');

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cinemadb";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if delete request is sent via POST
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
        $movie_id = $_POST['delete'];
        // Escape the movie_id to prevent SQL injection (assuming imdbid is a string type)
        $movie_id = $conn->real_escape_string($movie_id);

        // SQL to delete record, using imdbid as a string value
        $sql_delete = "DELETE FROM movies WHERE imdbid = '$movie_id'";
        if ($conn->query($sql_delete) === TRUE) {
            echo "<script>alert('Movie deleted successfully');</script>";
        } else {
            echo "<script>alert('Error deleting movie: " . $conn->error . "');</script>";
        }
    }

       // Query to fetch all movies sorted alphabetically (A-Z)
    $sql_az = "SELECT * FROM movies ORDER BY title ASC";
    $result_az = $conn->query($sql_az);

    // Group movies by their first letter
    $movies_by_letter = [];
    while ($row = $result_az->fetch_assoc()) {
        $first_letter = strtoupper($row['title'][0]);
        if (!isset($movies_by_letter[$first_letter])) {
            $movies_by_letter[$first_letter] = [];
        }
        $movies_by_letter[$first_letter][] = $row;
    }
    ?>

    <div class="container77">
        <?php
        foreach ($movies_by_letter as $letter => $movies) {
            echo '<div class="alphabet-section" id="section-' . $letter . '">';
            echo '<h3 class="section-title">' . $letter . '</h3>';
            foreach ($movies as $movie) {
                echo '<div class="movie-card">';
                echo '<img src="' . htmlspecialchars($movie['poster']) . '" alt="Movie Poster">';
                echo '<div class="movie-info">';
                echo '<h3>' . htmlspecialchars($movie['title']) . '</h3>';
                echo '<p class="year">Year: ' . htmlspecialchars($movie['year']) . '</p>';

                if ($user_id == 1) {
                    echo '<form action="movies.php" method="post" onsubmit="return confirm(\'Are you sure?\');">';
                    echo '<input type="hidden" name="delete" value="' . $movie['imdbid'] . '">';
                    echo '<button type="submit">Delete</button>';
                    echo '</form>';
                }
                echo '<form action="moviedetails.php" method="get">';
                echo '<input type="hidden" name="imdbID" value="' . $movie['imdbid'] . '">';
                echo '<button type="submit" style="background-color: #2196F3; color: white; border: none; padding: 10px 20px; margin: 10px 0; cursor: pointer; border-radius: 5px;">View</button>';
                echo '</form>';

                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>

    <div class="alphabet-nav">
    <?php
        foreach ($movies_by_letter as $letter => $movies) {
            echo '<a href="#section-' . $letter . '">' . $letter . '</a>';
        }
        ?>
    </div>

    <?php
    // Close MySQL connection
    $conn->close();
    ?>

    <script>
        document.querySelectorAll('.alphabet-nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
