<?php
require_once "./includes/library.php";

session_start();
checkToRedirectToLoginPage() ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation Page</title>
    <link rel="stylesheet" href="./styles/main.css">
</head>

<body>
    <?php include_once './includes/header.php';
    createLogOutButton();
    checkForLogOut();
    ?>
    <h1>API Documentation Page</h1>
    <main>
        <section class="loginbox">
            <h2>Movies Table Endpoints</h2>
            <ul>
                <li>GET & /movies/ - Returns all movies.</li>
                <li>GET & /movies/{id} - Returns the movie data for a specific movie.</li>
                <li>GET & /movies/{id}/rating - Returns the rating value for a specific movie.</li>
            </ul>
        </section>
        <section class="loginbox">
            <h2>toWatchList Table Endpoints</h2>
            <ul>
                <li>GET & /towatchlist/entries - Requires an API key. Returns all entries on the user's toWatchList.</li>
                <li>POST & /towatchlist/entries - Requires an API key. Inserts a new entry into the toWatchList table. Required data:
                    <ul>
                        <li>userID</li>
                        <li>movieID</li>
                        <li>priority</li>
                        <li>notes</li>
                    </ul>
                </li>
                <li>PUT & /towatchlist/entries/{id} - Requires an API key. Replaces an entry in the toWatchList table or inserts a new one if it doesn't exist. Required data:
                    <ul>
                        <li>userID</li>
                        <li>movieID</li>
                        <li>priority</li>
                        <li>notes</li>
                    </ul>
                </li>
                <li>PATCH & /towatchlist/entries/{id}/priority - Requires an API key. Updates the priority of a specific movie in the toWatchList table. Required data:
                    <ul>
                        <li>priority</li>
                    </ul>
                </li>
                <li>DELETE & /towatchlist/entries/{id} - Requires an API key. Deletes a specific movie from the user's toWatchList.</li>
            </ul>
        </section>
        <section class="loginbox">
            <h2>completedWatchList Table Endpoints</h2>
            <ul>
                <li>GET & /completedwatchlist/entries - Requires an API key. Returns all entries on the user's completedWatchList.</li>
                <li>GET & /completedwatchlist/entries/{id}/times-watched - Requires an API key. Returns the number of times the user has watched the given movie.</li>
                <li>GET & /completedwatchlist/entries/{id}/rating - Requires an API key. Returns the user's rating for a specific movie.</li>
                <li>POST & /completedwatchlist/entries - Requires an API key. Inserts a new entry into the completedWatchList table and updates the movie's rating. Required data:
                    <ul>
                        <li>userID</li>
                        <li>movieID</li>
                        <li>rating</li>
                        <li>notes</li>
                        <li>dateStarted</li>
                        <li>dateLastWatched</li>
                        <li>numOfTimesWatched</li>
                    </ul>
                </li>
                <li>PATCH & /completedwatchlist/entries/{id}/rating - Requires an API key. Updates the rating of a specific movie in the completedWatchList table and recalculates the movie's rating. Required data:
                    <ul>
                        <li>rating</li>
                    </ul>
                </li>
                <li>PATCH & /completedwatchlist/entries/{id}/times-watched - Requires an API key. Increments the number of times watched and updates the last date watched of the appropriate movie.</li>
                <li>DELETE & /completedwatchlist/entries/{id} - Requires an API key. Deletes a specific movie from the completedWatchList.</li>
            </ul>
        </section>
        <section class="loginbox">
            <h2>User Table Endpoints</h2>
            <ul>
                <li>GET & /users/{id}/stats - Returns basic watching stats for the provided user.</li>
            </ul>
        </section>
    </main>

</body>

</html>
