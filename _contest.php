<?php
if (basename($_SERVER['PHP_SELF']) === '_contest.php') die('Accès interdit');

include '_db_connect.php';

$id = $_POST['vote_id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM votes WHERE id = ?");
$stmt->execute([$id]);
$vote = $stmt->fetch();

if (!$vote || $vote['contested']) {
    http_response_code(400);
    die("Vote invalide ou déjà contesté");
}

$new_id = bin2hex(random_bytes(10));
$stmt = $pdo->prepare("INSERT INTO votes
    (id, title, choice_unanimous, choice_veto, total_voters, contestation_duration, show_results_immediately, new_vote_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $new_id, $vote['title'], $vote['choice_unanimous'], $vote['choice_veto'],
    $vote['total_voters'], $vote['contestation_duration'], $vote['show_results_immediately'], $id
]);

$stmt = $pdo->prepare("UPDATE votes SET contested = 1 WHERE id = ?");
$stmt->execute([$id]);

header("Location: ./$id");
exit;
