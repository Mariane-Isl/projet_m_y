<?php
header('Content-Type: application/json');

require_once '../../../classes/Database.php'; // Correction minuscule 'classes'
require_once '../../../classes/Rejet.php';

$db = (new Database())->getConnection();

// On récupère les filtres envoyés par DataTables
$filtres = [
    'fournisseur' => $_POST['fournisseur_id'] ?? '',
    'num_rejet'   => $_POST['num_rejet'] ?? '',
    'structure'   => $_POST['region_dpid'] ?? '', // On aligne la clé sur le modèle
    'contrat'     => $_POST['Contratid'] ?? '',   // On aligne la clé sur le modèle
    'statut'      => $_POST['statut'] ?? ''       // On aligne la clé sur le modèle
];

try {
    // ON APPELLE LA NOUVELLE FONCTION (Celle que vous avez dans VS Code)
    $rejets = Rejet::getAllWithDetails($db, $filtres);

    echo json_encode([
        "data" => $rejets
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
