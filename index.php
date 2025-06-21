<?php
session_start();
require_once 'config.php';

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Falsches Passwort";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<h2>Login</h2>
<?php if (isset($error)) echo "<p>$error</p>"; ?>
<form method="post">
    <input type="password" name="password" placeholder="Passwort">
    <input type="submit" value="Login">
</form>
</body>
</html>
