<?php
if (isset($_GET['tmdbid'])) {
    $tmdbid = $_GET['tmdbid'];
    $imdbid=searchMovie($tmdbid);
    header('Location: savedb.php?imdbID=' . $imdbid.'&view=View');
};

function fetchTMDbMovie($tmdbID, $token) {
    $url = "https://api.themoviedb.org/3/movie/" . urlencode($tmdbID);
    return fetchApiData($url, $token);
};

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
};
function searchMovie($tmdbid){
    $tmdbBearerToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIzZGMyNjA0NTYxMDU1NDFjYWJjOGNmYWJmODFhNmMyMCIsIm5iZiI6MTcyMDE1MDgyOC45MjgzMTEsInN1YiI6IjY2NWU3MmZlOGRkYzMyMGRlMjlkNjYxNCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.Dt4LMOnFQIGfDA9jVUFdTsl0sf9tXqdmJPxzPhvExt0';
    $tmdbMovie = fetchTMDbMovie($tmdbid, $tmdbBearerToken);
    $imdbid=$tmdbMovie['imdb_id'];
    return $imdbid;
};

?>