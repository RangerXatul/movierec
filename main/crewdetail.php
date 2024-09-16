<?php
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

function getPDO($dsn, $user, $pass, $options) {
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        return null;
    }
}

// Function to insert data into the interactions table
function insertUserCastHistory($userId, $crew_id, $timestamp, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_casthistory (user_id, crew_id, timestamp)
            VALUES (:user_id, :crew_id, :timestamp)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'crew_id' => $crew_id,
            'timestamp' => $timestamp
        ]);
        return true;  // Return true if insertion was successful
    } catch (PDOException $e) {
        echo "Insertion failed: " . $e->getMessage();
        return false;  // Return false if there was an error
    }
}

function getCrewDetails($pdo, $crew_id) {
    $stmt = $pdo->prepare('SELECT * FROM crew WHERE crew_id = ?');
    $stmt->execute([$crew_id]);
    return $stmt->fetch();
}

function getMovieDetails($pdo, $crew_id) {
    $stmt = $pdo->prepare('
        SELECT cm.imdbid, cm.role, m.title, m.poster, m.year
        FROM crew_movie cm
        JOIN movies m ON cm.imdbid = m.imdbid
        WHERE cm.crew_id = :crew_id
        AND cm.personnel = "cast"
        ORDER BY m.year ASC
    ');
    $stmt->bindParam(':crew_id', $crew_id, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

function fetchMovieCredits($crew_id, $token) {
    $url = "https://api.themoviedb.org/3/person/" . urlencode($crew_id) . "/movie_credits";
    return fetchApiData($url, $token);
}

function calculateAge($birthDate, $deathDate = null) {
    $birthDate = new DateTime($birthDate);
    $currentDate = new DateTime();

    if ($deathDate) {
        $deathDate = new DateTime($deathDate);
        return $deathDate->diff($birthDate)->y;
    } else {
        return $currentDate->diff($birthDate)->y;
    }
}

function formatDateRange($birthDate, $deathDate = null) {
    if ($deathDate) {
        return "(" . $birthDate->format('Y-m-d') . " - " . $deathDate->format('Y-m-d') . ")";
    }
    return $birthDate->format('Y-m-d') . " - Present";
}

function displayMovieDetails($movieDetails) {
    foreach (array_slice($movieDetails, 0, 15) as $member) {
        echo '<div class="cast">
                <a href="moviedetails.php?imdbID=' . htmlspecialchars($member['imdbid']) . '">
                <div class="castimg">
                    <img src="' . htmlspecialchars($member['poster']) . '" alt="Movie Poster">
                </div>
                <div class="castname">
                    <h3>' . htmlspecialchars($member['title']) . '</h3>
                    <p>as ' . htmlspecialchars($member['role']) . '</p>
                </div>
                </a>
            </div>';
    }
}

function displayMovieCredits($movieCredits) {
    if (!empty($movieCredits['cast'])) {
        $movieCredits['cast'] = array_filter($movieCredits['cast'], function($credit) {
            return !empty($credit['release_date']);
        });
        usort($movieCredits['cast'], function($a, $b) {
            return strcmp($b['release_date'], $a['release_date']);
        });

        foreach ($movieCredits['cast'] as $credit) {
            if ($credit['character'] != "Self" && $credit['character'] != "Self (archive footage)") {
                echo "<div class='list'>
                        <div class='year'>" . htmlspecialchars(substr($credit['release_date'], 0, 4)) . "</div>
                        <div class='name'><a href='converttoimdb.php?tmdbid=" . htmlspecialchars($credit['id']) . "'>" . htmlspecialchars($credit['title']) . "</a></div>
                        <div class='role'>as " . htmlspecialchars($credit['character']) . "</div>
                    </div>";
            }
        }
    } else {
        echo "<p>No movie credits found for this person.</p>";
    }
}

$pdo = getPDO($dsn, $user, $pass, $options);

if (isset($_GET['crew_id'])) {
    $crew_id = htmlspecialchars($_GET['crew_id']);
    $crewDetails = getCrewDetails($pdo, $crew_id);
    $movieDetails = getMovieDetails($pdo, $crew_id);
    

    $tmdbBearerToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';
    $movieCredits = fetchMovieCredits($crew_id, $tmdbBearerToken);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>
    <link rel="stylesheet" href="crewdetail.css">
</head>
<body>
<div class="whole-page">
    <div class="backgroundPicture" 
        style="position: fixed; background-position: center center; width: 100%; height: 100%; background-size: cover; background-repeat: no-repeat; z-index: -9999; transform: scale(0.5);">
        <?php if ($crewDetails): ?>
            <?php $image_base_url = "https://image.tmdb.org/t/p/w500"; ?>
            <img src="<?= $image_base_url . htmlspecialchars($crewDetails['poster']) ?>" alt="Movie Poster">
        <?php endif; ?>
    </div>
    <div class="reduceOpacity"
        style="position: fixed; background-color: #353232cc; width: 100%; height: 100%; background-size: cover; background-position: center center; background-repeat: no-repeat; z-index: -9998;"></div>
    <?php include("main-nav.php"); include('searchbox.php'); 
    ?>
    <div class="movieDetail1">
        <div class="imageContainer">
            <?php if ($crewDetails): ?>
                <?php $image_base_url = "https://image.tmdb.org/t/p/w500"; ?>
                <img src="<?= $image_base_url . htmlspecialchars($crewDetails['poster']) ?>" alt="Movie Poster">
            <?php endif; ?>
        </div>
        <div class="metaContainer">
            <div class="metaContainer1">
                <?php if ($crewDetails): ?>
                    <h1><?= htmlspecialchars($crewDetails['name']) ?></h1>
                    <?php
                    $birthDate = $crewDetails['birthday'];
                    $deathDate = $crewDetails['deathday'] ?? null;
                    $age = calculateAge($birthDate, $deathDate);
                    $dateRange = formatDateRange(new DateTime($birthDate), $deathDate ? new DateTime($deathDate) : null);
                    ?>
                    <p><?= $dateRange ?> (<?= htmlspecialchars($age) ?> yrs)</p>
                <?php endif; ?>
            </div>
            <div class="metaContainer2">
                <?php if ($crewDetails): ?>
                    <?php if (!$crewDetails['deathday']): ?>
                        <p>Born: <?= htmlspecialchars($crewDetails['birthday']) ?>, </p>
                    <?php endif; ?>
                    <p><?= htmlspecialchars($crewDetails['birthplace']) ?></p>
                <?php endif; ?>
            </div>
            <div class="metaContainer3">
                <?php if ($crewDetails): ?>
                    <h1>Overview</h1>
                    <p><?= htmlspecialchars($crewDetails['biography']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="castDetail">
        <div class="castContainer1">
            <h1>Known For</h1>
            <div class='scroll'>
                <button onclick="scrollCast1()"><</button>
                <button onclick="scrollCast2()">></button>
            </div>
        </div>
        <div class="castContainer2">
            <?php if ($movieDetails): ?>
                <?php displayMovieDetails($movieDetails); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="movieDetail">
        <div class="movieContainer1">
            <h1>Filmography</h1>
        </div>
        <div class="movieContainer2">
            <?php if ($movieCredits): ?>
                <?php displayMovieCredits($movieCredits); ?>
            <?php else: ?>
                <p>No movie credits found for this person.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php 
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $crew_id =  $crew_id;
    $timestamp = date('Y-m-d H:i:s');  // Current date and time
    insertUserCastHistory($userId, $crew_id, $timestamp, $pdo);
    }
?> 
<script>
    function scrollCast1() {
        const castContainer = document.querySelector('.castContainer2');
        castContainer.scrollLeft -= 500;
    }

    function scrollCast2() {
        const castContainer = document.querySelector('.castContainer2');
        castContainer.scrollLeft += 500;
    }

    var movieCredits = <?php echo json_encode($movieCredits); ?>;
    console.log(movieCredits);
</script>
</body>
</html>
