<?php
if (basename($_SERVER["PHP_SELF"]) === "_contest.php") {
    die("Accès interdit");
}

include "_lib.php";
include "_db_connect.php";

$id = $_POST["vote_id"] ?? "";
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

foreach (array_keys(VOTE_SCHEMA) as $field) {
    if ($field === "id") {
        $columns[] = $field;
        $values[] = "?";
        $params[] = $new_id;
    } else {
        $columns[] = $field;
        $values[] = "?";
        $params[] = $vote[$field];
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
