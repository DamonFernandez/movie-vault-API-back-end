<?php

//get data from post
$username = $_POST['username'] ?? "";
$email = $_POST['email'] ?? "";
$password1 = $_POST['password'] ?? "";
$password2 = $_POST['password2'] ?? "";
$errors = [];


//when form has been submitted
if (isset($_POST['submit'])) {
    //make sure name isn't empty
    if (empty($username)) {
        $errors['username'] = true;
    }
    //verify email is valid
    if (empty(filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = true;
    }
    if ($password1 === $password2) {
        if (strlen($password1) <= 10)
            $errors['p_strength'] = true;
    } else {
        $errors['p_match'] = true;
    }
    if (empty($errors)) {
        $hash = password_hash($password1, PASSWORD_DEFAULT);
        $userapikey = bin2hex(random_bytes(32));
        include './includes/library.php';
        $pdo = connectDB();
        $stmt = $pdo->prepare('INSERT INTO users () VALUES (?,?,?,?)')->execute([$username, $email, $hash, $userapikey]);
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
                <span class="error <?= !isset($errors['username']) ? 'hidden' : '' ?>">Your username cannot be empty</span>
            </div>
            <div>
                <label for="email">Email:</label>

                <input type="text" id="email" name="email" size="25" value="<?= $email ?>" />
                <span class="error <?= !isset($errors['email']) ? 'hidden' : '' ?>">Your email was invalid</span>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" size="25" />
                <span class="error <?= !isset($errors['p_strength']) ? 'hidden' : '' ?>">Your passwords was not strong
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