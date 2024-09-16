document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    searchInput.addEventListener('input', function() {
        const query = searchInput.value;

        if (query.length > 1) {
            fetch(`search_suggestions.php?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.classList.add('dropdown-item');
                            div.innerHTML = `
                                <div class="item-info">
                                    <img src="${item.Poster}" alt="Poster" class="item-poster">
                                    <span class="item-title">${item.title}</span>
                                    <span class="item-year">${item.year}</span>
                                </div>
                            `;
                            div.addEventListener('click', function() {
                                // Redirect to the movie detail page
                                const encodedimdbID = encodeURIComponent(item.imdbID);
                                window.location.href = `moviedetails.php?imdbID=${encodedimdbID}`;
                            });
                            searchResults.appendChild(div);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching search suggestions:', error));
        } else {
            searchResults.style.display = 'none';
        }
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#search-form')) {
            searchResults.style.display = 'none';
        }
    });
});
