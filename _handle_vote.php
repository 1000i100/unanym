<?php
if (basename($_SERVER["PHP_SELF"]) === "_handle_vote.php") {
    die("Accès interdit");
}

include_once "_db_connect.php";

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
    $stmt = $pdo->prepare(
        "UPDATE votes SET contestation_end = datetime('now') WHERE id = ?"
    );
    $stmt->execute([$id]);
} elseif ($vote["contestation_duration"] !== "always") {
    match ($vote["contestation_duration"]) {
        "5 minutes" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+5 minutes') WHERE id = ?"
        )),
        "1 hour" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+1 hour') WHERE id = ?"
        )),
        "1 day" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+1 day') WHERE id = ?"
        )),
        "7 days" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+7 days') WHERE id = ?"
        )),
        "15 days" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+15 days') WHERE id = ?"
        )),
        "1 month" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+1 month') WHERE id = ?"
        )),
        "1 year" => ($stmt = $pdo->prepare(
            "UPDATE votes SET contestation_end = datetime('now', '+1 year') WHERE id = ?"
        )),
    };
    $stmt->execute([$id]);
}

header("Location: ./$id");
exit();
