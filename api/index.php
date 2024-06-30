<?php require "../includes/library.php";

function getEndPoint()
{
    $uri = $_SERVER["REQUEST_URI"];
    $uri = parse_url($uri);
    // define('__BASE__', '/~damonfernandez/3430/assn/cois-3430-2024su-a2-Blitzcranq/api/');

    define('__BASE__', '/~vrajchauhan/3430/assn/cois-3430-2024su-a2-Blitzcranq/api/');
    $endpoint = str_replace(__BASE__, "", $uri["path"]);
    return $endpoint;
}

function sendResponse($data, $responseCode)
{
    header("HTTP/1.1 " . $responseCode);
    header("Content-Type: application/json; charset=UTF-8");
    $json_data = json_encode($data);
    echo $json_data;
    exit();
}
function getUserAPIKey($pdo)
{
    // Check that its set
    if (!isset($_SERVER['HTTP_X_API_KEY']) || empty($_SERVER['HTTP_X_API_KEY'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'You must provide an API key']);
        exit();
    }
    $userApiKey = $_SERVER['HTTP_X_API_KEY'];


    // Check if its valid
    $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `api_key` = ?"); //changed this coz table has api_key not apikey
    $stmt->execute([$userApiKey]);
    $isValidApiKey = $stmt->fetchColumn();
    if ($isValidApiKey === false) {
        sendResponse(['error' =>  ' Your api key is not valid'], "401 Unauthorized");
    }
    return $userApiKey;
}



function getUserID($pdo, $userApiKey)
{
    $query = "SELECT userID FROM users WHERE api_key = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userApiKey]);
    $result = $queryResultSetObject->fetch();
    return $result["userID"];
}


// Extract ID normally from an endpoint at a given index
function extractIDFromEndpointAtIndex($endpoint, $index)
{
    $explodedEndpoint = explode("/", $endpoint);
    if (isset($explodedEndpoint[$index])) {
        return $explodedEndpoint[$index];
    }

    echo "Could not find an id ";
    return null;
}

function queryDB($pdo, $query, $arrayOfValuesToPass)
{
    $stmt = $pdo->prepare($query);
    $stmt->execute($arrayOfValuesToPass);
    return $stmt;
}



function validateSingleValForCompletedWatchListEntry($pdo, $thingToCheckFor)
{


    if (!isset($_POST[$thingToCheckFor])) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode("Missing $thingToCheckFor");
        exit();
    }

    if ($thingToCheckFor == "userID") {
        $query = "SELECT userID FROM users WHERE userID = ?";
        $queryResultSetObject = queryDB($pdo, $query, [$_POST["userID"]]);
        if (!$queryResultSetObject->fetch()) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode("userID not found, check that you are accessing a valid userID");
            exit();
        }
    } else if ($thingToCheckFor == "movieID") {
        $query = "SELECT movieID FROM movies WHERE movieID = ?";
        $queryResultSetObject = queryDB($pdo, $query, [$_POST["movieID"]]);
        if (!$queryResultSetObject->fetch()) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode("movieID not found, check that you are accessing a valid movieID");
            exit();
        }
    }
}

function validateSingleValFortoWatchListEntry($pdo)
{
}


function validateWholeCompletedWatchList($pdo)
{
    validateSingleValForCompletedWatchListEntry($pdo, "userID");
    validateSingleValForCompletedWatchListEntry($pdo, "movieID");
    validateSingleValForCompletedWatchListEntry($pdo, "rating");
    validateSingleValForCompletedWatchListEntry($pdo, "notes");
    validateSingleValForCompletedWatchListEntry($pdo, "dateStarted");
    validateSingleValForCompletedWatchListEntry($pdo, "dateCompleted");
    validateSingleValForCompletedWatchListEntry($pdo, "numOfTimesWatched");
}
function validateWholetoWatchList($pdo)
{
    validateSingleValForCompletedWatchListEntry($pdo, "userID");
    validateSingleValForCompletedWatchListEntry($pdo, "movieID");
    validateSingleValForCompletedWatchListEntry($pdo, "priority");
    validateSingleValForCompletedWatchListEntry($pdo, "notes");
}
function recordExsists($pdo, $table, $tableIDName, $tableID)
{
    $query = "SELECT 1 from ? WHERE ? = ? ";
    $stmt = queryDB(
        $pdo,
        $query,
        [$table, $tableIDName, $tableID]
    );
    return $stmt->fetch();
}
function getVoteInfoForMovie($pdo, $movieID)
{
    $query = "SELECT movies.vote_average, movies.vote_count FROM movies WHERE movieID = ?";
    $movieVoteInfoObject = queryDB($pdo, $query, [$movieID]);
    return $movieVoteInfoObject->fetch();
}
function movieAvgRatingFormula($oldAvgRating, $oldRatingCount, $newRating, $oldRating = 0)
{

    // If oldRating != 0 it means we passed something in, implying that it already existed
    if ($oldRating != 0) {
        // set newRatingCount equal to oldRatingCount since the total amount of votes is still 
        // the same in this case, since we just over write
        $newRatingCount = $oldRatingCount;
        $newAvgRating = (($oldAvgRating * $oldRatingCount) - $oldRating + $newRating) / $newRatingCount;
        return $newAvgRating;
    } else {
        $newRatingCount = $oldRatingCount + 1;

        $newAvgRating = (($oldAvgRating * $oldRatingCount) + $newRating) / $newRatingCount;
        return $newAvgRating;
    }
}


function recalculateMoveRatingInfo($pdo, $movieID, $newUserRating)
{
    $movieVoteInfo = getVoteInfoForMovie($pdo, $movieID);
    $oldMovieVoteCount = $movieVoteInfo["vote_count"];
    $oldMovieVoteAvg = $movieVoteInfo["vote_average"];
    $queryToFindUserRating = "SELECT rating FROM completedWatchList WHERE watchListID = ?";
    $oldUserRatingObject = queryDB($pdo, $queryToFindUserRating, [$movieID]);

    if (!$oldUserRatingObject) {
        $newMovieVoteCount = 1 + $oldMovieVoteCount;
        $newMovieAvgRating = movieAvgRatingFormula($oldMovieVoteAvg, $oldMovieVoteCount, $newUserRating);
    } else {
        $oldUserRatingArray = $oldUserRatingObject->fetch();
        $oldUserRating = $oldUserRatingArray["rating"];
        $newMovieAvgRating = movieAvgRatingFormula($oldMovieVoteAvg, $oldMovieVoteCount, $newUserRating, $oldUserRating);
        $newMovieVoteCount = $oldMovieVoteCount;
    }

    return ["newMovieAvgRating" => $newMovieAvgRating, "newMovieVoteCount" => $newMovieVoteCount];
}



function changeMovieRatingInfoForMoviesTable($pdo, $movieID)
{
    $movieRatingInfoArray = recalculateMoveRatingInfo($pdo, $movieID, $_POST["rating"]);
    $query = "UPDATE movies SET vote_average = ?, vote_count = ? WHERE movieID = ?";

    queryDB($pdo, $query, [$movieRatingInfoArray["newMovieAvgRating"], $movieRatingInfoArray["newMovieVoteCount"], $movieID]);
}

function checkIfMovieExistsInCompletedWatchList($pdo, $completedWatchListID)
{
    $query = "SELECT completedWatchListID from completedWatchList WHERE completedWatchListID = ?";
    if (!queryDB($pdo, $query, [$completedWatchListID])) {
        sendResponse("This entry does not exist in your completed watch list", "404 Not Found");
    }
}
function deleteMovieFromCompletedWatchList($pdo, $completedWatchListID)
{
    $query = "DELETE FROM completedWatchList WHERE completedWatchListID = ?";
    queryDB($pdo, $query, [$completedWatchListID]);
}
function checkIfMovieExistsIntoWatchList($pdo, $completedWatchListID)
{
    $query = "SELECT toWatchListID from toWatchList WHERE toWatchListID = ?";
    if (!queryDB($pdo, $query, [$completedWatchListID])) {
        sendResponse("This entry does not exist in your to watch list", "404 Not Found");
    }
}
function deleteMovieFromtoWatchList($pdo, $completedWatchListID)
{
    $query = "DELETE FROM toWatchList WHERE toWatchListID = ?";
    queryDB($pdo, $query, [$completedWatchListID]);
}

function combineUserStatsIntoArray($pdo, $userID)
{
    $totalTimeWatched = getUserTotalTimeWatched($pdo, $userID);
    $totalPlannedWatchTime = getUserPlannedWatchTime($pdo, $userID);
    $userAvgRating = getUserAvgRating($pdo, $userID);
    $totalNumOfTimesUserWatchedAMovie = getNumOfTimesUserWatchedAMovie($pdo, $userID);

    return [
        "totalTimeWatched" => $totalTimeWatched,
        "totalPlannedWatchTime" => $totalPlannedWatchTime,
        "userAvgRating" => $userAvgRating,
        "totalNumOfTimesUserWatchedAMovie" => $totalNumOfTimesUserWatchedAMovie
    ];
}

function getUserTotalTimeWatched($pdo, $userID)
{
    $query = "SELECT completedWatchList.numOfTimesWatched, movies.runtime FROM completedWatchList INNER JOIN movies using (movieID) WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userID]);
    $totalWatchedTime = 0;
    foreach ($queryResultSetObject as $row) {
        $totalWatchedTime += ($row["runtime"] * $row["numOfTimesWatched"]);
    }
    return $totalWatchedTime;
}

function getUserPlannedWatchTime($pdo, $userID)
{
    $query = "SELECT movies.runtime FROM toWatchList INNER JOIN movies using (movieID) WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userID]);
    $totalPlannedWatchTime = 0;
    foreach ($queryResultSetObject as $row) {
        $totalPlannedWatchTime += $row["runtime"];
    }
    return $totalPlannedWatchTime;
}

function getUserAvgRating($pdo, $userID)
{
    $query = "SELECT AVG(rating) AS userAvgRating FROM completedWatchList WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userID]);
    $row = $queryResultSetObject->fetch();
    if ($row !== false) {
        $userAvgRating = $row["userAvgRating"];
    } else {
        $userAvgRating = null;
    }

    return $userAvgRating;
}

function getNumOfTimesUserWatchedAMovie($pdo, $userID)
{
    $query = "SELECT numOfTimesWatched FROM completedWatchList WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userID]);
    $totalTimesUserWatchedAMovie = 0;
    foreach ($queryResultSetObject as $row) {
        $totalTimesUserWatchedAMovie += $row["numOfTimesWatched"];
    }
    return $totalTimesUserWatchedAMovie;
}



// GLOBAL CODE
$endpoint = getEndPoint();
$requestMethod = $_SERVER['REQUEST_METHOD'];
$pdo = connectdb();


// All endpoints that dont involve the movies table require an API key
// So get it if the endpoint does not contain "movies" in its name
if (!str_contains($endpoint, "movies") && !str_contains($endpoint, "users")) {
    // Exists if valid api key is not found, preventing non auth users
    // from doing any non-allowed requests
    $userApiKey = getUserAPIKey($pdo);
    $userID = getUserID($pdo, $userApiKey);
}

switch ($requestMethod) {
    case "GET":
        if ($endpoint == "completedwatchlist/entries") {
            $query = "SELECT * FROM completedWatchList WHERE userID = ?";
            $queryResultSetObject = queryDB($pdo, $query, [$userID]);
            $completedWatchList = $queryResultSetObject->fetchAll();
            sendResponse($completedWatchList, "200 OK");
        } elseif (str_contains($endpoint, "completedwatchlist/entries/")) {
            $completedWatchListID = extractIDFromEndpointAtIndex($endpoint, 3);
            if (str_contains($endpoint, "times-watched") !== false) {
                $query = "SELECT numOfTimesWatched FROM completedWatchList WHERE completedWatchListID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
            } elseif (str_contains($endpoint, "rating") !== false) {
                $query = "SELECT rating FROM completedWatchList WHERE completedWatchListID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
            }
        } elseif ($endpoint == "movies") {
            $query = "SELECT * FROM movies";
            $queryResultSetObject = queryDB($pdo, $query, []);
            $movies = $queryResultSetObject->fetchAll();
            sendResponse($movies, "200 OK");
        } elseif (str_contains($endpoint, "movies/")) {
            $movieID = extractIDFromEndpointAtIndex($endpoint, 1);
            if (str_contains($endpoint, "rating")) {
                $query = "SELECT rating FROM completedWatchList WHERE movieID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
            } else {
                $query = "SELECT * FROM movies WHERE movieID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
            }
        } elseif ($endpoint == "toWatchList") {
            $query = "SELECT * FROM toWatchList WHERE userID=?";
            $queryResultSetObject = queryDB($pdo, $query, [$userID]);
            $toWatchList = $queryResultSetObject->fetchAll();
            sendResponse($toWatchList, "200 OK");
        } elseif ($endpoint == "users/" && str_contains($endpoint, "/stats")) {
            $userID = extractIDFromEndpointAtIndex($endpoint, 1);
            $userStats = combineUserStatsIntoArray($pdo, $userID);
            sendResponse($userStats, "200 OK");
        } else {
            sendResponse("Your request was not a valid endpoint", "400 Bad Request");
        }

        break;
    case "POST":
        if ($endpoint == "completedwatchlist/entries") {
            validateWholeCompletedWatchList($pdo);
            $query = "INSERT INTO completedWatchList (userID, movieID, rating, notes, dateStarted, dateCompleted, numOfTimesWatched) VALUES (?, ?, ?, ?, ?, ?, ?)";
            queryDB($pdo, $query, [
                $_POST["userID"],
                $_POST["movieID"],
                $_POST["rating"],
                $_POST["notes"],
                $_POST["dateStarted"],
                $_POST["dateCompleted"],
                $_POST["numOfTimesWatched"]
            ]);
            sendResponse("Completed watchlist entry added", "201 Created");
            // STILL NEED TO RECOMPUTE MOVIE AVG RATING, WITH THE NEW RATING THAT WAS ADDED 
        } elseif ($endpoint == "toWatchList/entries") {
            sendResponse("Completed watchlist entry added", "201 Created");
        } else {
            sendResponse("Your request was not a valid endpoint", "400 Bad Request");
        }
        break;

    case "PATCH":
        if ($endpoint == "/completedwatchlist/entries/") {
            $completedWatchListID = extractIDFromEndpointAtIndex($endpoint, 2);
            if (str_contains($endpoint, "times-watched")) {
                $query = "UPDATE completedWatchList SET numOfTimesWatched = numOfTimesWatched + 1, dateLastWatch = NOW() WHERE completedWatchListID = ?";
                queryDB($pdo, $query, [$completedWatchListID]);
                sendResponse("Updated times watched successfully", "200 OK");
            } elseif (str_contains($endpoint, "rating")) {

                changeMovieRatingInfoForMoviesTable($pdo, $_POST["movieID"]);
                checkIfMovieExistsInCompletedWatchList($pdo, $completedWatchListID);

                function changeUserRatingForMovie($pdo, $completedWatchListID)
                {
                    $query = "UPDATE completedWatchList SET rating = ? WHERE completedWatchListID = ? ";
                    if (queryDB($pdo, $query, [$_POST["rating"], $completedWatchListID])) {
                        sendResponse("Updated user rating for movie successfully", "200 OK");
                    } else {
                        sendResponse("Failed to update user rating for movie", "400 Bad Request");
                    }
                }
            }
        } elseif ($endpoint == "toWatchList/entries/" && str_contains($endpoint, "/priority")) {
            $toWatchListID = extractIDFromEndpointAtIndex($endpoint, 2);
            $query = "UPDATE toWatchList SET priority = ? WHERE toWatchListID=?";
            queryDB($pdo, $query, [$_POST['priority'], $toWatchListID]);
            sendResponse("Updated priority  successfully", "200 OK");
        } else
            sendResponse("Your request was not a valid endpoint", "400 Bad Request");
        break;
    case "PUT":
        if (str_contains($endpoint, "toWatchList/entries/")) {
            validateWholetoWatchList($pdo);
            $toWatchListID = extractIDFromEndpointAtIndex($endpoint, 2);
            if (recordExsists($pdo, "toWatchList", "toWatchListID", $toWatchListID)) {
            }
            $query = "UPDATE toWatchList SET userID= ?, movieID=?, priority=?,notes=? WHERE toWatchListID=?";
            queryDB($pdo, $query, [
                $_POST["userID"],
                $_POST["movieID"],
                $_POST["prioriy"],
                $_POST["notes"],
                $toWatchListID
            ]);
            sendResponse(["" => "ToWatchList Entry updated"], "204 No Content");
        }
    case "DELETE":
        if ($endpoint == "/completedwatchlist/entries/") {
            $completedWatchListID = extractIDFromEndpointAtIndex($endpoint, 2);
            checkIfMovieExistsInCompletedWatchList($pdo, $completedWatchListID);
            deleteMovieFromCompletedWatchList($pdo, $completedWatchListID);
            sendResponse("Movie Was deleted from completed watch list", "200 OK");
        } elseif ($endpoint == "/toWatchList/entries/") {
            $toWatchListID = extractIDFromEndpointAtIndex($endpoint, 2);
            checkIfMovieExistsIntoWatchList($pdo, $toWatchListID);
            deleteMovieFromtoWatchList($pdo, $toWatchListID);
            sendResponse("Movie Was deleted from to watch list", "200 OK");
        } else
            sendResponse("Your request was not a valid endpoint", "400 Bad Request");
    default:
        sendResponse(["errors" => "Your request method was not a valid method"], "400 Bad Request");
}
