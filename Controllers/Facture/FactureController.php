<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Facture.php';
require_once __DIR__ . '/../../classes/Fournisseur.php';

$db = (new Database())->getConnection();

$action = $_POST['action'] ?? '';

// ── ACTION 1 : RECHERCHER ─────────────────────────────────────────────────
if ($action === 'rechercher_factures') {
    $_SESSION['facture_search_results'] = Facture::searchFactures($db, $_POST);
    $_SESSION['facture_search_inputs']  = $_POST;
    header("Location: ../../Pages/Facture/Recherche_Facture.php");
    exit();
}

// ── ACTION 2 : VOIR DÉTAILS ───────────────────────────────────────────────
if ($action === 'view_details') {
    $fid = intval($_POST['facture_id'] ?? 0);
    $_SESSION['dossier_facture_id'] = $fid;
    header("Location: ../../Pages/Facture/Details_Facture.php");
    exit();
}

// ── PRÉPARATION DES DONNÉES COMMUNES ─────────────────────────────────────
$listeFournisseurs = Fournisseur::getAll($db);
// --- Vers la ligne 30 ---
$searchResults = $_SESSION['facture_search_results'] ?? [];
$searchInputs  = $_SESSION['facture_search_inputs']  ?? [];

// ON NE FAIT PAS LE UNSET ICI ! 
// On le fera seulement si une NOUVELLE recherche est lancée (en haut du fichier)

// Déterminer si une recherche a été faite
$recherche_effectuee = !empty($searchInputs);
// ── PRÉPARATION DES DONNÉES DÉTAIL ───────────────────────────────────────
$id_facture = $_SESSION['dossier_facture_id'] ?? 0;
$facture    = null;
$historique = [];
$ov         = null;
$historique_ov = [];
$historique_bordereau = [];
$statut_actuel = '';

if ($id_facture > 0) {
    $facture    = Facture::getDossierComplet($db, $id_facture);
    $historique = Facture::getHistoriqueComplet($db, $id_facture);

    if ($facture) {
        // Statut actuel = premier élément de l'historique (le plus récent)
        $statut_actuel = (!empty($historique) && isset($historique[0]['statut_label']))
            ? $historique[0]['statut_label']
            : 'En attente';

        // ── OV associé à cette facture ────────────────────────────────
        $stmtOV = $db->prepare(
            "SELECT ov.id, ov.Num_OV, ov.Num_KTP, ov.Date_OV,
                    s.label AS statut_ov_label, s.code AS statut_ov_code,
                    h.date_status_OV,
                    u.nom AS user_nom, u.prenom AS user_prenom,
                    TIMESTAMPDIFF(DAY, ov.Date_OV, COALESCE(h.date_status_OV, NOW())) AS duree
             FROM facture_ordres_virement fov
             JOIN ordres_virement ov    ON fov.ordres_virementid = ov.id
             LEFT JOIN (
                 SELECT ordres_virementid, statut_OVid, date_status_OV
                 FROM historique_status_ov h1
                 WHERE date_status_OV = (
                     SELECT MAX(date_status_OV)
                     FROM historique_status_ov h2
                     WHERE h2.ordres_virementid = h1.ordres_virementid
                 )
             ) h ON h.ordres_virementid = ov.id
             LEFT JOIN statut_ov s      ON h.statut_OVid        = s.id
             LEFT JOIN utilisateur u    ON ov.region_dpid        = u.region_dp_id
             WHERE fov.Factureid = :fid
             LIMIT 1"
        );
        $stmtOV->execute(['fid' => $id_facture]);
        $ov = $stmtOV->fetch(PDO::FETCH_ASSOC);

        // ── Historique OV ─────────────────────────────────────────────
        if ($ov) {
            $stmtHOV = $db->prepare(
                "SELECT s.label AS statut_label, h.date_status_OV,
                        u.nom AS user_nom, u.prenom AS user_prenom,
                        TIMESTAMPDIFF(DAY, LAG(h.date_status_OV) OVER (ORDER BY h.date_status_OV), h.date_status_OV) AS duree
                 FROM historique_status_ov h
                 JOIN statut_ov s       ON h.statut_OVid = s.id
                 LEFT JOIN utilisateur u ON u.id = (
                     SELECT emeteur_id FROM bordereau WHERE id = :brd_id LIMIT 1
                 )
                 WHERE h.ordres_virementid = :ov_id
                 ORDER BY h.date_status_OV DESC"
            );
            $stmtHOV->execute(['ov_id' => $ov['id'], 'brd_id' => $facture['Bordereau_id']]);
            $historique_ov = $stmtHOV->fetchAll(PDO::FETCH_ASSOC);
        }

        // ── Traçabilité Bordereau ─────────────────────────────────────
        $stmtBRD = $db->prepare(
            "SELECT b.num_bordereau, hb.date_historique,
                    sb.label AS statut_label, sb.code AS statut_code,
                    u.nom AS user_nom, u.prenom AS user_prenom,
                    TIMESTAMPDIFF(DAY,
                        LAG(hb.date_historique) OVER (ORDER BY hb.date_historique),
                        hb.date_historique
                    ) AS duree
             FROM historique_borderau hb
             JOIN bordereau b          ON hb.Bordereauid      = b.id
             JOIN statut_borderau sb   ON hb.statut_borderauid = sb.id
             LEFT JOIN utilisateur u   ON b.emeteur_id         = u.id
             WHERE b.id = :brd_id
             ORDER BY hb.date_historique DESC"
        );
        $stmtBRD->execute(['brd_id' => $facture['Bordereau_id']]);
        $historique_bordereau = $stmtBRD->fetchAll(PDO::FETCH_ASSOC);
    }
}
