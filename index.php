<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation Page</title>
</head>
<body>

<header>
    <h1>API Documentation Page</h1>
</header>
<main>
    <section>
        <h2>Movies Table Endpoints</h2>
        <ul>
            <li>GET & /movies/ - should return all movies.</li>
            <li>GET & /movies/{id} - returns the movie data for a specific movie.</li>
            <li>GET & /movies/{id}/rating - returns the rating value for a specific movie.</li>
        </ul>
    </section>
    <section>
        <h2>toWatchList Table Endpoints</h2>
        <ul>
            <li>GET & /towatchlist/entries - requires an api key and returns all entries on the user's toWatchList.</li>
            <li>POST & /towatchlist/entries - requires an api key and all other data necessary for the toWatchList table, validates then inserts the data.</li>
            <li>PUT & /towatchlist/entries/{id} - requires an api key and all other data necessary for the toWatchList table and replaces the entire record in the database (if there is no record it should insert and return the appropriate HTTP code).</li>
            <li>PATCH & /towatchlist/entries/{id}/priority - requires an api key and new priority and updates the user's priority for the appropriate movie.</li>
            <li>DELETE & /towatchlist/entries/{id} - requires an api key and movieID and deletes the appropriate movie from the user's watchlist.</li>
        </ul>
    </section>
    <section>
        <h2>completedWatchList Table Endpoints</h2>
        <ul>
            <li>GET & /completedwatchlist/entries - requires an api key and returns all entries on the user's completedWatchList.</li>
            <li>GET & /completedwatchlist/entries/{id}/times-watched - requires an api key and returns the number of times the user has watched the given movie.</li>
            <li>GET & /completedwatchlist/entries/{id}/rating - requires an api key and returns the user's rating for this specific movie.</li>
            <li>POST & /completedwatchlist/entries - requires an api key and all other data necessary for the completedWatchList table, validates then inserts the data. It should also recompute and update the rating for the appropriate movie.</li>
            <li>PATCH & /completedwatchlist/entries/{id}/rating - requires an api key and new rating and updates the rating for the appropriate movie in the completedWatchList table, then recalculates the movie's rating and updates the movies table.</li>
            <li>PATCH & /completedwatchlist/entries/{id}/times-watched - requires an api key and increments the number of times watched and updates the last date watched of the appropriate movie.</li>
            <li>DELETE & /completedwatchlist/entries/{id} - requires an api key and movieID and deletes the appropriate movie from the completedWatchList.</li>
        </ul>
    </section>
    <section>
        <h2>user Table Endpoints</h2>
        <ul>
            <li>GET & /users/{id}/stats - returns basic watching stats for the provided user.</li>
        </ul>
    </section>
</main>

</body>
</html>
