<?php require "../includes/library.php";

function getUserAPIKey($pdo){
    if (!isset($_SERVER['HTTP_X_API_KEY']) || empty($_SERVER['HTTP_X_API_KEY'])) {
      header('HTTP/1.1 400 Bad Request');
      echo json_encode(['error' => 'You must provide an API key']);
      exit();
  }
  $userApiKey = $_SERVER['HTTP_X_API_KEY'];
  
    $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `apikey` = ?");
    $stmt->execute([$userApiKey]);
    $isValidApiKey = $stmt->fetchColumn();
  
    if ($isValidApiKey === false) {
      header('HTTP/1.1 400 Bad Request');
      echo json_encode(['error' => 'The provided API key is invalid']);
      exit();
  }
  return $stmt["api_key"];
  }

$uri = $_SERVER["REQUEST_URI"];
$uri = parse_url($uri);
define('__BASE__', '/~damonfernandez/3430/cois-3430-2024su-a2-Blitzcranq/api/');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint = str_replace(__BASE__, "", $uri["path"]);


$pdo = connectdb();

?> 