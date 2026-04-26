<?php
// On empêche PHP d'afficher des erreurs HTML qui casseraient le JSON
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// Ajustez les "../" selon la profondeur exacte de vos dossiers !
require_once '../../../Classes/Database.php';
require_once '../../../Classes/Monnaie.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // On instancie la classe avec $db comme le demande votre constructeur
    $monnaieObj = new Monnaie($db);

    // On récupère les données
    $data = $monnaieObj->getAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
