<?php
/**
 * API LOCAL — changer_statut_ov.php
 * Chemin : Controllers/LOCAL_API/OV/changer_statut_ov.php
 *
 * CORRECTIONS :
 *  1. La validation des transitions doit être AVANT l'INSERT, pas après
 *  2. $pdo utilisé alors que la variable s'appelle $db
 *  3. $codeNouveauStatut n'était pas récupéré
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Vérifications préliminaires ────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$ov_id     = isset($_POST['ov_id'])     ? intval($_POST['ov_id'])     : 0;
$statut_id = isset($_POST['statut_id']) ? intval($_POST['statut_id']) : 0;

if ($ov_id <= 0 || $statut_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
    exit();
}

require_once __DIR__ . '/../../../classes/Database.php';
$db = (new Database())->getConnection();

try {

    // ── 1. Vérifier que le statut CIBLE existe et récupérer son code ──
    $chk = $db->prepare("SELECT code, label FROM statut_ov WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $statut_id]);
    $statutCible = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$statutCible) {
        echo json_encode(['success' => false, 'message' => 'Statut cible introuvable.']);
        exit();
    }
    $codeNouveauStatut = $statutCible['code'];
    $label             = $statutCible['label'];

    // ── 2. Récupérer le statut ACTUEL de l'OV ─────────────────────────
    $stmtCurrent = $db->prepare("
        SELECT sov.code
        FROM historique_status_ov h
        JOIN statut_ov sov ON h.statut_OVid = sov.id
        WHERE h.ordres_virementid = :id
        ORDER BY h.date_status_OV DESC
        LIMIT 1
    ");
    $stmtCurrent->execute([':id' => $ov_id]);
    $statutActuel = $stmtCurrent->fetchColumn();

    if (!$statutActuel) {
        echo json_encode(['success' => false, 'message' => 'OV introuvable ou sans historique.']);
        exit();
    }

    // ── 3. Récupérer la devise de l'OV ────────────────────────────────
    $stmtDevise = $db->prepare("
        SELECT m.code
        FROM ordres_virement ov
        JOIN money m ON ov.moneyid = m.id
        WHERE ov.id = :id
        LIMIT 1
    ");
    $stmtDevise->execute([':id' => $ov_id]);
    $devise = $stmtDevise->fetchColumn();
    $estDZD = ($devise === 'DZD');

    // ── 4. Vérifier la transition via road_map ─────────────────────────
    // On utilise la table road_map au lieu d'un tableau PHP codé en dur
    // → la logique métier est centralisée en base de données
    $stmtRoad = $db->prepare("
        SELECT COUNT(*) 
        FROM road_map rm
        JOIN statut_ov d ON rm.statut_depart = d.id
        JOIN statut_ov f ON rm.statut_final  = f.id
        WHERE d.code = :depart
          AND f.code = :final
    ");
    $stmtRoad->execute([
        ':depart' => $statutActuel,
        ':final'  => $codeNouveauStatut,
    ]);
    $transitionAutorisee = (int) $stmtRoad->fetchColumn() > 0;

    // Règle métier DZD : bloquer ATF et ADB_ATF
    if ($estDZD && in_array($codeNouveauStatut, ['ATF', 'ADB_ATF'])) {
        echo json_encode([
            'success' => false,
            'message' => "Transition interdite pour la devise DZD : l'étape « $label » ne s'applique pas.",
        ]);
        exit();
    }

    if (!$transitionAutorisee) {
        echo json_encode([
            'success' => false,
            'message' => "Transition « $statutActuel → $codeNouveauStatut » non autorisée.",
        ]);
        exit();
    }

    // ── 5. Tout est valide → insérer dans l'historique ────────────────
    $ins = $db->prepare("
        INSERT INTO historique_status_ov
            (ordres_virementid, statut_OVid, date_status_OV)
        VALUES
            (:ov_id, :stat_id, NOW())
    ");
    $ins->execute([':ov_id' => $ov_id, ':stat_id' => $statut_id]);

    echo json_encode([
        'success' => true,
        'label'   => $label,
        'date'    => date('d/m/Y H:i'),
        'message' => 'Statut mis à jour : ' . $label,
    ]);

} catch (PDOException $e) {
    error_log("changer_statut_ov error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
}