<?php
header('Content-Type: application/json');
require_once '../../../Classes/Database.php';
require_once '../../../Classes/Region.php'; // Ajustez le chemin vers votre classe Region

try {
    $db = (new Database())->getConnection();
    $regions = Region::getAll($db); // Retourne un tableau d'objets Region

    // On transforme les objets en tableau associatif pour le JSON
    $data = [];
    foreach ($regions as $r) {
        $data[] = [
            'id' => $r->getId(),
            'code' => $r->getCode(),
            'label' => $r->getLabel()
        ];
    }
 
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
