<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in'])) { header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

// Create
if (isset($_POST['title'], $_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO sources (title, content) VALUES (?, ?)");
    $stmt->execute([$_POST['title'], $_POST['content']]);
}

// Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM sources WHERE id=?");
    $stmt->execute([$_GET['delete']]);
}

$sources = $pdo->query("SELECT * FROM sources")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Sources</title></head>
<body>
<h2>Sources</h2>
<form method="post">
    <input name="title" placeholder="Titel">
    <textarea name="content" placeholder="Inhalt"></textarea>
    <input type="submit" value="Hinzufügen">
</form>

<ul>
<?php foreach ($sources as $src): ?>
    <li><?= htmlspecialchars($src['title']) ?> 
        <a href="?delete=<?= $src['id'] ?>">Löschen</a>
    </li>
<?php endforeach; ?>
</ul>

<a href="dashboard.php">Zurück</a>
</body>
</html>
