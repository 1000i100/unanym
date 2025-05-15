<?php
if (basename($_SERVER["PHP_SELF"]) === "_contest.php") {
    die("Accès interdit");
}

include_once "_lib.php";
include_once "_db_connect.php";

$id = $_GET["id"] ?? "";
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$vote = $stmt->fetch();

if (!$vote || $vote["contested"]) {
    http_response_code(400);
    die("Vote invalide ou déjà contesté");
}

$new_id = gen_new_id();

// Prépare les champs à insérer en base
$columns = [];
$values = [];
$params = [];

// Champs à conserver du vote original
$fieldsToKeep = [
    "title",
    "choice_unanimous",
    "choice_veto",
    "total_voters",
    "contestation_duration",
    "show_results_immediately",
];

foreach (array_keys(VOTE_SCHEMA) as $field) {
    $columns[] = $field;
    $values[] = "?";

    if ($field === "id") {
        $params[] = $new_id;
    } elseif (in_array($field, $fieldsToKeep)) {
        $params[] = $vote[$field]; // Garder les champs importants
    } elseif ($field === "status") {
        $params[] = "open"; // Nouveau vote toujours ouvert
    } elseif ($field === "votes_received") {
        $params[] = 0; // Réinitialiser les votes
    } elseif ($field === "veto_received") {
        $params[] = false; // Réinitialiser le véto
    } else {
        $params[] = null; // Pour tout autre champ, mettre à null
    }
}

// Génération de la requête d'insertion
$insert_sql =
    "INSERT INTO votes (" .
    implode(", ", $columns) .
    ") VALUES (" .
    implode(", ", $values) .
    ")";
$stmt = $pdo->prepare($insert_sql);
$stmt->execute($params);

// Marque le vote original comme contesté
$pdo->exec(
    "UPDATE votes SET status = 'contested', new_vote_id = '$new_id' WHERE id = '$id'"
);

header("Location: ./$id");
exit();
