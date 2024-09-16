<?php 
// Database configuration
$host = 'localhost';
$db = 'cinemadb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Function to establish a database connection
function getDatabaseConnection($dsn, $user, $pass, $options) {
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        return null;
    }
}

function addToUserHistory($userId, $movieId, $timestamp, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_history (user_id, imdbid, timestamp)
            VALUES (:user_id, :imdbid, :timestamp)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'imdbid' => $movieId,
            'timestamp' => $timestamp
        ]);
        return true;  // Return true if insertion was successful
    } catch (PDOException $e) {
        echo "Insertion failed: " . $e->getMessage();
        return false;  // Return false if there was an error
    }
}



// Function to get movie details from the database
function getMovieDetails($imdbID, $pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM movies WHERE imdbid = ?');
        $stmt->execute([$imdbID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo 'Query failed: ' . $e->getMessage();
        return null;
    }
}

// Function to get movie genres from the database
function getMovieGenre($imdbID, $pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT mg.*, g.name 
            FROM movie_genres mg 
            JOIN genres g ON mg.genre_id = g.genre_id 
            WHERE mg.imdbid = ? 
            ORDER BY mg.genre_id
        ');
        $stmt->execute([$imdbID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo 'Query failed: ' . $e->getMessage();
        return null;
    }
}

// Function to convert minutes to "1 hr ... mins" format
function convertMinutesToHoursAndMinutes($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf("%d hr %02d mins", $hours, $mins);
}

// Function to extract the first country from a comma-separated list
function getFirstCountry($countryList) {
    $countries = explode(',', $countryList);
    return trim($countries[0]);
}

// Function to get cast details from the database
function getCast($imdbID, $pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT cm.*, c.name, c.poster, c.status
            FROM crew_movie cm
            JOIN crew c ON cm.crew_id = c.crew_id
            WHERE cm.imdbid = ? AND cm.personnel = \'cast\'
            ORDER BY cm.point
        ');
        $stmt->execute([$imdbID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo 'Query failed: ' . $e->getMessage();
        return null;
    }
}

// Function to get crew details from the database
function getCrew($imdbID, $pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT cm.*, c.name, c.poster, c.status
            FROM crew_movie cm
            JOIN crew c ON cm.crew_id = c.crew_id
            WHERE cm.imdbid = ? AND cm.personnel = \'crew\'
            ORDER BY cm.point
        ');
        $stmt->execute([$imdbID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo 'Query failed: ' . $e->getMessage();
        return null;
    }
}

// Function to generate movie details HTML
function generateMovieDetailsHTML($movieDetails, $movieGenre) {
    $firstCountry = getFirstCountry($movieDetails['country']);
    $runtime = convertMinutesToHoursAndMinutes($movieDetails['runtime']);
    $genres = '';
    foreach ($movieGenre as $genre) {
        $genres .= '<p>' . htmlspecialchars($genre['name']) . '</p>';
    }
    return <<<HTML
    <div class="movieDetail">
        <div class="imageContainer">
            <img src="{$movieDetails['poster']}" alt="Movie Poster">
            <div class='playbutton'><button onclick="embedMovie()">Play</button></div>
        </div>
        <div class="metaContainer">
            <div class="metaContainer1">
                <h1>{$movieDetails['title']}</h1>
                <p>Directed by: {$movieDetails['director']}</p>
                <!--<button id="reportButton">Report just for test </button>-->
            </div>
            <div class="metaContainer2">
                <p>{$movieDetails['year']}</p>
                <p>{$runtime}</p>
                <p>{$firstCountry}</p>
            </div>
            <div class="metaContainer4">
                {$genres}
            </div>
            <div class="metaContainer5">
                <p>Rating: {$movieDetails['imdbrating']}</p>
                <p>Voting: {$movieDetails['numvote']}</p>
            </div>
            <div class="metaContainer3">
                <h1>Overview</h1>
                <p>{$movieDetails['plot']}</p>
            </div>
        </div>
    </div>
HTML;
}

// Function to generate cast HTML
function generateCastHTML($cast) {
    $castHTML = '<div class="castContainer2">';
    $image_base_url = "https://image.tmdb.org/t/p/w500";
    foreach ($cast as $member) {
        $poster = ($member['status'] == 1) ? $image_base_url . htmlspecialchars($member['poster']) : "test.png";
        $castHTML .= <<<HTML
        <div class="cast">
            <a href="checkdetail.php?crew_id={$member['crew_id']}">
                <div class="castimg">
                    <img src="{$poster}" alt="Cast Picture">
                </div>
                <div class="castname">
                    <h3>{$member['name']}</h3>
                    <p>as {$member['role']}</p>
                </div>
            </a>
        </div>
HTML;
    }
    $castHTML .= '</div>';
    return $castHTML;
}

// Function to generate crew HTML
function generateCrewHTML($crew) {
    $crewHTML = '<div class="crewContainer2">';
    $image_base_url = "https://image.tmdb.org/t/p/w500";
    foreach ($crew as $member) {
        $poster = ($member['status'] == 1) ? $image_base_url . htmlspecialchars($member['poster']) : "test.png";
        $crewHTML .= <<<HTML
        <div class="crew">
            <a href="checkdetail.php?crew_id={$member['crew_id']}">
                <div class="crewimg">
                    <img src="{$poster}" alt="Crew Picture">
                </div>
                <div class="crewname">
                    <h3>{$member['name']}</h3>
                    <p>as {$member['role']}</p>
                </div>
            </a>
        </div>
HTML;
    }
    $crewHTML .= '</div>';
    return $crewHTML;
}

// Main logic to fetch movie details and genres if IMDb ID is provided
if (isset($_GET['imdbID'])) {
    $imdbID = htmlspecialchars($_GET['imdbID']);
    $pdo = getDatabaseConnection($dsn, $user, $pass, $options);
    
    if ($pdo) {
        $movieDetails = getMovieDetails($imdbID, $pdo);
        $movieGenre = getMovieGenre($imdbID, $pdo);
        $cast = getCast($imdbID, $pdo);
        $crew = getCrew($imdbID, $pdo);
        
        if ($movieDetails) {
            $movieDetailsHTML = generateMovieDetailsHTML($movieDetails, $movieGenre);
            $castHTML = generateCastHTML($cast);
            $crewHTML = generateCrewHTML($crew);
        } else {
            $movieDetailsHTML = '<p style="color:white;">No movies found.</p>';
            $castHTML = '';
            $crewHTML = '';
        }
    } else {
        $movieDetailsHTML = '<p style="color:white;">No Database Connection.</p>';
        $castHTML = '';
        $crewHTML = '';
    }
} else {
    header("Location: home.php");
    exit; // Exit script
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>
    <link rel="stylesheet" href="moviedetail.css">
</head>
<body>
<div class="wholePage">
    <!-- Background image and overlay -->
    <div class="backgroundPicture" 
        style="position: fixed; 
        background-position: center center;
        width: 100%; height: 100%;
        background-size:cover;
        background-repeat: no-repeat;
        z-index: -9999;
        transform: scale(0.5);">
        <img src="<?= htmlspecialchars($movieDetails['poster']); ?>" alt="Movie Poster">
    </div>
    <div class="reduceOpacity" style="position: fixed; background-color: #353232cc;width: 100%; height: 100%;background-size: cover;background-position: center center;background-repeat: no-repeat;z-index: -9998;"></div>

    <!-- Navigation and search box -->
    <?php include("main-nav.php"); ?>
    <?php include('searchbox.php'); ?>
    
    

    

    <div class="movie-iframe-container" id="movie-iframe-container">
        <button class="close-button" onclick="closeIframe()">X</button>
        <iframe id="movie-iframe" class="movie-iframe" allowfullscreen></iframe>
    </div>

    <!-- Movie details section -->
    <?= $movieDetailsHTML; ?>

    <!-- Cast details section -->
    <div class="castDetail">
        <div class="castContainer1">
            <h1>Cast</h1>
            <div class="scroll">
                <button onclick="scrollCast(-500)">&lt;</button>
                <button onclick="scrollCast(500)">&gt;</button>
            </div>
        </div>
        <?= $castHTML; ?>
    </div>

    <!-- Crew details section -->
    <div class="crewDetail">
        <div class="crewContainter1">
            <h1>Crew</h1>
        </div>
        <?= $crewHTML; ?>
    </div>
</div>

<!-- Popup Modal -->
<div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Report Issue</h2>
            <form id="reportForm">
                </select>
                <label for="report_details">Report Details:</label>
                <textarea id="report_details" name="report_details" rows="4" required></textarea>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="report_type" value="movie">
                <button type="submit">Submit Report</button>
            </form>
        </div>
    </div>

<?php 
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $movieId =  $imdbID;
    $timestamp = date('Y-m-d H:i:s');  // Current date and time
    addToUserHistory ($userId, $movieId, $timestamp, $pdo);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "cinemadb";
    
        $conn = new mysqli($servername, $username, $password, $dbname);
    
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    
        // Get form data
    $userId = $_POST['user_id'];
    $movieId = $imdbID;
    $reportType = $_POST['report_type'];
    $reportDetails = $_POST['report_details'];
    $timestamp = date("Y-m-d H:i:s");
    $status = 'Pending';
    
    $sql = "INSERT INTO reports (user_id, movie_id, report_type, report_details, timestamp, status) 
            VALUES ('$userId', '$movieId', '$reportType', '$reportDetails', '$timestamp', '$status')";
    
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true]);
        } else {
            error_log("Error: " . $conn->error); // Log the error
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }
?> 

<script>
    function scrollCast(scrollAmount) {
        const castContainer = document.querySelector('.castContainer2');
        castContainer.scrollLeft += scrollAmount;
    }

    function embedMovie() {
        const movieId = '<?= $movieDetails['imdbid']; ?>';
        const iframe = document.getElementById('movie-iframe');
        const iframeContainer = document.getElementById('movie-iframe-container');
        iframe.src = `https://vidsrc.xyz/embed/movie/${movieId}`;
        iframeContainer.style.display = 'block';
        document.body.classList.add('noscroll');
    }

    function closeIframe() {
        const iframe = document.getElementById('movie-iframe');
        const iframeContainer = document.getElementById('movie-iframe-container');
        iframe.src = '';
        iframeContainer.style.display = 'none';
        document.body.classList.remove('noscroll');
    }

    document.addEventListener("DOMContentLoaded", function() {
            // Get the modal
            var modal = document.getElementById("reportModal");

            // Get the button that opens the modal
            var btn = document.getElementById("reportButton");

            // Get the <span> element that closes the modal
            var span = document.getElementsByClassName("close")[0];

            // When the user clicks the button, open the modal
            btn.onclick = function() {
                modal.style.display = "block";
            }

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = "none";
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            // Handle form submission
            var form = document.getElementById("reportForm");
            form.onsubmit = function(event) {
                event.preventDefault(); // Prevent the default form submission

                // Get form data
                var formData = new FormData(form);

                // Send data to the server using fetch API
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Report submitted successfully!");
                        modal.style.display = "none";
                        form.reset();
                    } else {
                        alert("There was an error submitting your report.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("There was an error submitting your report.");
                });
            }
        });

</script>

</body>
</html>
