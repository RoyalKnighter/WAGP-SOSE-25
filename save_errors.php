<?php
require_once 'config.php';
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $word = $_POST['word'] ?? '';
    $explanation = $_POST['explanation'] ?? '';

    if ($word && $explanation) {
        $stmt = $pdo->prepare("INSERT INTO problem_words (word, explanation) VALUES (?, ?)");
        $stmt->execute([$word, $explanation]);
    }
}
