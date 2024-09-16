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
$offset = ($page - 1) * $limit;

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'movies';

if ($tab === 'movies') {
    $data = getUserHistory($user_details['user_id'], $pdo, $offset, $limit);
    $totalItems = $pdo->query("
        SELECT COUNT(*) FROM user_history WHERE user_id = {$user_details['user_id']}
    ")->fetchColumn();
    $itemName = 'movie_name';
    $itemId = 'imdbid';
} else {
    $data = getCastHistory($user_details['user_id'], $pdo, $offset, $limit);
    $totalItems = $pdo->query("
        SELECT COUNT(*) FROM user_casthistory WHERE user_id = {$user_details['user_id']}
    ")->fetchColumn();
    $itemName = 'cast_name';
    $itemId = 'crew_id';
}

$totalPages = ceil($totalItems / $limit);

if (!empty($data)): ?>
    <table>
        <thead>
            <tr>
                <th><?php echo $tab === 'movies' ? 'Movie Name' : 'Cast Name'; ?></th>
                <th>Viewed On</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item[$itemName]); ?></td>
                    <td><?php echo htmlspecialchars($item['timestamp']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="#" data-page="<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <p>No <?php echo $tab === 'movies' ? 'movie' : 'cast'; ?> history to display.</p>
<?php endif; ?>
