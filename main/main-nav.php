<?php
include("main_session.php");
?>


<header>
    <div class="container1">
        <h1>CinemaDB</h1>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="movies.php">Movies</a></li>
                <li><a href="genre.php">Genres</a></li>
                <li><a href="trending.php">Trending</a></li>
                <li><a href="../user/dashboard.php"><?php echo htmlspecialchars($user_details['username']); ?></a></li>
            </ul>
        </nav>
    </div>
</header>
<style>
.container1{
    font-family: Arial, sans-serif;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background-color: #333;
}

header a {
    
    text-decoration: none;
    margin: 0 10px;
}

header a:hover {
    text-decoration: underline;
}

header h1 {
    font-size: 2em;
    margin-bottom: 10px;
    color: #ffffff;
}

header .container1 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

header .container1 h1 {
    margin: 0;
}

header .container1 nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
}

header .container1 nav ul li {
    margin-left: 1rem;
}

header .container1 nav ul li a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
}

header .container1 nav ul li a:hover {
    text-decoration: underline;
}


</style>
