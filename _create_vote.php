<?php
if (basename($_SERVER['PHP_SELF']) === '_create_vote.php') die('Accès interdit');

include '_db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = bin2hex(random_bytes(10));
    $title = $_POST['title'];
    $unanimous = $_POST['choice_unanimous'];
    $veto = $_POST['choice_veto'];
    $total = (int)$_POST['total_voters'];
    $contestation = $_POST['contestation_duration'];
    $show_results = isset($_POST['show_results_immediately']) ? 1 : 0;

    // Si les résultats ne s'affichent pas immédiatement, bloque "always"
    if ($contestation === 'always' && !$show_results) {
        die("Erreur : La contestation infinie n'est autorisée que si les résultats s'affichent dès le dernier vote reçu.");
    }

    $stmt = $pdo->prepare("INSERT INTO votes
        (id, title, choice_unanimous, choice_veto, total_voters, contestation_duration, show_results_immediately)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $title, $unanimous, $veto, $total, $contestation, $show_results]);

    header("Location: ./$id");
    exit;
}
