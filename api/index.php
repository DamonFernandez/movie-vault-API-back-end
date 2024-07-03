<?php



require "../includes/library.php";
$endpointRegexs = [
    'completedWatchListEntries' => '/^completedwatchlist\/entries$/',
    'completedWatchListEntry' => '/^completedwatchlist\/entries\/(\d+)$/',
    'completedWatchListEntryTimesWatched' => '/^completedwatchlist\/entries\/(\d+)\/times-watched$/',
    'completedWatchListEntryRating' => '/^completedwatchlist\/entries\/(\d+)\/rating$/',
    'movies' => '/^movies\/$/',
    'movie' => '/^movies\/(\d+)$/',
    'movieRating' => '/^movies\/(\d+)\/rating$/',
    'toWatchListEntries' => '/^towatchlist\/entries$/',
    'toWatchListEntry' => '/^towatchlist\/entries\/(\d+)$/',
    'toWatchListPriority' => '/^towatchlist\/entries\/(\d+)\/priority$/',
    'userStats' => '/^users\/(\d+)\/stats$/'
];
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



function validateWholeCompletedWatchList($pdo)
{
    validateSingleValForCompletedWatchListEntry($pdo, "userID");
    validateSingleValForCompletedWatchListEntry($pdo, "movieID");
    validateSingleValForCompletedWatchListEntry($pdo, "rating");
    validateSingleValForCompletedWatchListEntry($pdo, "notes");
    validateSingleValForCompletedWatchListEntry($pdo, "dateStarted");
    validateSingleValForCompletedWatchListEntry($pdo, "dateLastWatched");
    validateSingleValForCompletedWatchListEntry($pdo, "numOfTimesWatched");
}
function validateWholetoWatchList($pdo)
{

    validateSingleValForCompletedWatchListEntry($pdo, "movieID");
    validateSingleValForCompletedWatchListEntry($pdo, "priority");
    validateSingleValForCompletedWatchListEntry($pdo, "notes");
}
function recordExists($pdo, $table, $tableIDName, $tableID)
{

    $query = "SELECT 1 FROM " . $table . " WHERE " . $tableIDName . " = ?";
    $result = queryDB($pdo, $query, [$tableID]);
    return $result->fetch();
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
    $queryToFindUserRating = "SELECT rating FROM completedWatchList WHERE movieID = ?";
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

function checkIfMovieExists($pdo, $tbname, $movieID)
{
    $query = "SELECT 1 from " . $tbname . " WHERE movieID = ?";
    if (!queryDB($pdo, $query, [$movieID])) {
        sendResponse(["errors" => "This entry does not exist in your completed watch list"], "404 Not Found");
    }
}
function deleteMovie($pdo, $tbname, $movieID)
{
    $query = "DELETE FROM " . $tbname . " WHERE movieID = ?";
    queryDB($pdo, $query, [$movieID]);
}

function filterIfExists($urlFilter, &$filters, &$filteredQuery)
{
    if (isset($_GET[$urlFilter])) {
        $filter = $_GET[$urlFilter] ?? "";
        $filters[$urlFilter] = "%" . $filter . "%"; // Prepare the value for LIKE query
        if (str_contains($filteredQuery, "WHERE")) {
            return $filteredQuery . " AND " . $urlFilter . " LIKE ?";
        } else {
            return $filteredQuery . " WHERE " . $urlFilter . " LIKE ?";
        }
    }
    return $filteredQuery;
}

function filterByGenre()
{
}
function changeUserRatingForMovie($pdo, $movieID, $input)
{
    $query = "UPDATE completedWatchList SET rating = ? WHERE movieID = ? ";
    if (queryDB($pdo, $query, [$input["rating"], $movieID])) {
        sendResponse(["" => "Updated user rating for movie successfully"], "200 OK");
    } else {
        sendResponse(["errors" => "Failed to update user rating for movie"], "400 Bad Request");
    }
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
        //completedwatchList GETs
        if (preg_match($endpointRegexs['completedWatchListEntries'], $endpoint)) {
            $query = "SELECT * FROM completedWatchList WHERE userID = ?";
            $queryResultSetObject = queryDB($pdo, $query, [$userID]);
            $completedWatchList = $queryResultSetObject->fetchAll();
            sendResponse($completedWatchList, "200 OK");
        } elseif (preg_match($endpointRegexs['completedWatchListEntryRating'], $endpoint, $matches)) {
            $movieID = $matches[1];
            checkIfMovieExists($pdo, "completedWatchList", $movieID);
            $query = "SELECT rating FROM completedWatchList WHERE movieID = ?";
            $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
            $result = $queryResultSetObject->fetch();
            sendResponse($result, "200 OK");
        } elseif (preg_match($endpointRegexs['completedWatchListEntryTimesWatched'], $endpoint, $matches)) {
            $movieID = $matches[1];
            checkIfMovieExists($pdo, "completedWatchList", $movieID);
            $query = "SELECT numOfTimesWatched FROM completedWatchList WHERE movieID = ?";
            $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
            $result = $queryResultSetObject->fetch();
            sendResponse($result, "200 OK");
        }
        //movies GETs
        elseif (preg_match($endpointRegexs['movies'], $endpoint)) {
            $filters = [];
            $querybase = "SELECT * FROM movies";
            $filteredQuery = filterIfExists("original_language", $filters, $filteredQuery);
            $filteredQuery = filterIfExists("genres", $filters, $filteredQuery);
            $filteredQuery = filterIfExists("title", $filters, $filteredQuery);
            $filteredQuery = filterIfExists("release_date", $filters, $filteredQuery);
            $query = $querybase . $filteredQuery;
            $queryResultSetObject = queryDB($pdo, $query, array_values($filters));
            $movies = $queryResultSetObject->fetchAll();
            sendResponse($movies, "200 OK");
        } elseif (preg_match($endpointRegexs['movie'], $endpoint, $matches)) {
            $movieID = $matches[1];
            $query = "SELECT * FROM movies WHERE movieID = ?";
            $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
            $result = $queryResultSetObject->fetch();
            sendResponse($result, "200 OK");
        } elseif (preg_match($endpointRegexs['movieRating'], $endpoint, $matches)) {
            $movieID = $matches[1];

            $queryToFindUserRating = "SELECT rating FROM completedWatchList WHERE movieID = ?";
            $userRatingObject = queryDB($pdo, $queryToFindUserRating, [$movieID]);

            if ($userRatingObject) {
                $voteDetails = getVoteInfoForMovie($pdo, $movieID);
                $result['rating'] = $voteDetails['vote_average'];
            } else {
                $userRatingArray = $userRatingObject->fetch();
                $result['rating'] = $userRatingArray["rating"];
            }
            sendResponse($result, "200 OK");
        }
        //toWatchList GETs
        elseif (preg_match($endpointRegexs['toWatchListEntries'], $endpoint)) {
            $query = "SELECT * FROM toWatchList WHERE userID=?";
            $queryResultSetObject = queryDB($pdo, $query, [$userID]);
            $toWatchList = $queryResultSetObject->fetchAll();
            sendResponse($toWatchList, "200 OK");
        } elseif (preg_match($endpointRegexs['userStats'], $endpoint, $matches)) {
            $userID = $matches[1];
            $userStats = combineUserStatsIntoArray($pdo, $userID);
            sendResponse($userStats, "200 OK");
        } else {
            sendResponse(["errors" => "Your request was not a valid endpoint"], "400 Bad Request");
        }

        break;
    case "POST":
        //compltedwatchlist Post 
        if (preg_match($endpointRegexs['completedWatchListEntries'], $endpoint)) {
            validateWholeCompletedWatchList($pdo);
            $query = "INSERT INTO completedWatchList (userID, movieID, rating, notes, dateStarted, dateLastWatched, numOfTimesWatched) VALUES (?, ?, ?, ?, ?, ?, ?)";
            queryDB($pdo, $query, [
                $_POST["userID"],
                $_POST["movieID"],
                $_POST["rating"],
                $_POST["notes"],
                $_POST["dateStarted"],
                $_POST["dateLastWatched"],
                $_POST["numOfTimesWatched"]
            ]);
            changeMovieRatingInfoForMoviesTable($pdo, $_POST["movieID"]);
            sendResponse([
                $_POST["userID"],
                $_POST["movieID"],
                $_POST["rating"],
                $_POST["notes"],
                $_POST["dateStarted"],
                $_POST["dateLastWatched"],
                $_POST["numOfTimesWatched"]
            ], "201 Created");

            //towatchlist post
        } elseif (preg_match($endpointRegexs['toWatchList'], $endpoint)) {
            validateWholetoWatchList($pdo);
            $query = "INSERT INTO toWatchList (userID,movieID,priority,notes) VALUES (?,?,?,?)";
            queryDB(
                $pdo,
                $query,
                [
                    $_POST["userID"],
                    $_POST["movieID"],
                    $_POST["priority"],
                    $_POST["notes"]
                ]
            );
            sendResponse(["" => "ToWatchList Entry added"], "201 Created");
        } else {
            sendResponse(["errors" => "Your request was not a valid endpoint"], "400 Bad Request");
        }
        break;

    case "PATCH":
        //compltedwatchlist PATCH
        if (preg_match($endpointRegexs['completedWatchListEntryTimesWatched'], $endpoint, $matches)) {
            $movieID = $matches[1];
            $query = "UPDATE completedWatchList SET numOfTimesWatched = numOfTimesWatched + 1, dateLastWatched = NOW() WHERE movieID = ?";
            queryDB($pdo, $query, [$movieID]);
            sendResponse(["" => "Updated times watched successfully"], "200 OK");
        } elseif (preg_match($endpointRegexs['completedWatchListEntryRating'], $endpoint, $matches)) {
            $movieID = $matches[1];
            parse_str(file_get_contents("php://input"), $_POST);

            if (recordExists($pdo, "completedWatchList", 'movieID', $movieID)) {
                checkIfMovieExists($pdo, "movies", $movieID);
                changeMovieRatingInfoForMoviesTable($pdo, $movieID);
                checkIfMovieExists($pdo, "completedWatchList", $movieID);
                changeUserRatingForMovie($pdo, $movieID, $_POST);
                sendResponse(["" => "Updated rating successfully"], "200 OK");
            } else {
                sendResponse(["errors" => "Movie record not found in completed Watch list"], "404 Not Found");
            }
            //toWatchlist PATCH
        } elseif (preg_match($endpointRegexs['toWatchListPriority'], $endpoint, $matches)) {
            $toWatchListID = $matches[1];
            parse_str(file_get_contents("php://input"), $input);
            $query = "UPDATE toWatchList SET priority = ? WHERE toWatchListID=?";
            queryDB($pdo, $query, [$input["priority"], $toWatchListID]);
            sendResponse(["" => "Updated priority successfully"], "200 OK");
        } else
            sendResponse(["errors" => "Your request was not a valid endpoint"], "400 Bad Request");
        break;
    case "PUT":
        //toWatchList PUT
        if (preg_match($endpointRegexs['toWatchListEntry'], $endpoint, $matches)) {
            // validateWholetoWatchList($pdo);
            $toWatchListID = $matches[1];
            parse_str(file_get_contents("php://input"), $input);
            if (recordExists($pdo, "toWatchList", "toWatchListID", $toWatchListID)) {

                $query = "UPDATE toWatchList SET userID=?,movieID=?, priority=?,notes=? WHERE toWatchListId=? ";
                queryDB($pdo, $query, [
                    $input["userID"],
                    $input["movieID"],
                    $input["priority"],
                    $input["notes"],
                    $toWatchListID
                ]);
                sendResponse("", "204 No Content");
            } else {
                $query = "INSERT INTO toWatchList (userID,movieID,priority,notes,toWatchListID) VALUES (?,?,?,?,?)";
                queryDB(
                    $pdo,
                    $query,
                    [
                        $input["userID"],
                        $input["movieID"],
                        $input["priority"],
                        $input["notes"],
                        $toWatchListID
                    ]
                );
                sendResponse([
                    $input["userID"],
                    $input["movieID"],
                    $input["priority"],
                    $input["notes"],
                    $toWatchListID
                ], "201 Created");
            }
        } else
            sendResponse(["errors" => "Your request was not a valid endpoint"], "400 Bad Request");
        break;


    case "DELETE":
        //completedwatchlist DELETE
        if (preg_match($endpointRegexs['completedWatchListEntry'], $endpoint, $matches)) {
            $movieID = $matches[1];
            checkIfMovieExists($pdo, "completedWatchList", $movieID);
            deleteMovie($pdo, "completedWatchList", $movieID);
            sendResponse(["" => "Movie Was deleted from completed watch list"], "200 OK");
            //towatchlist DELETE
        } elseif (preg_match($endpointRegexs['toWatchListEntry'], $endpoint, $matches)) {
            $movieID = $matches[1];
            checkIfMovieExists($pdo, "toWatchList", $movieID);
            deleteMovie($pdo, "toWatchList", $movieID);
            sendResponse(["" => "Movie Was deleted from to watch list"], "200 OK");
        } else
            sendResponse(["errors" => "Your request was not a valid endpoint"], "400 Bad Request");
    default:
        sendResponse(["errors" => "Your request method was not a valid method"], "400 Bad Request");
}
