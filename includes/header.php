<?php


if (isset($_GET['api_doc'])) {
    header("Location: index.php");
    exit();
}
if (isset($_GET["profile"])) {
    header("Location: view-account.php");
    exit();
}

?>
<header>
    <nav>
        <form method="get">
            <button type="submit" name="api_doc" id="api_doc">API Documentation</button>
            <button type="submit" name="profile" id="profile">Profile</button>
        </form>
    </nav>
</header>