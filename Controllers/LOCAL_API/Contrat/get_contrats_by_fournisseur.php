<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../Classes/Database.php';

// On vérifie maintenant $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fournisseur_id'])) {
    $db = (new Database())->getConnection();
    $fid = intval($_POST['fournisseur_id']);
    
    $stmt = $db->prepare("SELECT id, num_contrat FROM contrat WHERE fournisseur_id = :fid");
    $stmt->execute([':fid' => $fid]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}