<?php
header('Content-Type: application/json');
require_once '../../../Classes/Database.php';
require_once '../../../Classes/Facture.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = (new Database())->getConnection();
    
    // On récupère les données envoyées par JavaScript
    $fourn = $_POST['fournisseur_id'];
    $contr = $_POST['contrat_id'];
    $devise = $_POST['devise'];
    $struct = $_POST['structure_id'];
    
    $factures = Facture::getFacturesPourOV($db, $fourn, $contr, $devise, $struct);
    
    echo json_encode(['success' => true, 'data' => $factures]);
}
?>