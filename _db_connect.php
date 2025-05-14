<?php
if (basename($_SERVER["PHP_SELF"]) === "_db_connect.php") {
    die("Accès interdit");
}

include "_lib.php";

$dbFile = __DIR__ . "/_vote.db";

if (!file_exists($dbFile)) {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec(generate_create_table_sql());
}

$pdo = new PDO("sqlite:" . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
