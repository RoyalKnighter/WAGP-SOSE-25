<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in'])) {
    header("Location: index.php");
    exit;
}

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

$sources = $pdo->query("SELECT content FROM sources")->fetchAll(PDO::FETCH_COLUMN);
$problems = $pdo->query("SELECT word, explanation FROM problem_words")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anzahl = (int)$_POST['paare'];

    $prompt = "Erstelle $anzahl einzigartige Paare aus Fachbegriff und Erklärung im JSON-Format:\n\n";
    $prompt .= "Beispiel:\n[{\"term\": \"Osmose\", \"definition\": \"Diffusion von Wasser durch eine semipermeable Membran.\"}, ...]\n\n";
    $prompt .= "Bitte füge keien Sonderzeichen in die json ein, da javascript diese sonst icht zu einer json umwandeln kann.";
    $prompt .= "Basierend auf diesen Quellen:\n\n" . implode("\n", $sources);
    $prompt .= "\n\nZusätzliche Problemwörter:\n";
    foreach ($problems as $p) {
        $prompt .= $p['word'] . ": " . $p['explanation'] . "\n";
    }

    // API Request an OpenAI
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-4o",
        "messages" => [["role" => "user", "content" => $prompt]],
        "max_tokens" => 1000
    ]));
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    $antwort = $result['choices'][0]['message']['content'];

    // Entferne umgebende ```json ... ``` falls vorhanden
    $antwort = preg_replace('/^```json|```$/m', '', trim($antwort));
    $antwort = trim($antwort);

    // JSON dekodieren
    $memoryData = json_decode($antwort, true);


    if (!$memoryData) {
        $error = "Konnte JSON nicht verarbeiten.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Memory</title>
</head>

<body>
    <h2>Memory erstellen</h2>

    <form method="post">
        <label>Anzahl Paare:</label>
        <input type="number" name="paare" required>
        <input type="submit" value="Erstellen">
    </form>

    <?php if (isset($memoryData) && is_array($memoryData)): ?>
        <h3>Memory Spiel</h3>

        <div id="memory-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <?php
            $items = [];
            $pairId = 0;

            foreach ($memoryData as $pair) {
                $term = htmlspecialchars($pair['term']);
                $definition = htmlspecialchars($pair['definition']);

                // Begriff-Button
                $items[] = [
                    'value' => $term,
                    'pair_id' => $pairId,
                    'type' => 'term'
                ];

                // Definition-Button
                $items[] = [
                    'value' => $definition,
                    'pair_id' => $pairId,
                    'type' => 'definition'
                ];

                $pairId++;
            }
            shuffle($items);
            foreach ($items as $i => $item):
            ?>
                <button
                    data-id="<?= $i ?>"
                    data-value="<?= $item['value'] ?>"
                    data-type="<?= $item['type'] ?>"
                    data-pair-id="<?= $item['pair_id'] ?>"
                    onclick="reveal(this)">
                    <?= htmlspecialchars($item['value']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <style>
            #memory-grid button {
                min-height: 80px;
                padding: 10px;
                white-space: normal;
                font-size: 16px;
                background-color: lightgrey;
            }
        </style>

        <script>
            let flipped = [];

            function reveal(button) {
                if (flipped.length === 2 || button.disabled) return;

                button.style.backgroundColor = "yellow";
                flipped.push(button);

                if (flipped.length === 2) {
                    const [a, b] = flipped;
                    const typeA = a.dataset.type;
                    const typeB = b.dataset.type;
                    const pairA = a.dataset.pairId;
                    const pairB = b.dataset.pairId;
                    const valA = a.dataset.value;
                    const valB = b.dataset.value;

                    const isMatch = (pairA === pairB);
                    const isValidPair = (typeA !== typeB);

                    if (isMatch && isValidPair) {
                        a.disabled = true;
                        b.disabled = true;

                        // Visuelles Feedback (grün)
                        a.style.backgroundColor = '#c7f7c7';
                        b.style.backgroundColor = '#c7f7c7';

                        flipped = [];
                    } else {
                        if (isValidPair) {
                            var pairId = a.dataset.pairId;

                            // Alle Buttons mit dieser pairId finden
                            var allButtons = document.querySelectorAll(`[data-pair-id='${pairId}']`);

                            let correctTerm = '';
                            let correctDefinition = '';

                            allButtons.forEach(btn => {
                                if (btn.dataset.type === 'term') correctTerm = btn.dataset.value;
                                if (btn.dataset.type === 'definition') correctDefinition = btn.dataset.value;
                            });

                            // 1. Was der Nutzer für ein Wort hielt, bekommt die echte Erklärung
                            fetch('save_errors.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    word: correctTerm,
                                    explanation: correctDefinition
                                })
                            });

                            pairId = b.dataset.pairId;

                            // Alle Buttons mit dieser pairId finden
                            allButtons = document.querySelectorAll(`[data-pair-id='${pairId}']`);

                            correctTerm = '';
                            correctDefinition = '';

                            allButtons.forEach(btn => {
                                if (btn.dataset.type === 'term') correctTerm = btn.dataset.value;
                                if (btn.dataset.type === 'definition') correctDefinition = btn.dataset.value;
                            });

                            // 1. Was der Nutzer für ein Wort hielt, bekommt die echte Erklärung
                            fetch('save_errors.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    word: correctTerm,
                                    explanation: correctDefinition
                                })
                            });
                        }

                        a.style.backgroundColor = '#f8d7da';
                        b.style.backgroundColor = '#f8d7da';
                    }

                    // Zurücksetzen nach kurzer Zeit
                    setTimeout(() => {
			            if (!a.disabled || !b.disabled) {
                        	a.style.backgroundColor = "lightgrey";
                        	b.style.backgroundColor = "lightgrey";
			            }
                        flipped = [];
                    }, 1000);
                }

                if (isMatch && isValidPair) {
                    flipped = []; // nur zurücksetzen, wenn matched
                }
            }
        </script>
    <?php endif; ?>


    <a href="dashboard.php">Zurück</a>
</body>

</html>
