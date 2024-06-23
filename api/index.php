<?php require "../includes/library.php";

function getEndPoint(){
    $uri = $_SERVER["REQUEST_URI"];
    $uri = parse_url($uri);
    define('__BASE__', '/~damonfernandez/3430/cois-3430-2024su-a2-Blitzcranq/api/');
    $endpoint = str_replace(__BASE__, "", $uri["path"]);
    return $endpoint;
}
function getUserAPIKey($pdo){
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

  function queryDB($pdo, $query, $arrayOfValuesToPass){
    $stmt = $pdo -> prepare($query);
     $stmt -> execute($arrayOfValuesToPass);
     return $stmt;
  }

function getUserID($pdo, $userApiKey){
    $query = "SELECT user_id FROM users WHERE api_key = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$userApiKey]);
    $result = $queryResultSetObject -> fetch();
    return $result["user_id"];
}

function extractIDFromEndpoint($endpoint) {
    $pattern = "/\{([^}]+)\}/";
    // Use a regular expression to match the ID pattern in the endpoint
    if (preg_match($pattern, $endpoint, $matches)) {
        // Return the matched ID
        return $matches[1];
    }
    // Return null if no match is found
    return null;
}

  // GLOBAL CODE
  $endpoint = getEndPoint();
  $requestMethod = $_SERVER['REQUEST_METHOD'];
  $pdo = connectdb();


  // All endpoints that dont involve the movies table require an API key
  // So get it if the endpoint does not contain "movies" in its name
  if(!str_contains($endpoint, "movies")){
    $userApiKey = getUserAPIKey($pdo);
    $user_id = getUserID($pdo, $userApiKey);
  }

if($requestMethod == "GET" && $endpoint == "/completedwatchlist/entries"){
    $query = "SELECT * FROM completedWatchList WHERE userID = ?";
    $queryResultSetObject = queryDB($pdo, $query, [$user_id]);
    $completedWatchList = $queryResultSetObject -> fetchAll();
    $completedWatchList = json_encode($completedWatchList);

    header("Content-Type: application/json; charset=UTF-8"); 
    header("HTTP/1.1 200 OK");
    echo $completedWatchList;
}

if($requestMethod == "GET" && $endpoint = "/completedwatchlist/entries/{id}/times-watched"){
    $user_id = getUserID($pdo, $userApiKey);
    $completedWatchListID = extractIDFromEndpoint($endpoint);

}
  
?> 