<?php require "../includes/library.php";

function getEndPoint()
{
    $uri = $_SERVER["REQUEST_URI"];
    $uri = parse_url($uri);
    define('__BASE__', '/~damonfernandez/3430/cois-3430-2024su-a2-Blitzcranq/api/');

    // define('__BASE__', '/~damonfernandez/3430/cois-3430-2024su-a2-Blitzcranq/api/');
    $endpoint = str_replace(__BASE__, "", $uri["path"]);
    return $endpoint;
}

function sendResponse($json_data, $responseCode)
{
    header("HTTP/1.1 " . $responseCode);
    header("Content-Type: application/json; charset=UTF-8");
    $json_data = json_encode($json_data);
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
    $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `apikey` = ?");
    $stmt->execute([$userApiKey]);
    $isValidApiKey = $stmt->fetchColumn();
    if ($isValidApiKey === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'The provided API key is invalid']);
        exit();
    }
    return $userApiKey;
}



function getUserID($pdo, $userApiKey)
{
    $query = "SELECT user_id FROM users WHERE api_key = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userApiKey]);
    $result = $queryResultSetObject->fetch();
    return $result["user_id"];
}


// Extract ID normally
function extractIDFromEndpoint($endpoint)
{
    $explodedEndpoint = explode("/", $endpoint);
    foreach ($explodedEndpoint as $string) {
        if (str_starts_with($string, "{")) {
            $stringToReturn = str_replace(['{', '}'], '', $string);;
            return $stringToReturn;
        }
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

function calcNewAvgRating($pdo, $id, $oldAvgRating, $oldRatingCount, $newRating, $oldRating = 0)
{
    $query = "SELECT rating,  FROM completedWatchList WHERE watchListID = ?";
    if (queryDB($pdo, $query, [$id]) != false) {
        $newAvgRating = (($oldAvgRating * $oldRatingCount) - $oldRating + $newRating) / $oldRatingCount + 1;
        return $newAvgRating;
    } else {
        $newAvgRating = (($oldAvgRating * $oldRatingCount) + $newRating) / $oldRatingCount + 1;
        return $newAvgRating;
    }
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


function validateWholeCompletedWatchListEntry($pdo)
{
    validateSingleValForCompletedWatchListEntry($pdo, "userID");
    validateSingleValForCompletedWatchListEntry($pdo, "movieID");
    validateSingleValForCompletedWatchListEntry($pdo, "rating");
    validateSingleValForCompletedWatchListEntry($pdo, "notes");
    validateSingleValForCompletedWatchListEntry($pdo, "dateStarted");
    validateSingleValForCompletedWatchListEntry($pdo, "dateCompleted");
    validateSingleValForCompletedWatchListEntry($pdo, "numOfTimesWatched");
}

function setResponse()
{
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
    $user_id = getUserID($pdo, $userApiKey);
}

if ($requestMethod == "GET" && $endpoint == "/completedwatchlist/entries") {
    $query = "SELECT * FROM completedWatchList WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$user_id]);
    $completedWatchList = $queryResultSetObject->fetchAll();
    $completedWatchList = json_encode($completedWatchList);

    header("Content-Type: application/json; charset=UTF-8");
    header("HTTP/1.1 200 OK");
    echo $completedWatchList;
    exit();
} else if ($requestMethod == "GET" && $endpoint == "/completedwatchlist/entries/{id}/times-watched") {
    $completedWatchListID = extractIDFromEndpoint($endpoint);
    $query = "SELECT numOfTimesWatched FROM completedWatchList WHERE completedWatchListID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
    $result = $queryResultSetObject->fetch();
    $result = json_encode($result);
    header("Content-Type: application/json; charset=UTF-8");
    header("HTTP/1.1 200 OK");
    echo $result;
    exit();
} else if ($requestMethod == "GET" && $endpoint == "/completedwatchlist/entries/{id}/rating") {
    $completedWatchListID = extractIDFromEndpoint($endpoint);
    $query = "SELECT rating FROM completedWatchList WHERE completedWatchListID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
    $result = $queryResultSetObject->fetch();
    $result = json_encode($result);
    header("Content-Type: application/json; charset=UTF-8");
    header("HTTP/1.1 200 OK");
    echo $result;
    exit();
} else if ($requestMethod == "POST" && $endpoint == "/completedwatchlist/entries") {

    validateWholeCompletedWatchListEntry($pdo);

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

    header("HTTP/1.1 200 OK");
    echo json_encode(['message' => 'insertion of new row successful']);

    // STILL NEED TO RECOMPUTE MOVIE AVG RATING, WITH THE NEW RATING THAT WAS ADDED 

} else if ($requestMethod == "PATCH" && $endpoint == "/completedwatchlist/entries/{id}/times-watched") {
    $completedWatchListID = $endpoint;
    $query = "UPDATE completedWatchList SET numOfTimesWatched = numOfTimesWatched + 1, dateLastWatch = NOW() WHERE completedWatchListID = ?";
    queryDB($pdo, $query, [$completedWatchListID]);
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode("Your request was not a valid endpoint and/or it involved an invalid paring of an route and a request method");
    exit();
}
