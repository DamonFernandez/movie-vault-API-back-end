<?php

include_once './includes/library.php';
$pdo = connectDB();

$username = $_POST['username'] ?? "";
$email = $_POST['email'] ?? "";
$password1 = $_POST['password'] ?? "";
$password2 = $_POST['password2'] ?? "";
$errors = [];

if (isset($_POST['submit'])) {

    if (empty($username)) {
        $errors['username_empty'] = true;
    } else {
        $stmt = $pdo->prepare(('SELECT 1 FROM users WHERE username=?'));
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username_duplicate'] = true;
        }
    }
    //verify email is valid
    if (empty(filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = true;
    }
    if ($password1 === $password2) {
        if (strlen($password1) < 7)
            $errors['p_strength'] = true;
    } else {
        $errors['p_match'] = true;
    }
    if (empty($errors)) {
        $hash = password_hash($password1, PASSWORD_DEFAULT);
        $userapikey = genAPIKey($pdo);

        //chnage this, readability : 0
        $stmt = $pdo->prepare('INSERT INTO users (`username`,`email`,`password`,`api_key`,`api_date`) VALUES (?,?,?,?,NOW())')->execute([$username, $email, $hash, $userapikey]);
        header("Location: login.php");
        exit();
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="./styles/main.css">
</head>

<body>
    <header>
        <h1>My Movie List</h1>
    </header>
    <main>
        <div>
            <h2>Create Account</h2>
            <form id="create-account" method="post" action="" />
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" size="25" value="<?= $username ?>" />
                <span class="error <?= !isset($errors['username_empty']) ? 'hidden' : '' ?>">Your username cannot be empty</span>
                <span class="error <?= !isset($errors['username_duplicate']) ? 'hidden' : '' ?>">Username already exists</span>
            </div>
            <div>
                <label for="email">Email:</label>

                <input type="text" id="email" name="email" size="25" value="<?= $email ?>" />
                <span class="error <?= !isset($errors['email']) ? 'hidden' : '' ?>">Your email was invalid</span>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" size="25" />
                <span class="error <?= !isset($errors['p_strength']) ? 'hidden' : '' ?>">Your password is not strong
                    enough</span>
            </div>
            <div>
                <label for="password2">Verify Password:</label>
                <input type="password" id="password2" name="password2" size="25" />
                <span class="error <?= !isset($errors['p_match']) ? 'hidden' : '' ?>">Your passwords do not match</span>
            </div>
            <button id="submit" name="submit">Create Account</button>
            </form>

        </div>
    </main>
</body>

</html>