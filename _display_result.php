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

// Prépare les données à injecter
$data = [
    "title" => htmlspecialchars($vote["title"]),
    "votes_received" => $vote["votes_received"],
    "total_voters" => $vote["total_voters"],
    "choice_unanimous" => htmlspecialchars($vote["choice_unanimous"]),
    "choice_veto" => htmlspecialchars($vote["choice_veto"]),
    "new_vote_id" => $vote["new_vote_id"] ?: "",
    "contestation_duration" => htmlspecialchars($vote["contestation_duration"]),
    "veto_received" => $vote["veto_received"],
    "show_results_immediately" => $vote["show_results_immediately"],
];

// status open si votes_received < total_voters
// TODO: Implement vote status check

// status contested si new_vote_id et non vide.
// TODO : status contested

// status closed sinon
// et show_results en fonction de show_results_immediately
// et veto si veto_received != 0, unanimous sinon
// TODO : Implement les commentaires

// Possibilité de contestation
// TODO : clean following code to only keep what's needed... and use IntlDateFormatter
$data["can_contest"] =
    $contestation_end && new DateTime() < new DateTime($contestation_end);

// Calcul de la date de fin de contestation
if ($vote["contestation_duration"] === "always") {
    $data["contestation_left"] = "∞";
    $data["contestation_end_human"] = "Contestation infinie";
} elseif ($vote["contestation_end"]) {
    // utilise intl pour formater le temps restant et la date de fin de manière localisé
    // FIXME: Implement date formatting using IntlDateFormatter
    try {
        $date = new DateTime($vote["contestation_end"]);
        $data["contestation_left"] = $interval->format(
            "%R%a jours, %h heures et %i minutes"
        );
        $data["contestation_end_human"] = $date->format("d/m/Y à H:i");
    } catch (Exception $e) {
        $data["contestation_left"] = "";
    }
}
// Gestion du délai restant pour contestation
if (!$data["show_results"] && $vote["status"] === "closed") {
    $now = new DateTime();
    $end = new DateTime($vote["contestation_end"]);
    $interval = $now->diff($end);
    $data["contestation_left"] = $interval->format(
        "%R%a jours, %h heures et %i minutes"
    );
} else {
    $data["contestation_left"] = "";
}

// Chargement du template HTML
$template = file_get_contents(__DIR__ . "/_template.html");

// Création du DOM
$dom = new DOMDocument();
$dom->loadHTML($template, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($dom);

// Gestion des conditions data-show-if

// Remplacement des attributs data-replace, data-replace-href et data-replace-title

// 8. Affichage final
echo $dom->saveHTML();
exit();
