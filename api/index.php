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

function getVoteInfoForMovie($pdo, $movieID){
    $query = "SELECT movies.vote_average, movies.vote_count FROM movies WHERE movieID = ?";
    $movieVoteInfoObject = queryDB($pdo, $query, [$movieID]);
    return $movieVoteInfoObject -> fetch();
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


function recalculateMoveRatingInfo($pdo, $movieID, $newUserRating){
    $movieVoteInfo = getVoteInfoForMovie($pdo, $movieID);
    $oldMovieVoteCount = $movieVoteInfo["vote_count"];
    $oldMovieVoteAvg = $movieVoteInfo["vote_average"];
    $queryToFindUserRating = "SELECT rating FROM completedWatchList WHERE watchListID = ?";
    $oldUserRatingObject = queryDB($pdo, $queryToFindUserRating, [$movieID]);

    if(!$oldUserRatingObject){
        $newMovieVoteCount = 1 + $oldMovieVoteCount;
        $newMovieAvgRating = movieAvgRatingFormula($oldMovieVoteAvg, $oldMovieVoteCount, $newUserRating);
    }
    else{
        $oldUserRatingArray = $oldUserRatingObject -> fetch();
        $oldUserRating = $oldUserRatingArray["rating"];
        $newMovieAvgRating = movieAvgRatingFormula($oldMovieVoteAvg, $oldMovieVoteCount, $newUserRating, $oldUserRating);
        $newMovieVoteCount = $oldMovieVoteCount;
    }
    
    return ["newMovieAvgRating" => $newMovieAvgRating, "newMovieVoteCount" => $newMovieVoteCount ];
}



function changeMovieRatingInfoForMoviesTable($pdo, $movieID){
    $movieRatingInfoArray = recalculateMoveRatingInfo($pdo, $movieID, $_POST["rating"]);
    $query = "UPDATE movies SET vote_average = ?, vote_count = ? WHERE movieID = ?";
    
    queryDB($pdo, $query, [$movieRatingInfoArray["newMovieAvgRating"], $movieRatingInfoArray["newMovieVoteCount"], $movieID]);
}

function checkIfMovieExistsInCompletedWatchList($pdo, $completedWatchListID){
    $query = "SELECT completedWatchListID from completedWatchList WHERE completedWatchListID = ?";
    if(!queryDB($pdo, $query, [$completedWatchListID])){
        sendResponse("This entry does not exist in your completed watch list", "404 Not Found");
    }
}
function deleteMovieFromCompletedWatchList($pdo, $completedWatchListID){
    $query = "DELETE FROM completedWatchList WHERE completedWatchListID = ?";
    queryDB($pdo, $query, [$completedWatchListID]);
    
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

switch ($requestMethod) {
    case "GET":
        switch ($endpoint) {
            case "/completedwatchlist/entries":
                $query = "SELECT * FROM completedWatchList WHERE userID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$user_id]);
                $completedWatchList = $queryResultSetObject->fetchAll();
                sendResponse($completedWatchList, "200 OK");
                break;
            case "/completedwatchlist/entries/{id}/times-watched":
                $completedWatchListID = extractIDFromEndpoint($endpoint);
                $query = "SELECT numOfTimesWatched FROM completedWatchList WHERE completedWatchListID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
                break;
            case "/completedwatchlist/entries/{id}/rating":
                $completedWatchListID = extractIDFromEndpoint($endpoint);
                $query = "SELECT rating FROM completedWatchList WHERE completedWatchListID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$completedWatchListID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
                break;
            case "/movies/{id}/rating":
                $movieID = extractIDFromEndpoint($endpoint);
                $query = "SELECT rating FROM completedWatchList WHERE movieID = ?";
                $queryResultSetObject = queryDB($pdo, $query, [$movieID]);
                $result = $queryResultSetObject->fetch();
                sendResponse($result, "200 OK");
                break;
            default:
                sendResponse("Your request was not a valid endpoint", "400 Bad Request");
        }
        break;
    case "POST":
        switch ($endpoint) {
            case "/completedwatchlist/entries":
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
                
                changeMovieRatingInfoForMoviesTable($pdo, $_POST["movieID"]);
                sendResponse("Completed watchlist entry successfully added", "201 Created");
                break;
            default:
                sendResponse("Your request was not a valid endpoint", "400 Bad Request");
        }
        break;
    case "PATCH":
        switch ($endpoint) {
            case "/completedwatchlist/entries/{id}/times-watched":
                $completedWatchListID = $endpoint;
                $query = "UPDATE completedWatchList SET numOfTimesWatched = numOfTimesWatched + 1, dateLastWatch = NOW() WHERE completedWatchListID = ?";
                queryDB($pdo, $query, [$completedWatchListID]);
                break;
            
        
            
                default:
                sendResponse("Your request was not a valid endpoint", "400 Bad Request");

        }
        break;
    case "DELETE":
        switch($endpoint){
            case "/completedwatchlist/entries/{id}":
                $completedWatchListID = extractIDFromEndpoint($endpoint);
                checkIfMovieExistsInCompletedWatchList($pdo, $completedWatchListID);
                deleteMovieFromCompletedWatchList($pdo, $completedWatchListID);
                sendResponse("Movie Was deleted from completed watch list", "200 OK");

        }
        default: 
            sendResponse("Your request was not a valid endpoint", "400 Bad Request");
    default:
        sendResponse("Your request was not a valid endpoint", "400 Bad Request");
}
