<?php
// Database connection
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=cinemadb", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Fetch user history with movie names
function getUserHistory($userId, $pdo, $offset, $limit) {
    $stmt = $pdo->prepare("
        SELECT uh.imdbid, uh.timestamp, m.title AS movie_name
        FROM user_history uh
        JOIN movies m ON uh.imdbid = m.imdbid
        WHERE uh.user_id = :user_id
        ORDER BY uh.timestamp DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch cast history with cast names
function getCastHistory($userId, $pdo, $offset, $limit) {
    $stmt = $pdo->prepare("
        SELECT uch.crew_id, uch.timestamp, c.name AS cast_name
        FROM user_casthistory uch
        JOIN crew c ON uch.crew_id = c.crew_id
        WHERE uch.user_id = :user_id
        ORDER BY uch.timestamp DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Start session and get user details
include("user_session.php");

$pdo = getDatabaseConnection();
$limit = 10; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'movies';
$offset = ($page - 1) * $limit;

$moviesHistory = getUserHistory($user_details['user_id'], $pdo, $offset, $limit);
$castHistory = getCastHistory($user_details['user_id'], $pdo, $offset, $limit);

// Get total counts for pagination
$totalMovies = $pdo->query("
    SELECT COUNT(*) FROM user_history WHERE user_id = {$user_details['user_id']}
")->fetchColumn();

$totalCast = $pdo->query("
    SELECT COUNT(*) FROM user_casthistory WHERE user_id = {$user_details['user_id']}
")->fetchColumn();

$totalPagesMovies = ceil($totalMovies / $limit);
$totalPagesCast = ceil($totalCast / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <?php include 'user-nav.php'; ?> <!-- Include your navigation file -->

    <div class="container1">
        <div class="welcome">
            <h1>Welcome, <?php echo htmlspecialchars($user_details['username']); ?>!!</h1>
        </div>
        
        <div class="details">
            <?php if ($user_details): ?>
                <p><strong>UserId:</strong> <?php echo htmlspecialchars($user_details['user_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_details['email']); ?></p>
                <p><strong>Joined:</strong> <?php echo htmlspecialchars($user_details['created_at']); ?></p>
            <?php endif; ?>
        </div>
        
        <form action="../process/logout.php" method="POST" style="display: inline;">
            <button type="submit" class="logout">Logout</button>
        </form>

        <?php if ($user_details['user_id'] == 1): ?>
            <a href="configuration.php" class="config-button">Configuration</a>
        <?php endif; ?>
        
        <div class="section">
            <h2>Recent Activities</h2>
            <div class="tabs">
                <div class="tab <?php echo ($tab === 'movies') ? 'tab-active' : ''; ?>" onclick="showTab('movies')">Movies</div>
                <div class="tab <?php echo ($tab === 'cast') ? 'tab-active' : ''; ?>" onclick="showTab('cast')">Cast</div>
            </div>
            <div id="movies" class="tab-content" style="<?php echo ($tab === 'movies') ? 'display: block;' : 'display: none;'; ?>">
                <?php if (!empty($moviesHistory)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Movie Name</th>
                                <th>Viewed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moviesHistory as $movie): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($movie['movie_name']); ?></td>
                                    <td><?php echo htmlspecialchars($movie['timestamp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesMovies; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&tab=movies" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p>No movie history to display.</p>
                <?php endif; ?>
            </div>
            <div id="cast" class="tab-content" style="<?php echo ($tab === 'cast') ? 'display: block;' : 'display: none;'; ?>">
                <?php if (!empty($castHistory)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cast Name</th>
                                <th>Viewed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($castHistory as $cast): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cast['cast_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cast['timestamp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPagesCast; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&tab=cast" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p>No cast history to display.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Settings</h2>
            <p>Edit your profile settings <a href="profile.php">here</a>.</p>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        // Reset the page number to 1
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('page', '1');
        currentUrl.searchParams.set('tab', tabName);
        history.pushState(null, '', currentUrl);

        // Hide all tab contents and remove active class from all tabs
        document.querySelectorAll('.tab-content').forEach(function(tabContent) {
            tabContent.style.display = 'none';
        });
        document.querySelectorAll('.tab').forEach(function(tab) {
            tab.classList.remove('tab-active');
        });

        // Show the selected tab content and set the active class
        document.getElementById(tabName).style.display = 'block';
        document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('tab-active');

        // Load the tab content via AJAX
        loadTabContent(tabName, 1);
    }

    function loadTabContent(tabName, page) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_activities.php?tab=${tabName}&page=${page}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById(tabName).innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    // Event delegation for pagination links
    document.addEventListener('click', function(event) {
        if (event.target.matches('.pagination a')) {
            event.preventDefault();
            const page = event.target.getAttribute('data-page');
            const tab = document.querySelector('.tab-active').getAttribute('onclick').split("'")[1];
            loadTabContent(tab, page);
        }
    });

    // Show the 'Movies' tab by default
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'movies';
        showTab(tab);
    });
</script>

</body>
</html>
