<?php
// search_suggestions.php

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$db = 'cinemaDB';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$query = isset($_GET['query']) ? $_GET['query'] : '';

$suggestions = [];

if ($query) {
    $stmt = $pdo->prepare('SELECT title, year,imdbID, Poster FROM movies WHERE title LIKE ? LIMIT 5');
    $stmt->execute(["%$query%"]);
    $suggestions = $stmt->fetchAll();
}

echo json_encode($suggestions);
?>
