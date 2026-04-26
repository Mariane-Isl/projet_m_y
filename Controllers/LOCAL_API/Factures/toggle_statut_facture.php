<?php
// ════════════════════════════════════════════════════════════════
// 
// RÔLE    : Enregistre le statut RECU ou INTROUVABLE d'une facture
//
// CORRECTIONS APPORTÉES :
//   1. Suppression du DELETE → on garde l'historique complet
//   2. CURDATE() remplacé par NOW() → enregistre date ET heure
// ════════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
require_once '../../../classes/Database.php';

// Vérification de la requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['facture_id'], $_POST['trouve'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit();
}

$facture_id = intval($_POST['facture_id']);
$trouve     = ($_POST['trouve'] === '1');

if ($facture_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID facture invalide.']);
    exit();
}

try {
    $database = new Database();
    $db       = $database->getConnection();

    // Codes réels en BDD : RECU = Trouvé, INTROUVABLE = Non trouvé
    $codeStatut = $trouve ? 'RECU' : 'INTROUVABLE';

    $stmtStatut = $db->prepare("SELECT id FROM statut_facture WHERE code = :code LIMIT 1");
    $stmtStatut->bindParam(':code', $codeStatut, PDO::PARAM_STR);
    $stmtStatut->execute();
    $statut_id = $stmtStatut->fetchColumn();

    if (!$statut_id) {
        $stmtAll = $db->prepare("SELECT id, code, label FROM statut_facture ORDER BY id ASC");
        $stmtAll->execute();
        $statuts = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        $codesDispos = implode(', ', array_column($statuts, 'code'));

        echo json_encode([
            'success' => false,
            'message' => "Statut '$codeStatut' introuvable. Codes disponibles : $codesDispos"
        ]);
        exit();
    }

    // ── CORRECTION 1 : On N'efface PLUS l'ancien statut ──
    // Avant : DELETE FROM historique_facture WHERE Factureid = :id  ← SUPPRIMÉ
    // Maintenant : on insert directement, l'historique est conservé

    // ── CORRECTION 2 : NOW() au lieu de CURDATE() → date + heure ──
    $stmtIns = $db->prepare(
        "INSERT INTO historique_facture (Factureid, statut_factureid, date_statuts)
         VALUES (:facture_id, :statut_id, NOW())"
    );
    $stmtIns->bindParam(':facture_id', $facture_id, PDO::PARAM_INT);
    $stmtIns->bindParam(':statut_id',  $statut_id,  PDO::PARAM_INT);
    $stmtIns->execute();

    $label = $trouve ? 'Trouvé' : 'Non trouvé';
    echo json_encode(['success' => true, 'trouve' => $trouve, 'label' => $label]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>