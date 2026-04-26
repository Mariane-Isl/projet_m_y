<?php
session_start();
header('Content-Type: application/json');

// Vérifiez bien le chemin pour remonter vers classes (3 niveaux : Factures -> LOCAL_API -> Controllers -> classes)
require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Rejet.php';

$database = new Database();
$db = $database->getConnection();

// On récupère l'ID envoyé par le JavaScript
$fid = $_POST['fournisseur_id'] ?? 0;

$contrats = [];
if ($fid > 0) {
    // On appelle la fonction de la classe Rejet
    $contrats = Rejet::getContratsByFournisseur($db, $fid);
}

// On renvoie le résultat en JSON
echo json_encode($contrats);
exit();
