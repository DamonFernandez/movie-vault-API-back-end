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
    <?php include_once './includes/header.php' ?>
    <div class="userinfo">
        <h2>User Info</h2>
        <ul>
            <li>Username: <span class="value"><?= $tableRow["username"] ?></span> </li>
            <li>Email:<span class="value"> <?= $tableRow["email"] ?></span></li>
            <li>API Key:<span class="value"> <?= $tableRow["api_key"] ?></span></li>
            <li>Date API key was issued: <span class="value"><?= $tableRow["api_date"] ?></span></li>
        </ul>
    </div>

    <form method="POST" class="view-form">
        <button type="submit" name="apiRequestButton">Request new API key</button>
        <p> <? $message ?>
        </p>
        <?= createLogOutButton() ?>
    </form>

</body>

</html>