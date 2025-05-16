<?php
// test_setup.php

// Active l'affichage de toutes les erreurs pour ce script uniquement
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

// Vérifie si le script s'exécute dans un navigateur
if (PHP_SAPI !== "cli" && !isset($_SERVER["REQUEST_METHOD"])) {
    echo "Ce script doit être exécuté via un navigateur web ou en ligne de commande.";
    exit(1);
}

// Détermine si nous sommes dans un navigateur ou en CLI
$isCli = PHP_SAPI === "cli";
$newline = $isCli ? "\n" : "<br>";

// Style conditionnel selon l'environnement
function styleText($text, $color)
{
    global $isCli;
    if ($isCli) {
        $colors = [
            "red" => "\033[31m",
            "green" => "\033[32m",
            "yellow" => "\033[33m",
            "reset" => "\033[0m",
        ];
        return $colors[$color] . $text . $colors["reset"];
    } else {
        $htmlColors = [
            "red" => '<span style="color:red;font-weight:bold">',
            "green" => '<span style="color:green;font-weight:bold">',
            "yellow" => '<span style="color:orange;font-weight:bold">',
            "reset" => "</span>",
        ];
        return $htmlColors[$color] . $text . $htmlColors["reset"];
    }
}

// Configure l'output selon l'environnement
if (!$isCli) {
    header("Content-Type: text/html; charset=utf-8");
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Test Configuration Unanym</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo "<style>
        body{font-family:monospace;margin:20px;line-height:1.5;max-width:1200px;margin:0 auto;padding:20px}
        pre{white-space:pre-wrap;word-break:break-all;background:#f5f5f5;padding:10px;border-radius:4px;}
        details{margin:10px 0;border:1px solid #eee;padding:10px;border-radius:4px;}
        summary{cursor:pointer;font-weight:bold;}
    </style></head><body>";
}

// Fonction pour afficher des informations détaillées
function debugInfo($title, $data)
{
    global $newline, $isCli;
    echo styleText("🔍 {$title}", "yellow") . $newline;
    if (is_array($data) || is_object($data)) {
        if ($isCli) {
            print_r($data);
            echo $newline;
        } else {
            echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
        }
    } else {
        if (!is_string($data)) {
            $data = var_export($data, true);
        }
        echo htmlspecialchars((string) $data) . $newline;
    }
    echo $newline;
}

// Fonction pour échapper en toute sécurité le contenu pour l'affichage HTML
function safeEcho($content)
{
    global $isCli;
    if ($isCli) {
        echo $content;
    } else {
        echo htmlspecialchars($content, ENT_QUOTES, "UTF-8");
    }
}

// 0. Vérifie la version de PHP
echo "🔍 Version de PHP$newline";
echo "----------------$newline";
$requiredVersion = "8.0.0";
$recommendedVersion = "8.1.0";
$phpVersion = PHP_VERSION;
$versionCheck = version_compare($phpVersion, $requiredVersion, ">=");
$recommendedCheck = version_compare($phpVersion, $recommendedVersion, ">=");

echo "Version installée : $phpVersion$newline";
echo "Version minimale requise : $requiredVersion$newline";
echo "Version recommandée : $recommendedVersion ou supérieure$newline";
echo "Version suffisante ? ";
echo $versionCheck
    ? styleText("✅ Oui", "green") . $newline
    : styleText("❌ Non - Veuillez mettre à jour PHP", "red") . $newline;
echo "Version recommandée ? ";
echo $recommendedCheck
    ? styleText("✅ Oui", "green") . $newline
    : styleText(
            "⚠️ Non - Envisagez une mise à jour pour de meilleures performances",
            "yellow"
        ) . $newline;

// 1. Vérifie l'existence du fichier _vote.db
$dbFile = __DIR__ . "/_vote.db";
$dir = dirname($dbFile);

// 2. Vérifie les accès en écriture
echo $newline . "📝 Accès en écriture" . $newline;
echo "------------------------" . $newline;

echo "Répertoire principal accessible en écriture ? ";
echo is_writable($dir)
    ? styleText("✅ Oui", "green") . $newline
    : styleText("❌ Non - Vérifiez les droits du répertoire", "red") . $newline;

if (file_exists($dbFile)) {
    echo "Fichier _vote.db accessible en écriture ? ";
    echo is_writable($dbFile)
        ? styleText("✅ Oui", "green") . $newline
        : styleText("❌ Non - Vérifiez les droits", "red") . $newline;
}

// 3. Vérifie les extensions PHP nécessaires
echo $newline . "🧩 Extensions PHP requises" . $newline;
echo "----------------------------" . $newline;

$extensions = [
    "pdo_sqlite" => "Requise pour SQLite",
    "intl" => "Requise pour formatage localisé des dates",
    "bcmath" => "Requise pour Base58",
    "curl" => "Optionnelle - pour le test de réécriture d'URL",
];

foreach ($extensions as $ext => $desc) {
    echo "$ext : $desc - ";
    if ($ext === "curl") {
        echo extension_loaded($ext)
            ? styleText("✅ Activée", "green") . $newline
            : styleText("⚠️ Désactivée", "yellow") . $newline;
    } else {
        echo extension_loaded($ext)
            ? styleText("✅ Activée", "green") . $newline
            : styleText("❌ Désactivée", "red") . $newline;
    }
}

// 4. Vérifie si la réécriture d'URL est activée
echo $newline . "🔄 Réécriture d'URL" . $newline;
echo "----------------" . $newline;

$urlRewriteTest = null; // null = indéterminé, false = non fonctionnel, true = fonctionnel

// Vérifie si l'extension curl est disponible
if (extension_loaded("curl")) {
    $rewriteTestId = "rewrite";
    $host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "localhost";
    $protocol =
        isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on"
            ? "https"
            : "http";
    $baseUrl = $protocol . "://" . $host;
    // Pas besoin de reconstruire le chemin, on teste directement à la racine
    $testUrl = $baseUrl . "/" . $rewriteTestId;

    echo "Test de réécriture : " . $testUrl . $newline;

    // Tente d'accéder à une URL qui devrait être réécrite avec timeout
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 secondes
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout de 3 secondes
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ne pas vérifier le certificat SSL pour les tests locaux
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ne pas vérifier le nom d'hôte SSL pour les tests locaux
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);

    if (curl_errno($ch)) {
        echo styleText(
            "⚠️ Erreur CURL : " .
                curl_error($ch) .
                " (code: " .
                curl_errno($ch) .
                ")",
            "yellow"
        ) . $newline;
        // Afficher des informations détaillées sur l'erreur
        debugInfo("Détails supplémentaires de l'erreur cURL", [
            "Type d'erreur" => curl_errno($ch),
            "Message" => curl_error($ch),
            "URL testée" => $testUrl,
        ]);
        $urlRewriteTest = null; // Indéterminé
    } else {
        $httpCode = $info["http_code"];
        $headerSize = $info["header_size"];
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Vérifie si la réponse est "Vote non trouvé" (404) ce qui indiquerait une réécriture fonctionnelle
        $urlRewriteTest =
            $httpCode == 404 && strpos($body, "Vote non trouvé") !== false;

        // Affiche des informations détaillées pour le débogage
        if ($urlRewriteTest === false) {
            debugInfo("Réponse détaillée", [
                "Code HTTP" => $httpCode,
                "Headers" => $header,
                "Corps de la réponse" =>
                    substr($body, 0, 500) . (strlen($body) > 500 ? "..." : ""),
                "Contient 'Vote non trouvé'" =>
                    strpos($body, "Vote non trouvé") !== false ? "Oui" : "Non",
            ]);
        }
    }
    curl_close($ch);
} else {
    $urlRewriteTest = null; // Indéterminé
}

echo "Réécriture d'URL fonctionnelle ? ";
if ($urlRewriteTest === true) {
    echo styleText("✅ Oui", "green") . $newline;

    // Vérification et mise à jour du fichier _config.php pour la réécriture d'URL
    $configFile = __DIR__ . "/_config.php";
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        $isConfigured = preg_match(
            '/"url_rewriting"\s*=>\s*true/i',
            $configContent
        );

        echo "Réécriture d'URL activée dans la config ? ";
        if ($isConfigured) {
            echo styleText("✅ Oui", "green") . $newline;
        } else {
            echo styleText("⚠️ Non", "yellow") . $newline;

            if (is_writable($configFile)) {
                // Remplace "url_rewriting" => false par "url_rewriting" => true
                $configContent = preg_replace(
                    '/"url_rewriting"\s*=>\s*false/i',
                    '"url_rewriting" => true',
                    $configContent
                );
                file_put_contents($configFile, $configContent);
                echo styleText(
                    "✅ Configuration mise à jour : réécriture d'URL activée dans _config.php",
                    "green"
                ) . $newline;
            } else {
                echo styleText(
                    "⚠️ Impossible de mettre à jour _config.php automatiquement",
                    "yellow"
                ) . $newline;
            }
        }
    } else {
        echo styleText(
            "⚠️ Impossible de mettre à jour _config.php automatiquement",
            "yellow"
        ) . $newline;
    }
} elseif ($urlRewriteTest === false) {
    echo styleText("❌ Non", "yellow") . $newline;

    // Détection du type de serveur
    $serverType = "unknown";
    if (isset($_SERVER["SERVER_SOFTWARE"])) {
        $serverSoftware = strtolower($_SERVER["SERVER_SOFTWARE"]);
        if (strpos($serverSoftware, "apache") !== false) {
            $serverType = "apache";
        } elseif (strpos($serverSoftware, "nginx") !== false) {
            $serverType = "nginx";
        } elseif (strpos($serverSoftware, "litespeed") !== false) {
            $serverType = "litespeed";
        } else {
            $serverType = "other";
        }
    }
    echo "Type de serveur détecté : " . $serverType . $newline;

    if ($serverType === "apache" || $serverType === "litespeed") {
        echo "La réécriture d'URL ne fonctionne pas correctement.$newline";
        echo styleText("📋 Suggestions pour Apache:", "yellow") . $newline;
        echo "1. Vérifiez que mod_rewrite est activé$newline";
        echo "2. Vérifiez que AllowOverride est configuré correctement$newline";
        echo "3. Vérifiez le contenu du fichier .htaccess$newline";
    } elseif ($serverType === "nginx") {
        echo "La réécriture d'URL ne fonctionne pas correctement.$newline";
        echo styleText("📋 Suggestions pour Nginx:", "yellow") . $newline;
        echo "1. Vérifiez votre configuration dans /etc/nginx/sites-available/$newline";
        echo "2. Assurez-vous d'avoir les directives try_files appropriées$newline";
        echo "3. Redémarrez Nginx après modification$newline";
    } else {
        echo "La réécriture d'URL ne fonctionne pas correctement.$newline";
        echo "Consultez la documentation de votre serveur web pour configurer la réécriture d'URL.$newline";
    }
    // Vérification du fichier de configuration selon le type de serveur
    if ($serverType === "apache" || $serverType === "litespeed") {
        $htaccess = __DIR__ . "/.htaccess";
        if (file_exists($htaccess)) {
            echo "Fichier .htaccess présent : " .
                styleText("✅ Oui", "green") .
                $newline;
            echo "Contenu du fichier .htaccess :$newline";
            echo "<pre>" .
                htmlspecialchars(file_get_contents($htaccess)) .
                "</pre>$newline";
        } else {
            echo "Fichier .htaccess absent : " .
                styleText("⚠️ Attention", "yellow") .
                $newline;
            echo "Cela explique pourquoi la réécriture d'URL ne fonctionne pas.$newline";
        }
    } elseif ($serverType === "nginx") {
        $nginx_example = __DIR__ . "/_nginx_vhost_conf_example.conf";
        echo "Référence de configuration : fichier _nginx_vhost_conf_example.conf présent$newline";
        echo "Contenu du fichier exemple pour Nginx :$newline";
        echo "<pre>" .
            htmlspecialchars(file_get_contents($nginx_example)) .
            "</pre>" .
            $newline;
    }
} else {
    echo styleText(
        "⚠️ Indéterminé (nécessite l'extension CURL pour tester)",
        "yellow"
    ) . $newline;
}

// 5. Vérification de la base de données
echo $newline . "🗄️ Vérification de la base de données" . $newline;
echo "------------------------------" . $newline;

try {
    // Tente de se connecter à la base de données
    $testPdo = new PDO("sqlite:" . $dbFile);
    $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données : " .
        styleText("✅ Réussie", "green") .
        $newline;

    // Vérifie la structure de la base de données
    $tables = $testPdo
        ->query("SELECT name FROM sqlite_master WHERE type='table'")
        ->fetchAll(PDO::FETCH_COLUMN);

    if (in_array("votes", $tables)) {
        echo "Table 'votes' : " . styleText("✅ Existe", "green") . $newline;

        // Vérifie les colonnes de la table votes
        $columns = $testPdo
            ->query("PRAGMA table_info(votes)")
            ->fetchAll(PDO::FETCH_ASSOC);
        echo "Structure de la table votes : " .
            count($columns) .
            " colonnes trouvées$newline";

        // Récupération du schéma défini dans _lib.php
        include_once "_lib.php";
        if (defined("VOTE_SCHEMA")) {
            // Comparaison du schéma avec la structure réelle
            $schemaErrors = [];
            $schemaWarnings = [];

            // Vérification des colonnes manquantes dans la base de données
            foreach (array_keys(VOTE_SCHEMA) as $expectedColumn) {
                $found = false;
                foreach ($columns as $actualColumn) {
                    if ($actualColumn["name"] === $expectedColumn) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $schemaErrors[] = "Colonne '{$expectedColumn}' définie dans VOTE_SCHEMA mais absente de la base de données";
                }
            }

            // Vérification des colonnes supplémentaires dans la base de données
            foreach ($columns as $actualColumn) {
                if (!array_key_exists($actualColumn["name"], VOTE_SCHEMA)) {
                    $schemaWarnings[] = "Colonne '{$actualColumn["name"]}' présente dans la base de données mais non définie dans VOTE_SCHEMA";
                }
            }

            if (!empty($schemaErrors)) {
                echo styleText(
                    "❌ Erreurs de structure de la base de données détectées:",
                    "red"
                ) . $newline;
                foreach ($schemaErrors as $error) {
                    echo "- $error$newline";
                }
            } elseif (!empty($schemaWarnings)) {
                echo styleText(
                    "⚠️ Avertissements sur la structure de la base de données:",
                    "yellow"
                ) . $newline;
                foreach ($schemaWarnings as $warning) {
                    echo "- $warning$newline";
                }
            } else {
                echo styleText(
                    "✅ La structure de la base de données correspond au schéma défini",
                    "green"
                ) . $newline;
            }
        } else {
            echo styleText(
                "⚠️ Impossible de vérifier la cohérence avec le schéma: VOTE_SCHEMA non défini",
                "yellow"
            ) . $newline;
        }

        // Affiche les détails des colonnes
        echo "<pre>";
        foreach ($columns as $column) {
            echo "- {$column["name"]} ({$column["type"]})$newline";
        }
        echo "</pre>";
    } else {
        echo "Table 'votes' : " .
            styleText("❌ N'existe pas", "red") .
            $newline;
    }
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " .
        styleText($e->getMessage(), "red") .
        $newline;
    debugInfo("Détails de l'erreur PDO", [
        "Message" => $e->getMessage(),
        "Code" => $e->getCode(),
        "Trace" => $e->getTraceAsString(),
    ]);
}

// 6. Informations système additionnelles
echo $newline . "🖥️ Informations système" . $newline;
echo "----------------------" . $newline;

echo "Système d'exploitation: " . PHP_OS . $newline;
echo "Architecture: " . php_uname("m") . $newline;
echo "Serveur web: " .
    ($_SERVER["SERVER_SOFTWARE"] ?? "Non détecté") .
    $newline;
echo "Interface PHP: " . php_sapi_name() . $newline;
echo "Limite de mémoire PHP: " . ini_get("memory_limit") . $newline;
echo "Limite de temps d'exécution: " .
    ini_get("max_execution_time") .
    " secondes$newline";
echo "Répertoire de travail: " . getcwd() . $newline;
echo "Chemin du script: " . __FILE__ . $newline;

// 7. Résumé final
echo $newline . "✅ Résumé final" . $newline;
echo "------------------" . $newline;

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
if (!extension_loaded("curl")) {
    $warnings[] =
        "Extension curl manquante (test de réécriture d'URL impossible)";
}

// Avertissements (non bloquants)
if ($urlRewriteTest === false) {
    $warnings[] = "La réécriture d'URL ne fonctionne pas correctement";
} elseif ($urlRewriteTest === null) {
    $warnings[] =
        "Impossible de déterminer si la réécriture d'URL fonctionne correctement";
}

// Ajouter les erreurs de schéma de base de données si détectées
if (!empty($schemaErrors ?? [])) {
    foreach ($schemaErrors as $error) {
        $problems[] = "Erreur de schéma de la base de données: " . $error;
    }
}
if (!empty($schemaWarnings ?? [])) {
    foreach ($schemaWarnings as $warning) {
        $warnings[] =
            "Avertissement de schéma de la base de données: " . $warning;
    }
}

if (empty($problems) && empty($warnings)) {
    echo styleText("✔️ Configuration optimale pour le projet", "green") .
        $newline;
} elseif (empty($problems) && !empty($warnings)) {
    echo styleText(
        "⚠️ Configuration acceptable avec avertissements :",
        "yellow"
    ) . $newline;
    foreach ($warnings as $w) {
        echo "- $w$newline";
    }

    echo "$newline" .
        styleText(
            "ℹ️ Vous pouvez quand même utiliser l'application, mais certaines fonctionnalités pourraient être limitées.",
            "yellow"
        ) .
        $newline;
} else {
    echo styleText("❗ Problèmes critiques détectés :", "red") . $newline;
    foreach ($problems as $p) {
        echo "- $p$newline";
    }

    if (!empty($warnings)) {
        echo $newline . styleText("⚠️ Avertissements :", "yellow") . $newline;
        foreach ($warnings as $w) {
            echo "- $w$newline";
        }
    }

    echo "$newline" .
        styleText(
            "⚠️ L'application ne fonctionnera pas correctement tant que les problèmes ci-dessus ne seront pas résolus.",
            "red"
        ) .
        $newline;

    // Ne pas quitter si le script est exécuté dans un navigateur
    if ($isCli) {
        exit(1);
    }
}

// Afficher les détails de la configuration PHP
echo $newline . "📋 Détails de la configuration PHP" . $newline;
echo "-------------------------------$newline";
echo "<details><summary>" .
    "Cliquez pour afficher les paramètres PHP détaillés" .
    "</summary><pre>";
phpinfo();
echo "</pre></details>";

// Fermer la balise HTML si on est dans un navigateur
if (!$isCli) {
    echo "</body></html>";
}
