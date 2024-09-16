<?php

?>


<header>
    <div class="container">
        <h1>CinemaDB</h1>
        <nav>
            <ul>
                <li><a href="../main/home.php">Home</a></li>
                <li><a href="../main/movies.php">Movies</a></li>
                <li><a href="../main/genre.php">Genres</a></li>
                <li><a href="../main/movies.php">Trending</a></li>
                <li><a href="dashboard.php"><?php echo htmlspecialchars($user_details['username']); ?></a></li>
                <li><a href="../process/logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
<style>

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background-color: #333;
    position: fixed;
    width: 100%;
    top:0;
    left: 0;
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

header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

header h1 {
    margin: 0;
}

header nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
}

header nav ul li {
    margin-left: 1rem;
}

header nav ul li a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
}

header nav ul li a:hover {
    text-decoration: underline;
}


</style>
