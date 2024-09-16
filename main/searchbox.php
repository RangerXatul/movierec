<style>
    .container9{
        width: 100%;
        margin-top: 5px;
        align-items: center;
        
    }

.container9 .dropdown-item {
    max-width: 1200px;
    cursor: pointer;
    padding: 10px;
    border: 1px solid #ddd;
    background-color: #fff;
    align-items: center;
    
}

.container9 .dropdown-item:hover {
    background-color: #f0f0f0;
}

.container9 .dropdown {
    align-content: center;
    position: absolute;
    display: none;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    background-color: #fff;
    z-index: 1000;
    width: 900px;
    margin-left: 90px;
}

.item-poster{
    width: 50px;
    height: 75px;
    display: block;

}
.item-info {
    display: flex;
    justify-content: space-between;
}

.item-title {
    font-weight: bold;
    margin-top: 20px;
    color:black;
}

.item-year {
    color: #888;
    margin-top: 20px;
}

form {
    text-align: center;
    margin-bottom: 20px;
    width:100%;
    align-content: center;
    
}

form input[type="text"] {
    width: 80%;
    padding: 10px;
    font-size: 1em;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.container9 form button {
    padding: 10px 20px;
    font-size: 1em;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.container9 form button:hover {
    background-color: #45a049;
}
</style>
<div class="container9">
    <div class="container58">
        <form action="search.php" method="GET" id="search-form">
            <input type="text" name="title" id="search-input" placeholder="Search movies, cast, directors...">
            <button type="submit">Search</button>
            <div id="search-results" class="dropdown"></div>
        </form></div>
        
    </div>
    <script src="search.js"></script>