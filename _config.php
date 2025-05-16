<?php
if (basename($_SERVER["PHP_SELF"]) === "_config.php") {
    die("Accès interdit"); // Protection contre l'accès direct
}

/**
 * Configuration de l'application Unanym
 */

// Configuration générale
$config = [
    // Si activé, utilise /xxx au lieu de vote.php?id=xxx
    // avant d'activer, vérifiez que la réécriture d'URL fonctionne.
    "url_rewriting" => false,
];
