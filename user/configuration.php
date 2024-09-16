<?php
include("user_session.php");

// Restrict access to user_id 1
if ($user_details['user_id'] != 1) {
    header("Location: ../user/dashboard.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "cinemadb");

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Handle delete request
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['imdbid'])) {
    $imdbID = $_POST['imdbid'];
    
    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM movies WHERE imdbid = ?");
    $stmt->bind_param("s", $imdbID);
    $stmt->execute();
    $stmt->close();
    
    // Optionally, delete related entries from other tables
    $stmt = $conn->prepare("DELETE FROM movie_genres WHERE imdbid = ?");
    $stmt->bind_param("s", $imdbID);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM crew_movie WHERE imdbid = ?");
    $stmt->bind_param("s", $imdbID);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration</title>
    <link rel="stylesheet" href="configuration.css">
</head>
<body>
    <?php include 'user-nav.php'; ?>

    <div class="container1">
        <h1>Configuration Page</h1>

        <div class="tabs">
            <div class="tab" id="tab-movies">Movies</div>
            <div class="tab" id="tab-crew">Crew</div>
        </div>

        <div class="search">
            <h2>Search</h2>
            <input type="text" id="search-box" placeholder="Search by Name Or Id">
            <button id="search-button">Search</button>
        </div>

        <div id="movies" class="tab-content">
            <h2>Movies</h2>
            <div id="movies-data"></div>
        </div>

        <div id="crew" class="tab-content">
            <h2>Crew</h2>
            <div id="crew-data"></div>
        </div>
    </div>

    <script>
        let activeTab = 'movies'; // Default tab

        function showTab(tabId) {
            activeTab = tabId;
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            document.getElementById(`tab-${tabId}`).classList.add('active');
            document.getElementById(tabId).classList.add('active');

            // Clear search box and fetch all data for the selected tab
            document.getElementById('search-box').value = '';
            fetchData(tabId);
        }

        function fetchData(tabId, searchQuery = '') {
            fetch(`configuration/fetch_${tabId}.php?search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById(`${tabId}-data`);
                    let tableHtml = '';

                    if (data.length > 0) {
                        tableHtml = `
                            <table>
                                <thead>
                                    <tr>
                                        <th>SN</th>
                                        ${tabId === 'movies' ? 
                                        '<th>Name</th><th>Year</th><th>IMDb ID</th><th>TMDb ID</th><th>Plot</th><th>Poster</th><th>Runtime</th><th>Rating</th><th>Votes</th><th>Actions</th>' : '<th>Crew ID</th><th>Name</th><th>Birthdate</th><th>Deatedate</th><th>Birthplace</th><th>Poster</th><th>Status</th><th>Actions</th>'}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((item, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            ${tabId === 'movies' ? `
                                                <td>${escape(item.title)}</td>
                                                <td>${escape(item.year)}</td>
                                                <td>${escape(item.imdbid)}</td>
                                                <td>${escape(item.tmdbid)}</td>
                                                <td>${escape(item.plot)}</td>
                                                <td>${item.poster ? 'Available' : 'Not Available'}</td>
                                                <td>${escape(item.runtime)}</td>
                                                <td>${escape(item.imdbrating)}</td>
                                                <td>${escape(item.numvote)}</td>
                                                <td class="action">
                                                    <button class="refresh" onclick="confirmAction('refresh', '${escape(item.imdbid)}')">Refresh</button>
                                                    <button class="delete" onclick="confirmAction('delete', '${escape(item.imdbid)}')">Delete</button>
                                                </td>` : `
                                                <td>${escape(item.crew_id)}</td>
                                                <td>${escape(item.name)}</td>
                                                <td>${escape(item.birthday)}</td>
                                                <td>${escape(item.deathday)}</td>
                                                <td>${escape(item.birthplace)}</td>
                                                <td>${item.poster ? 'Available' : 'Not Available'}</td>
                                                <td>${item.status === "1" ? 'Updated' : 'Not Updated'}</td>
                                                <td class="action">
                                                    <button class="refresh" onclick="confirmAction('refresh', '${escape(item.crew_id)}')">Refresh</button>
                                                </td>`
                                            }
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                    } else {
                        tableHtml = `<p>No ${tabId === 'movies' ? 'movies' : 'crew members'} found.</p>`;
                    }

                    container.innerHTML = tableHtml;
                });
        }

        function escape(string) {
            return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function confirmAction(action, id) {
            let message = '';
            if (action === 'delete') {
                message = 'Are you sure you want to delete this movie?';
            } else if (action === 'refresh') {
                message = 'Are you sure you want to refresh this item?';
            }

            if (confirm(message)) {
                if (action === 'delete') {
                    deleteItem(id);
                } else {
                    window.location.href = activeTab === 'movies' 
                        ? `../user/configuration/refresh_movie.php?imdbid=${encodeURIComponent(id)}` 
                        : `../user/configuration/refresh_crew.php?crew_id=${encodeURIComponent(id)}`;
                }
            }
        }

        function deleteItem(imdbID) {
            fetch('configuration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'delete',
                    imdbid: imdbID
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Movie deleted successfully.');
                    fetchData('movies'); // Refresh movie data
                } else {
                    alert('Error deleting movie.');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            showTab(activeTab); // Show default tab on load

            // Trigger search on button click
            document.getElementById('search-button').addEventListener('click', () => {
                fetchData(activeTab, document.getElementById('search-box').value);
            });

            // Trigger search on Enter key press
            document.getElementById('search-box').addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault(); // Prevent form submission
                    fetchData(activeTab, document.getElementById('search-box').value);
                }
            });

            // Tab switching functionality
            document.getElementById('tab-movies').addEventListener('click', () => {
                showTab('movies');
            });

            document.getElementById('tab-crew').addEventListener('click', () => {
                showTab('crew');
            });
        });
    </script>
</body>
</html>
