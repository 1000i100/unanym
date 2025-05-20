<?php
// _lib.php

// Inclusion du fichier de configuration
include_once "_config.php";

/**
 * Génère une URL complète pour un vote basée sur la configuration de réécriture d'URL
 *
 * @param string $id L'identifiant du vote
 * @return string L'URL formatée complète (avec http/https)
 */
function get_vote_url($id)
{
    global $config;
    
    // Construction du préfixe de l'URL (domaine)
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
    $domain = $_SERVER["HTTP_HOST"];
    $base_path = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/");
    
    // Construction du chemin relatif selon la configuration
    if ($config["url_rewriting"]) {
        $relative_path = "/{$id}";
    } else {
        $relative_path = "/vote.php?id={$id}";
    }
    
    // Assemblage de l'URL complète
    return "{$protocol}://{$domain}{$base_path}{$relative_path}";
}
// Fonction Base58 (nécessite bcmath)
function base58_encode($input)
{
    $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    $base = strlen($alphabet);

    $inputInt = "0";
    for ($i = 0; $i < strlen($input); $i++) {
        $inputInt = bcadd(bcmul($inputInt, "256"), ord($input[$i]));
    }

    $output = "";
    while ($inputInt > 0) {
        $remainder = bcmod($inputInt, $base);
        $output = $alphabet[$remainder] . $output;
        $inputInt = bcdiv($inputInt, $base);
    }

    // Ajout de '1' pour les octets nuls en début
    $leadingZeros = 0;
    while ($leadingZeros < strlen($input) && ord($input[$leadingZeros]) === 0) {
        $leadingZeros++;
    }

    return str_repeat("1", $leadingZeros) . $output;
}

function gen_new_id()
{
    return base58_encode(random_bytes(6));
}
function now()
{
    return new DateTime("now", new DateTimeZone("UTC"));
}

/**
 * Format date to ISO 8601 with UTC timezone marker
 */
function format_date_for_db($dateTime)
{
    if (!($dateTime instanceof DateTime)) {
        return null;
    }

    // Ensure the date is in UTC
    if ($dateTime->getTimezone()->getName() !== "UTC") {
        $dateTime->setTimezone(new DateTimeZone("UTC"));
    }

    // Format with explicit Z to indicate UTC
    return $dateTime->format("Y-m-d\TH:i:s\Z");
}

/**
 * Parse a date from DB ensuring it's interpreted as UTC
 */
function parse_date_from_db($dateString)
{
    if (empty($dateString)) {
        return null;
    }

    // Always interpret dates as UTC
    $date = new DateTime($dateString, new DateTimeZone("UTC"));
    return $date;
}
// Schéma de la base de données
define("VOTE_SCHEMA", [
    "id" => "TEXT PRIMARY KEY",
    "title" => "TEXT",
    "choice_unanimous" => "TEXT",
    "choice_veto" => "TEXT",
    "total_voters" => "INTEGER",
    "contestation_duration" => "TEXT DEFAULT '7 days'",
    "show_results_immediately" => "INTEGER DEFAULT 0",
    "votes_received" => "INTEGER DEFAULT 0",
    "veto_received" => "BOOLEAN DEFAULT FALSE",
    "status" => "TEXT DEFAULT 'open'", // enum('open', 'closed', 'contested')
    "new_vote_id" => "TEXT",
    "closed_at" => "DATETIME",
    "contestation_end" => "DATETIME",
]);

/**
 * Remplace les entités HTML par des espaces
 * 
 * @param string $text Le texte contenant des entités HTML
 * @return string Le texte avec les entités HTML remplacées par des espaces
 */
function remove_html_entities($text)
{
    return preg_replace('/&[^;]+;/', ' ', $text);
}

// Générateur dynamique de requête CREATE TABLE
function generate_create_table_sql($table = "votes")
{
    $fields = [];
    foreach (VOTE_SCHEMA as $name => $def) {
        $fields[] = "$name $def";
    }
    return "CREATE TABLE $table (" . implode(", ", $fields) . ")";
}
