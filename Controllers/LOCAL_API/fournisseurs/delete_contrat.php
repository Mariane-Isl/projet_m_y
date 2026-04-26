<?php
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['contrat_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit();
}

$contrat_id = intval($_POST['contrat_id']);

if ($contrat_id <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // ── ÉTAPE 1 : récupérer les IDs des bordereaux liés au contrat
    $stmtBrd = $db->prepare("SELECT id FROM bordereau WHERE Contrat_id = :contrat_id");
    $stmtBrd->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
    $stmtBrd->execute();
    $bordereaux = $stmtBrd->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($bordereaux)) {

        // ── ÉTAPE 2 : récupérer les IDs des factures liées à ces bordereaux
        $phBrd = implode(',', array_fill(0, count($bordereaux), '?'));

        $stmtFac = $db->prepare("SELECT id FROM facture WHERE Bordereau_id IN ($phBrd)");
        $stmtFac->execute($bordereaux);
        $factures = $stmtFac->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($factures)) {
            $phFac = implode(',', array_fill(0, count($factures), '?'));

            // ── ÉTAPE 3 : supprimer facture_ordres_virement
            $db->prepare("DELETE FROM facture_ordres_virement WHERE Factureid IN ($phFac)")
               ->execute($factures);

            // ── ÉTAPE 4 : supprimer facture_rejer
            $db->prepare("DELETE FROM facture_rejer WHERE Factureid IN ($phFac)")
               ->execute($factures);

            // ── ÉTAPE 5 : supprimer historique_facture
            $db->prepare("DELETE FROM historique_facture WHERE Factureid IN ($phFac)")
               ->execute($factures);

            // ── ÉTAPE 6 : supprimer les factures
            $db->prepare("DELETE FROM facture WHERE id IN ($phFac)")
               ->execute($factures);
        }

        // ── ÉTAPE 7 : supprimer historique_borderau
        $db->prepare("DELETE FROM historique_borderau WHERE Bordereauid IN ($phBrd)")
           ->execute($bordereaux);

        // ── ÉTAPE 8 : supprimer les bordereaux
        $db->prepare("DELETE FROM bordereau WHERE id IN ($phBrd)")
           ->execute($bordereaux);
    }

    // ── ÉTAPE 9 : récupérer les IDs des rejets liés au contrat
    $stmtRej = $db->prepare("SELECT id FROM rejet WHERE Contratid = :contrat_id");
    $stmtRej->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
    $stmtRej->execute();
    $rejets = $stmtRej->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($rejets)) {
        $phRej = implode(',', array_fill(0, count($rejets), '?'));

        // ── ÉTAPE 10 : supprimer historique_rejet (enfant de rejet)
        $db->prepare("DELETE FROM historique_rejet WHERE Rejetid IN ($phRej)")
           ->execute($rejets);

        // ── ÉTAPE 11 : supprimer les rejets
        $db->prepare("DELETE FROM rejet WHERE id IN ($phRej)")
           ->execute($rejets);
    }

    // ── ÉTAPE 12 : supprimer l'affectation
    $stmtAff = $db->prepare("DELETE FROM affectation WHERE Contratid = :contrat_id");
    $stmtAff->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
    $stmtAff->execute();

    // ── ÉTAPE 13 : supprimer le contrat
    $stmtCnt = $db->prepare("DELETE FROM contrat WHERE id = :contrat_id");
    $stmtCnt->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
    $stmtCnt->execute();

    $db->commit();

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Contrat supprimé avec succès.']);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Erreur delete_contrat : " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}
?>