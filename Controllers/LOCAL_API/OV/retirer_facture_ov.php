<?php
/**
 * API LOCAL — retirer_facture_ov.php
 * Chemin : Controllers/LOCAL_API/OV/retirer_facture_ov.php
 * Retire une facture d'un OV + remet son statut → RECU
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit();
}

$ov_id      = isset($_POST['ov_id'])      ? intval($_POST['ov_id'])      : 0;
$facture_id = isset($_POST['facture_id']) ? intval($_POST['facture_id']) : 0;

if ($ov_id <= 0 || $facture_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']); exit();
}

require_once __DIR__ . '/../../../classes/Database.php';

$database = new Database();
$db       = $database->getConnection();

try {
    $db->beginTransaction();

    // Supprimer le lien
    $del = $db->prepare("DELETE FROM facture_ordres_virement
                          WHERE Factureid = :fid AND ordres_virementid = :ov_id");
    $del->execute([':fid' => $facture_id, ':ov_id' => $ov_id]);

    // Remettre le statut facture → RECU
    $statutId = $db->prepare("SELECT id FROM statut_facture WHERE code = 'RECU' LIMIT 1");
    $statutId->execute();
    $sid = $statutId->fetchColumn();

    if ($sid) {
        $hf = $db->prepare("INSERT INTO historique_facture (Factureid, statut_factureid, date_statuts)
                             VALUES (:fid, :sid, NOW())");
        $hf->execute([':fid' => $facture_id, ':sid' => $sid]);
    }

    // Recalculer montant total
    $mont = $db->prepare("SELECT COALESCE(SUM(f.Montant), 0) AS total
                           FROM facture_ordres_virement fov
                           JOIN facture f ON fov.Factureid = f.id
                           WHERE fov.ordres_virementid = :ov_id");
    $mont->execute([':ov_id' => $ov_id]);
    $montant_total = $mont->fetchColumn();

    $db->commit();

    echo json_encode([
        'success'       => true,
        'message'       => 'Facture retirée.',
        'montant_total' => $montant_total,
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("retirer_facture_ov error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
}
