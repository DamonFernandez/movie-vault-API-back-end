<?php


$username = $_POST['username'] ?? "";
$password = $_POST['password'] ?? "";
$errors = [];


if (isset($_POST['submit'])) {
    require_once("./includes/library.php");
    $pdo = connectdb();
    $query = "SELECT * FROM `users` WHERE `username` = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);
    $dbrow = $stmt->fetch();

    if ($dbrow) {
        if (password_verify($password, $dbrow['password'])) {
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['userID'] = $dbrow['userID']; //fixed for the correct column name
            header("Location: view-account.php");
            exit();
        } else {
            $errors['password'] = true;
        }
    } else {
        $errors['username'] = true;
    }
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./styles/main.css">
</head>

<body>
    <header>
        <h1>My Movie List</h1>
    </header>
    <main>

        <div class="loginbox">
            <h2>Login</h2>
            <form id="login" method="post" action="" class="forms" />

            <div>
                <label for="username">Username:</label>
                <!--notice the echo of username to allow for a sticky form on error-->
                <input type="text" id="username" name="username" size="25" value="<?php echo $username ?>">
                <span class="error <?= !isset($errors['username']) ? 'hidden' : '' ?>">Your username was invalid</span>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" size="25">
                <span class="error <?= !isset($errors['password']) ? 'hidden' : '' ?>">Your password was invalid</span>
            </div>

            <div>
                <label for="remember">Remember:</label>
                <input type="checkbox" name="remember" value="remember" />
            </div>

            <button id="submit" name="submit">Login</button>
            <a href="create-account.php">Create a New Account?</a>
            </form>

        </div>
    </main>


</body>

</html>