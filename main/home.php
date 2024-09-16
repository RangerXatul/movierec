<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaDB - Home</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include("main-nav.php"); ?>
    <?php include("searchbox.php"); ?>

    <!-- Movie Recommendations Section -->
    <div class="recommendation" id="movie-recommendation">
        <div class="movieContainer">
            <h1>Recommended For You</h1>
            <div class='scroll'>
                <button onclick="scrollContainer('#movies-container', -500)"><</button>
                <button onclick="scrollContainer('#movies-container', 500)">></button>
            </div>
        </div>
        <div id="movies-container" class="movies-container">
            <!-- Movie recommendations will be dynamically loaded here -->
        </div>
    </div>

    <!-- Crew Recommendations Section -->
    <div class="recommendation" id="crew-recommendation">
        <div class="movieContainer">
            <h1>Recommended Based on Crew</h1>
        </div>
        <div id="crew-container" class="crew-container">
            <!-- Crew recommendations will be dynamically loaded here -->
        </div>
    </div>

    <!-- Country Recommendations Section -->
    <div class="recommendation" id="country-recommendation">
        <div class="movieContainer">
            <h1>Recommended Based on Country</h1>
        </div>
        <div id="country-container" class="country-container">
            <!-- Country-based recommendations will be dynamically loaded here -->
        </div>
    </div>

    <footer>
        <div class="container2">
            <p>&copy; 2024 CinemaDB. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function createCard(item, isCrew = false) {
            const card = document.createElement('div');
            card.className = 'card';

            const img = document.createElement('img');
            img.src = item.poster;
            img.alt = item.title;

            const info = document.createElement('div');
            info.className = 'movie-info';

            const title = document.createElement('h4');
            title.textContent = item.title;

            info.appendChild(title);

            const rating = document.createElement('p');
            rating.textContent = `IMDB Rating: ${item.rating || 'N/A'}`;
            info.appendChild(rating);

            const form = document.createElement('form');
            form.action = 'moviedetails.php';
            form.method = 'GET';

            const imdbIDInput = document.createElement('input');
            imdbIDInput.type = 'hidden';
            imdbIDInput.name = 'imdbID';
            imdbIDInput.value = item.imdbid;

            const viewButton = document.createElement('button');
            viewButton.type = 'submit';
            viewButton.textContent = 'View';

            form.appendChild(imdbIDInput);
            form.appendChild(viewButton);

            info.appendChild(form);

            card.appendChild(img);
            card.appendChild(info);

            return card;
        }

        function createCrewSection(crew) {
            const crewSection = document.createElement('div');
            crewSection.className = 'crew-section';

            const crewTitle = document.createElement('h3');

            const crewLink = document.createElement('a');
            crewLink.href = `checkdetail.php?crew_id=${crew.crew_id}`;
            crewLink.textContent = crew.name;
            crewLink.className = 'crew-link';

            crewTitle.appendChild(crewLink);

            if (crew.movies.length > 0) {
                crewSection.appendChild(crewTitle);

                const container = document.createElement('div');
                container.className = 'movies-container';

                crew.movies.slice(0, 7).forEach(movie => {
                    container.appendChild(createCard(movie, true));
                });

                crewSection.appendChild(container);
            }

            return crewSection;
        }

        function createCountrySection(country, movies) {
            const countrySection = document.createElement('div');
            countrySection.className = 'country-section';

            const countryTitle = document.createElement('h3');
            countryTitle.textContent = country;

            countrySection.appendChild(countryTitle);

            const container = document.createElement('div');
            container.className = 'movies-container';

            movies.forEach(movie => {
                container.appendChild(createCard(movie));
            });

            countrySection.appendChild(container);

            return countrySection;
        }

        function scrollContainer(selector, amount) {
            document.querySelector(selector).scrollLeft += amount;
        }

        async function fetchAndDisplayData() {
            try {
                // Fetch movie recommendations
                const movieResponse = await fetch('recommendation/movrec.php');
                const movieData = await movieResponse.json();
                console.log('Movie Recommendations:', movieData);

                const movieContainer = document.getElementById('movies-container');
                const movieRecommendation = document.getElementById('movie-recommendation');
                movieContainer.innerHTML = '';

                if (movieData.length === 0) {
                    movieRecommendation.style.display = 'none';
                } else {
                    movieData.forEach(movie => {
                        movieContainer.appendChild(createCard(movie));
                    });
                    movieRecommendation.style.display = 'block';
                }

                // Fetch crew-based recommendations
                const crewResponse = await fetch('recommendation/personrec.php');
                const crewData = await crewResponse.json();
                console.log('Crew Recommendations:', crewData);

                const crewContainer = document.getElementById('crew-container');
                const crewRecommendation = document.getElementById('crew-recommendation');
                crewContainer.innerHTML = '';

                let hasMovieData = false;

                crewData.forEach(crew => {
                    if (crew.movies && crew.movies.length > 0) {
                        hasMovieData = true;
                    }
                });

                if (!hasMovieData) {
                    crewRecommendation.style.display = 'none';
                } else {
                    crewData.forEach(crew => {
                        const crewSection = createCrewSection(crew);
                        if (crewSection.childElementCount > 0) {
                            crewContainer.appendChild(crewSection);
                        }
                    });
                    crewRecommendation.style.display = 'block';
                }

                // Fetch country-based recommendations
                const countryResponse = await fetch('recommendation/countryrec.php');
                const countryData = await countryResponse.json();
                console.log('Country Recommendations:', countryData);

                const countryContainer = document.getElementById('country-container');
                const countryRecommendation = document.getElementById('country-recommendation');
                countryContainer.innerHTML = '';

                if (Object.keys(countryData).length === 0) {
                    countryRecommendation.style.display = 'none';
                } else {
                    countryData.forEach((countryEntry) => {
                        const { number, country, movies } = countryEntry;
                        const countrySection = createCountrySection(`${number}. ${country}`, movies);
                        if (countrySection.childElementCount > 0) {
                            countryContainer.appendChild(countrySection);
                        }
                    });
                    countryRecommendation.style.display = 'block';
                }

            } catch (error) {
                console.error('Error fetching recommendations:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', fetchAndDisplayData);
    </script>
</body>
</html>
