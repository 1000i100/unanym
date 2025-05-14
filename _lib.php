<?php
// _lib.php

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
    "status" => "enum('open', 'closed', 'contested') DEFAULT 'open'",
    "new_vote_id" => "TEXT",
    "closed_at" => "DATETIME",
    "contestation_end" => "DATETIME",
]);

// Générateur dynamique de requête CREATE TABLE
function generate_create_table_sql($table = "votes")
{
    $fields = [];
    foreach (VOTE_SCHEMA as $name => $def) {
        $fields[] = "$name $def";
    }
    return "CREATE TABLE $table (" . implode(", ", $fields) . ")";
}
