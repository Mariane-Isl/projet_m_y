<?php
// Controllers/LOCAL_API/Rejets/get_contrats.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Rejet.php';

$database = new Database();
$db       = $database->getConnection();

// Récupère l'ID via GET ou POST
$fid = intval($_GET['fournisseur_id'] ?? $_POST['fournisseur_id'] ?? 0);

if ($fid > 0) {
    $result = Rejet::getContratsByFournisseur($db, $fid);
    echo json_encode($result);
} else {
    echo json_encode([]);
}