<?php
// Controllers/LOCAL_API/Rejets/search_factures.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Rejet.php';

$database = new Database();
$db       = $database->getConnection();

// On accepte les données en POST normal ou en JSON (fetch)
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$f_id = intval($data['fournisseur_id'] ?? 0);
$c_id = intval($data['contrat_id'] ?? 0);
$s_id = intval($data['structure_id'] ?? 0);

if ($f_id > 0 && $c_id > 0 && $s_id > 0) {
    $resultats = Rejet::getFacturesRejetables($db, $f_id, $c_id, $s_id);
    echo json_encode([
        'success' => true,
        'data' => $resultats
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Veuillez sélectionner un fournisseur, un contrat et une structure.'
    ]);
}