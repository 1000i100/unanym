<pre>
<?php
// test_setup.php

// 0. Vérifie la version de PHP
echo "🔍 Version de PHP\n";
echo "----------------\n";
$requiredVersion = "8.0.0";
$recommendedVersion = "8.1.0";
$phpVersion = PHP_VERSION;
$versionCheck = version_compare($phpVersion, $requiredVersion, ">=");
$recommendedCheck = version_compare($phpVersion, $recommendedVersion, ">=");

echo "Version installée : $phpVersion\n";
echo "Version minimale requise : $requiredVersion\n";
echo "Version recommandée : $recommendedVersion ou supérieure\n";
echo "Version suffisante ? ";
echo $versionCheck
    ? "\033[32m✅ Oui\n\033[0m"
    : "\033[31m❌ Non - Veuillez mettre à jour PHP\n\033[0m";
echo "Version recommandée ? ";
echo $recommendedCheck
    ? "\033[32m✅ Oui\n\033[0m"
    : "\033[33m⚠️ Non - Envisagez une mise à jour pour de meilleures performances\n\033[0m";

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

// 4. Vérifie si la réécriture d'URL est activée
echo "\n🔄 Réécriture d'URL\n";
echo "----------------\n";

$urlRewriteTest = false;
$rewriteTestId = "rewrite";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "localhost";
$protocol =
    isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
$baseUrl = $protocol . "://" . $host;
$pathParts = explode("/", $_SERVER["PHP_SELF"]);
array_pop($pathParts); // Retire test_setup.php
$basePath = implode("/", $pathParts);
$testUrl = $baseUrl . $basePath . "/" . $rewriteTestId;

echo "Test de réécriture : " . $testUrl . "\n";

// Tente d'accéder à une URL qui devrait être réécrite
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Vérifie si la réponse est "Vote non trouvé" (404) ce qui indiquerait une réécriture fonctionnelle
$urlRewriteTest =
    $httpCode == 404 && strpos($response, "Vote non trouvé") !== false;

echo "Réécriture d'URL fonctionnelle ? ";
if ($urlRewriteTest) {
    echo "\033[32m✅ Oui\n\033[0m";
} else {
    echo "\033[33m⚠️ Non\n\033[0m";
    echo "La réécriture d'URL ne semble pas fonctionner correctement.\n";
    echo "Vérifiez que votre serveur web est configuré correctement.\n";
}

// 5. Résumé final
echo "\n✅ Résumé final\n";
echo "------------------\n";

$problems = [];
$warnings = [];

if (!$versionCheck) {
    $problems[] = "Version PHP insuffisante (requise: $requiredVersion, installée: $phpVersion)";
} elseif (!$recommendedCheck) {
    $warnings[] = "Version PHP en-dessous de la recommandation (recommandée: $recommendedVersion, installée: $phpVersion)";
}
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

// Avertissements (non bloquants)
if (!$urlRewriteTest) {
    $warnings[] = "La réécriture d'URL ne semble pas fonctionner correctement";
}
if (empty($problems) && empty($warnings)) {
    echo "\033[32m✔️ Configuration optimale pour le projet\n\033[0m";
} elseif (empty($problems) && !empty($warnings)) {
    echo "\033[33m⚠️ Configuration acceptable avec avertissements :\n\033[0m";
    foreach ($warnings as $w) {
        echo "- $w\n";
    }
} else {
    echo "\033[31m❗ Problèmes détectés :\n\033[0m";
    foreach ($problems as $p) {
        echo "- $p\n";
    }

    if (!empty($warnings)) {
        echo "\n\033[33m⚠️ Avertissements :\n\033[0m";
        foreach ($warnings as $w) {
            echo "- $w\n";
        }
    }

    exit(1);
}

