<?php
if (basename($_SERVER["PHP_SELF"]) === "_db_connect.php") {
    die("Accès interdit");
}

$dbFile = __DIR__ . "/_vote.db";
if (!file_exists($dbFile)) {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE votes (
        id TEXT PRIMARY KEY,
        title TEXT,
        choice_unanimous TEXT,
        choice_veto TEXT,
        total_voters INTEGER,
        votes_received INTEGER DEFAULT 0,
        status TEXT DEFAULT 'open',
        contestation_duration TEXT DEFAULT '7 days',
        show_results_immediately INTEGER DEFAULT 0,
        contested INTEGER DEFAULT 0,
        new_vote_id TEXT,
        closed_at DATETIME,
        contestation_end DATETIME
    )");
}
$pdo = new PDO("sqlite:" . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
