<?php
// test_setup.php

// 1. Vérifie l'existence du fichier _vote.db
$dbFile = __DIR__ . "/_vote.db";
$dir = dirname($dbFile);

// 2. Vérifie les accès en écriture
echo "\n📝 Accès en écriture\n";
echo "------------------------\n";

echo "Répertoire principal accessible en écriture ? ";
echo is_writable($dir)
    ? "\033[32m✅ Oui\n\033[0m"
    : "\033[31m❌ Non - Vérifiez les droits du répertoire\n\033[0m";

if (file_exists($dbFile)) {
    echo "Fichier _vote.db accessible en écriture ? ";
    echo is_writable($dbFile)
        ? "\033[32m✅ Oui\n\033[0m"
        : "\033[31m❌ Non - Vérifiez les droits\n\033[0m";
}

// 3. Vérifie les extensions PHP nécessaires
echo "\n🧩 Extensions PHP requises\n";
echo "----------------------------\n";

$extensions = [
    "pdo_sqlite" => "Requise pour SQLite",
    "intl" => "Requise pour formatage localisé des dates",
    "bcmath" => "Requise pour Base58",
];

foreach ($extensions as $ext => $desc) {
    echo "$ext : $desc - ";
    echo extension_loaded($ext)
        ? "\033[32m✅ Activée\n\033[0m"
        : "\033[31m❌ Désactivée\n\033[0m";
}

// 5. Résumé final
echo "\n✅ Résumé final\n";
echo "------------------\n";

$problems = [];

if (!is_writable($dir)) {
    $problems[] = "Répertoire parent non accessible en écriture";
}
if (file_exists($dbFile) && !is_writable($dbFile)) {
    $problems[] = "Fichier _vote.db non modifiable";
}
if (!extension_loaded("pdo_sqlite")) {
    $problems[] = "Extension pdo_sqlite désactivée";
}
if (!extension_loaded("intl")) {
    $problems[] = "Extension intl manquante (dates localisées)";
}
if (!extension_loaded("bcmath")) {
    $problems[] = "Extension bcmath manquante (Base58)";
}

if (empty($problems)) {
    echo "\033[32m✔️ Configuration optimale pour le projet\n\033[0m";
} else {
    echo "\033[31m❗ Problèmes détectés :\n\033[0m";
    foreach ($problems as $p) {
        echo "- $p\n";
    }
    exit(1);
}
