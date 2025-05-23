<?php
if (basename($_SERVER["PHP_SELF"]) === "_display_result.php") {
    die("Accès interdit");
}

include_once "_lib.php";
include_once "_db_connect.php";

// S'assurer que le fuseau horaire par défaut est UTC pour toutes les opérations de date
date_default_timezone_set("UTC");

$id = $_GET["id"] ?? "";
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$vote = $stmt->fetch();

if (!$vote) {
    http_response_code(404);
    die("Vote non trouvé");
}

// Préparation des données à injecter dans le template
$data = [
    "title" => htmlspecialchars($vote["title"]),
    "votes_received" => $vote["votes_received"],
    "total_voters" => $vote["total_voters"],
    "status" => $GLOBALS["share"] ? "share" : $vote["status"],
    "choice_unanimous" => htmlspecialchars($vote["choice_unanimous"]),
    "choice_veto" => htmlspecialchars($vote["choice_veto"]),
    "new_vote_id" => $vote["new_vote_id"] ?: "",
    "contestation_duration" => $vote["contestation_duration"],
    "show_results_immediately" => $vote["show_results_immediately"],
    "veto_received" => $vote["veto_received"] ? 1 : 0,
    "id" => $id,
    "vote_link" => get_vote_url($id),
    // Données pour Open Graph
    "current_url" =>
        (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on"
            ? "https"
            : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
];

// Calcul des états de contestation
$contestation_end = null;
if ($vote["contestation_duration"] === "always") {
    $data["contestation_left"] = "∞";
    $data["contestation_end_human"] = "Contestation infinie";
} elseif ($vote["contestation_duration"] === "none") {
    $data["contestation_left"] = "Aucun délai";
    $data["contestation_end_human"] = "Pas de contestation";
} elseif ($vote["contestation_end"]) {
    try {
        $contestation_end = parse_date_from_db($vote["contestation_end"]);
        $now = now();
        $interval = $now->diff($contestation_end);

        // Formatage localisé du délai restant (max 2 unités)
        $parts = [];
        if ($interval->m > 0) {
            $parts[] = $interval->m . " mois";
        }
        if ($interval->d > 0) {
            $parts[] =
                $interval->d . " " . ($interval->d === 1 ? "jour" : "jours");
        }
        if ($interval->h > 0) {
            $parts[] =
                $interval->h . " " . ($interval->h === 1 ? "heure" : "heures");
        }
        if ($interval->i > 0) {
            $parts[] =
                $interval->i .
                " " .
                ($interval->i === 1 ? "minute" : "minutes");
        }
        if ($interval->s > 0 && empty($parts)) {
            $parts[] = 'moins d\'une minute';
        }

        // Garde les 2 premières unités
        $data["contestation_left"] =
            count($parts) > 0 ? implode(", ", array_slice($parts, 0, 2)) : "";

        // Format court de la date de fin
        $formatter = new \IntlDateFormatter(
            "fr_FR",
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            new DateTimeZone(date_default_timezone_get())
        );
        $data["contestation_end_human"] =
            $vote["contestation_duration"] === "always"
                ? "Contestation infinie"
                : $formatter->format($contestation_end);
    } catch (Exception $e) {
        $data["contestation_left"] = "";
        $data["contestation_end_human"] = "";
    }
} else {
    $data["contestation_left"] = "";
    $data["contestation_end_human"] = "";
}

// Déterminer si la contestation est encore possible
$can_contest = false;
if ($vote["status"] === "closed" && $vote["contestation_duration"] !== "none") {
    if ($vote["contestation_duration"] === "always") {
        $can_contest = true;
    } elseif ($vote["contestation_end"]) {
        $now = now();
        $end_time = parse_date_from_db($vote["contestation_end"]);
        $can_contest = $now < $end_time;
    }
}
$data["can_contest"] = $can_contest ? "1" : "0";

// Détermine si les résultats doivent être affichés et si la contestation est possible
$show_results = $vote["show_results_immediately"] || !$can_contest;
$data["show_results"] = $show_results ? "1" : "0";

// Description pour Open Graph
if ($GLOBALS["share"]) {
    $og_description =
        "Partagez ce lien avec les votants pour qu'ils puissent participer au vote.";
} elseif ($vote["status"] === "open") {
    $og_description =
        "Choix proposés : 1️⃣ " .
        $data["choice_unanimous"] .
        " ⚡️ OU ⚡️ 2️⃣ " .
        $data["choice_veto"];
} elseif ($vote["status"] === "closed" && !$show_results) {
    $og_description =
        "Ce vote est clos. Le résultat sera visible à la fin du délai de contestation.";
} elseif ($vote["status"] === "closed" && $show_results) {
    $og_description = $vote["veto_received"]
        ? "Véto reçu : " . $data["choice_veto"]
        : "Unanimité atteinte : " . $data["choice_unanimous"];
} elseif ($vote["status"] === "contested") {
    $og_description =
        "Ce vote est nul. Un nouveau le remplace. Cliquez pour y accéder.";
} else {
    $og_description = "Système de vote unanime ou veto - Unanym";
}
$data["og_description"] = remove_html_entities(
    htmlspecialchars($og_description)
);

// Chargement du template
$template = file_get_contents(__DIR__ . "/_template.html");

// Création du DOM
$dom = new DOMDocument();
$dom->loadHTML($template, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($dom);

// Gestion des conditions data-show-if
foreach ($xpath->query("//*[@data-show-if]") as $element) {
    $condition = $element->getAttribute("data-show-if");
    list($key, $value) = explode("=", $condition, 2);
    $match = isset($data[$key])
        ? (string) $data[$key] === (string) $value
        : false;

    if (!$match) {
        $element->parentNode->removeChild($element);
    }
}

// Remplacement du contenu des balises avec data-replace
foreach ($xpath->query("//*[@data-replace]") as $element) {
    $key = $element->getAttribute("data-replace");
    if (isset($data[$key])) {
        $element->nodeValue = $data[$key];
    }
}

// Remplacement des href via data-replace-href
foreach ($xpath->query("//a[@data-replace-href]") as $element) {
    $key = $element->getAttribute("data-replace-href");
    if (isset($data[$key])) {
        $element->setAttribute("href", $data[$key]);
    }
}

// Gestion des attributs title via data-replace-title
foreach ($xpath->query("//*[@data-replace-title]") as $element) {
    $key = $element->getAttribute("data-replace-title");
    if (isset($data[$key])) {
        $element->setAttribute("title", $data[$key]);
    }
}

// Gestion du contenu des meta tags (pour Open Graph)
foreach ($xpath->query("//*[@data-replace-content]") as $element) {
    $key = $element->getAttribute("data-replace-content");
    if (isset($data[$key])) {
        $element->setAttribute("content", $data[$key]);
    }
}

echo $dom->saveHTML();
exit();
