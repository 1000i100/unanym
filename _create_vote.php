<?php
if (basename($_SERVER["PHP_SELF"]) === "_create_vote.php") {
    die("Accès interdit");
}

include_once "_lib.php";
include_once "_db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = gen_new_id();
    $title = $_POST["title"];
    $unanimous = $_POST["choice_unanimous"];
    $veto = $_POST["choice_veto"];
    $total = (int) $_POST["total_voters"];
    $contestation = $_POST["contestation_duration"];
    $show_results = $_POST["show_results_immediately"];

    // Blocage de "always" si résultats non immédiats
    if ($contestation === "always" && !$show_results) {
        die(
            "Erreur : La contestation infinie n'est autorisée que si les résultats s'affichent dès le dernier vote reçu."
        );
    }

    // Utilise VOTE_SCHEMA pour générer les champs dynamiquement
    $columns = array_keys(VOTE_SCHEMA);
    $placeholders = array_map(fn($col) => "?", $columns);

    $stmt = $pdo->prepare(
        "INSERT INTO votes (" .
            implode(", ", $columns) .
            ")
         VALUES (" .
            implode(", ", $placeholders) .
            ")"
    );

    $stmt->execute([
        $id,
        $title,
        $unanimous,
        $veto,
        $total,
        $contestation,
        $show_results,
        0, // votes_received
        0, // veto_received
        "open", // status
        null, // new_vote_id
        null, // closed_at
        null, // contestation_end
    ]);

    header("Location: ./$id");
    exit();
}
