<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
<h2>Willkommen</h2>
<ul>
    <li><a href="sources.php">Sources verwalten</a></li>
    <li><a href="problems.php">Problemwörter verwalten</a></li>
    <li><a href="memory.php">Memory erstellen</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>
</body>
</html>
