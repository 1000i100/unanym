<?php
if (basename($_SERVER["PHP_SELF"]) === "_handle_vote.php") {
    die("Accès interdit");
}

include_once "_db_connect.php";
include_once "_lib.php";

$id = $_GET["id"] ?? "";
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$vote = $stmt->fetch();

if (!$vote || $vote["status"] !== "open") {
    http_response_code(400);
    die("Vote invalide ou fermé");
}

// Incrémente le nombre de votes
$stmt = $pdo->prepare(
    "UPDATE votes SET votes_received = votes_received + 1 WHERE id = ?"
);
$stmt->execute([$id]);

// Si le vote est "veto", marque-le dans la base
if ($_POST["choice"] === "veto") {
    $stmt = $pdo->prepare("UPDATE votes SET veto_received = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

// Récupère le vote avec son compteur mis à jour
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$updated_vote = $stmt->fetch();

// Si ce n'est pas le dernier vote, redirige vers vote_received.html
if ($updated_vote["votes_received"] < $updated_vote["total_voters"]) {
    header("Location: ./thanks.html");
    exit();
}

// Si c'est le dernier vote, calcule la fin de contestation
$stmt = $pdo->prepare(
    "UPDATE votes SET status = 'closed', closed_at = datetime('now') WHERE id = ?"
);
$stmt->execute([$id]);

if ($vote["contestation_duration"] === "none") {
    // Pas de contestation, mettre fin immédiatement
    $now = now();
    $stmt = $pdo->prepare("UPDATE votes SET contestation_end = ? WHERE id = ?");
    $stmt->execute([format_date_for_db($now), $id]);
} elseif ($vote["contestation_duration"] !== "always") {
    // Calculer la date de fin de contestation avec PHP
    $now = now();
    $endDate = clone $now;

    // Déterminer la durée à ajouter
    switch ($vote["contestation_duration"]) {
        case "5 minutes":
            $endDate->add(new DateInterval("PT5M"));
            break;
        case "1 hour":
            $endDate->add(new DateInterval("PT1H"));
            break;
        case "1 day":
            $endDate->add(new DateInterval("P1D"));
            break;
        case "7 days":
            $endDate->add(new DateInterval("P7D"));
            break;
        case "15 days":
            $endDate->add(new DateInterval("P15D"));
            break;
        case "1 month":
            $endDate->add(new DateInterval("P1M"));
            break;
        case "1 year":
            $endDate->add(new DateInterval("P1Y"));
            break;
    }

    // Mettre à jour la date de fin de contestation
    $stmt = $pdo->prepare("UPDATE votes SET contestation_end = ? WHERE id = ?");
    $stmt->execute([format_date_for_db($endDate), $id]);
}

header("Location: ./$id");
exit();
