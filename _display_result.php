<?php
if (basename($_SERVER["PHP_SELF"]) === "_display_result.php") {
    die("Accès interdit");
}

include "_db_connect.php";

$id = $_GET["id"] ?? "";
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$vote = $stmt->fetch();

if (!$vote) {
    http_response_code(404);
    die("Vote non trouvé");
}

if ($vote["contestation_end"]) {
    try {
        $date = new DateTime($vote["contestation_end"]);
        $contestation_end = $date->format("c");
    } catch (Exception $e) {
        $contestation_end = null;
    }
} else {
    $contestation_end = null;
}
$can_contest =
    $contestation_end && new DateTime() < new DateTime($contestation_end);
$show_results =
    $vote["show_results_immediately"] ||
    (!$can_contest && $contestation_end !== null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($vote["title"]) ?></title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 2em auto;
            padding: 1em;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        .result {
            text-align: center;
            padding: 2em;
            background: #dff0d8;
            border-radius: 8px;
        }
        .radio-buttons {
            display: flex;
            gap: 1em;
            margin: 1.5em 0;
        }
        .radio-button {
            flex: 1;
            min-width: 120px;
            padding: 1em;
            background: #ecf0f1;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .radio-button input {
            display: none;
        }
        .radio-button.active {
            background: #3498db;
            color: white;
        }
        button {
            background: #3498db;
            color: white;
            padding: 0.7em 1.5em;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($vote["title"]) ?></h1>

        <?php if ($vote["contested"]): ?>
            <p>Vote nul : erreur ou triche au nombre de votes.</p>
            <p>Nouveau vote créé : <a href="<?= $vote[
                "new_vote_id"
            ] ?>">Accéder au nouveau vote</a></p>

        <?php elseif ($vote["status"] === "closed"): ?>
            <?php if ($show_results): ?>
                <?php $isUnanimous =
                    $vote["votes_received"] === $vote["total_voters"]; ?>
                <div class="result">
                    <?= $isUnanimous
                        ? "<h2>✅ " .
                            htmlspecialchars($vote["choice_unanimous"]) .
                            "</h2>"
                        : "<h2>❌ " .
                            htmlspecialchars($vote["choice_veto"]) .
                            "</h2>" ?>
                </div>
            <?php else: ?>
                <p>Ce vote est clos. Le résultat sera visible après le délai de contestation.</p>
                <form method="post">
                    <input type="hidden" name="action" value="contest">
                    <input type="hidden" name="vote_id" value="<?= $vote[
                        "id"
                    ] ?>">
                    <button type="submit">Contester : j'aurais dû pouvoir voter</button>
                </form>
                <p>Délai de contestation : <?= htmlspecialchars(
                    $vote["contestation_duration"]
                ) ?></p>
            <?php endif; ?>

        <?php else: ?>
            <p><?= $vote["votes_received"] ?> vote(s) sur <?= $vote[
     "total_voters"
 ] ?> attendus.</p>
            <form method="post">
                <input type="hidden" name="action" value="vote">
                <input type="hidden" name="vote_id" value="<?= $vote["id"] ?>">
                <div class="vote-options">
                    <label class="radio-button active">
                        <input type="radio" name="choice" value="unanimous" required>
                        <span><?= htmlspecialchars(
                            $vote["choice_unanimous"]
                        ) ?></span>
                    </label>
                    <label class="radio-button">
                        <input type="radio" name="choice" value="veto" required>
                        <span><?= htmlspecialchars(
                            $vote["choice_veto"]
                        ) ?></span>
                    </label>
                </div>
                <button type="submit">Valider mon vote</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
