<?php

require_once "./includes/library.php";

session_start();
checkToRedirectToLoginPage();
$pdo = connectdb();


function queryForUserDetails($pdo)
{
    $userID = $_SESSION["userID"];
    $query = "SELECT * FROM users WHERE userID = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userID]);

    return $stmt->fetch();
}

function issueNewAPIKey($pdo)
{

    $key = genAPIKey($pdo);
    $userID = $_SESSION["userID"];
    $query = "UPDATE users SET api_key = ?, api_date = NOW()  WHERE userID = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$key, $userID]);

    // Redirect to the same page to clear POST data and avoid resubmission on reload
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$tableRow = queryForUserDetails($pdo);


if (!$tableRow) {
    echo "User not found";
    exit();
}

if (isset($_POST["apiRequestButton"])) {
    $message = issueNewAPIKey($pdo);
}

checkForLogOut();




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <link rel="stylesheet" href="./styles/main.css">
</head>

<body>
    <ul>
        <li>Username: <?= $tableRow["username"] ?> </li>
        <li>Email: <?= $tableRow["email"] ?></li>
        <li>API Key: <?= $tableRow["api_key"] ?></li>
        <li>Date API key was issued: <?= $tableRow["api_date"] ?></li>
    </ul>

    <form method="POST">
        <button type="submit" name="apiRequestButton">Request new API key</button>
    </form>



    <p> <? $message ?>
    </p>

    <?= createLogOutButton() ?>
</body>

</html>