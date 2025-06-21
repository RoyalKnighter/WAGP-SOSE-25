<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in'])) { header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

// Create
if (isset($_POST['word'], $_POST['explanation'])) {
    $stmt = $pdo->prepare("INSERT INTO problem_words (word, explanation) VALUES (?, ?)");
    $stmt->execute([$_POST['word'], $_POST['explanation']]);
}

// Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM problem_words WHERE id=?");
    $stmt->execute([$_GET['delete']]);
}

$words = $pdo->query("SELECT * FROM problem_words")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Problemwörter</title></head>
<body>
<h2>Problemwörter</h2>
<form method="post">
    <input name="word" placeholder="Wort">
    <textarea name="explanation" placeholder="Erklärung"></textarea>
    <input type="submit" value="Hinzufügen">
</form>

<ul>
<?php foreach ($words as $w): ?>
    <li><?= htmlspecialchars($w['word']) ?> 
        <a href="?delete=<?= $w['id'] ?>">Löschen</a>
    </li>
<?php endforeach; ?>
</ul>

<a href="dashboard.php">Zurück</a>
</body>
</html>
