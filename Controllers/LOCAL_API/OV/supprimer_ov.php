<?php
/**
 * API LOCAL — supprimer_ov.php
 * Chemin : Controllers/LOCAL_API/OV/supprimer_ov.php
 * Supprime l'OV + remet les factures liées au statut RECU
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Session expirée.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Méthode non autorisée.']); exit(); }

$ov_id = isset($_POST['ov_id']) ? intval($_POST['ov_id']) : 0;
if ($ov_id <= 0) { echo json_encode(['success'=>false,'message'=>'ID invalide.']); exit(); }

require_once __DIR__ . '/../../../classes/Database.php';
$database = new Database();
$db       = $database->getConnection();

try {
    $db->beginTransaction();

    // Récupérer les factures liées avant suppression
    $facs = $db->prepare("SELECT Factureid FROM facture_ordres_virement WHERE ordres_virementid = :ov_id");
    $facs->execute([':ov_id' => $ov_id]);
    $facture_ids = $facs->fetchAll(PDO::FETCH_COLUMN);

    // Remettre chaque facture au statut RECU
    if (!empty($facture_ids)) {
        $sid = $db->query("SELECT id FROM statut_facture WHERE code = 'RECU' LIMIT 1")->fetchColumn();
        if ($sid) {
            $hf = $db->prepare("INSERT INTO historique_facture (Factureid, statut_factureid, date_statuts)
                                 VALUES (:fid, :sid, NOW())");
            foreach ($facture_ids as $fid) {
                $hf->execute([':fid' => $fid, ':sid' => $sid]);
            }
        }
    }

    // Supprimer l'OV (CASCADE supprime facture_ordres_virement et historique_status_ov)
    $del = $db->prepare("DELETE FROM ordres_virement WHERE id = :ov_id");
    $del->execute([':ov_id' => $ov_id]);

    $db->commit();
    echo json_encode(['success'=>true,'message'=>'OV supprimé avec succès.']);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("supprimer_ov error: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur base de données.']);
}
