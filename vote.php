<?php
include_once "_db_connect.php";

// Gestion des actions via POST uniquement
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
} else {
    $action = $_GET["id"] ? "display" : "home";
}

switch ($action) {
    case "vote":
        include "_handle_vote.php";
        break;
    case "contest":
        include "_contest.php";
        break;
    case "create":
        include "_create_vote.php";
        break;
    case "display":
        include "_process_template.php";
        break;
    default:
        header("Location: ./");
        exit();
}
