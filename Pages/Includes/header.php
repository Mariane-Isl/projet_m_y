<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Suivi Factures DP' : 'Suivi Factures DP' ?>
    </title>
    <?php
    /*
     * Calcul fiable du chemin vers dist/ quel que soit la profondeur de la page.
     * header.php est dans Pages/Includes/ → ../../ = racine du projet (où se trouve dist/).
     * On convertit en URL relative depuis DOCUMENT_ROOT.
     */
    $projectRoot = realpath(__DIR__ . '/../../');
    $docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
    $baseUrl     = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    $cssBase     = $baseUrl . '/dist/css/';
    $jsBase      = $baseUrl . '/dist/js/';
    ?>
    <link rel="stylesheet" href="<?= $cssBase ?>bootstrap.min.css">
    <link rel="stylesheet" href="<?= $cssBase ?>Font_Google.css">
    <link rel="stylesheet" href="<?= $cssBase ?>dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= $cssBase ?>dashbord.css">
    <script src="<?= $jsBase ?>sweetalert2@11.js"></script>
</head>

<body>