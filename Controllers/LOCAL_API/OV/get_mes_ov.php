<?php
/**
 * API LOCAL — get_mes_ov.php
 * Chemin : Controllers/LOCAL_API/OV/get_mes_ov.php
 *
 * ── PROBLÈME 1 RÉSOLU ──────────────────────────────────────────
 * Retourne uniquement les OV liés aux contrats affectés
 * à l'utilisateur connecté ($_SESSION['user_id']).
 *
 * CAUSE RACINE de l'absence : pas d'API POST dédiée.
 * La méthode OV::getAllWithDetailsByUser() existait dans le Modèle
 * mais n'était jamais appelée depuis aucun endpoint.
 * ───────────────────────────────────────────────────────────────
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/OV.php';

$database = new Database();
$db       = $database->getConnection();

// L'user_id vient TOUJOURS de la session, jamais du POST
// → Règle de sécurité : un utilisateur ne peut pas demander les OV d'un autre
$user_id = (int) $_SESSION['user_id'];

try {
    $ovModel = new OV($db);
    $ovs     = $ovModel->getAllWithDetailsByUser($user_id);

    echo json_encode([
        'success' => true,
        'ovs'     => $ovs,
        'total'   => count($ovs),
    ]);

} catch (PDOException $e) {
    error_log("get_mes_ov error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
}
