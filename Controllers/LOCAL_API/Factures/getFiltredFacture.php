<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
 
require_once '../../../Classes/Database.php';
require_once '../../../Classes/Facture.php';

try {
    $db = (new Database())->getConnection();

    $fournisseur_id = $_POST['fournisseur_id'] ?? '';
    $contrat_id     = $_POST['contrat_id'] ?? '';
    $devise_code    = $_POST['devise_id'] ?? '';
    $region_dp_id   = $_POST['structure_id'] ?? '';

    if (empty($fournisseur_id)) {
        $msg = "Fournisseur absent";
    } elseif (empty($contrat_id)) {
        $msg = "Contrat absent";
    } elseif (empty($devise_code)) {
        $msg = "Devise absente";
    } elseif (empty($region_dp_id)) {
        $msg = "Structure absente";
    }

    if (isset($msg)) {
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    $factures = Facture::getFilteredForVirement($db, $fournisseur_id, $contrat_id, $region_dp_id, $devise_code);
    echo json_encode(['success' => true, 'data' => $factures]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
