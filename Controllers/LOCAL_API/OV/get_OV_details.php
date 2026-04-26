<?php
/**
 * API LOCAL — get_ov_details.php
 * Chemin : Controllers/LOCAL_API/OV/get_ov_details.php
 * Retourne en JSON : données OV, factures liées, factures éligibles,
 *                   historique statuts, statuts disponibles.
 * Accès : POST uniquement (règle projet)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Sécurité session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit();
}

// POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$ov_id = isset($_POST['ov_id']) ? intval($_POST['ov_id']) : 0;
if ($ov_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID OV invalide.']);
    exit();
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/OV.php';
require_once __DIR__ . '/../../../classes/HistoriqueStatusOV.php';

$database = new Database();
$db       = $database->getConnection();

try {
    // ── 1. Données complètes de l'OV ────────────────────────────
    $sql = "SELECT
                ov.id,
                ov.Num_OV,
                ov.Num_KTP,
                ov.Date_OV,
                nov.code          AS nature_code,
                nov.label         AS nature_label,
                rdp.code          AS structure_code,
                rdp.label         AS structure_label,
                cnt.num_Contrat   AS num_Contrat,
                f.Nom_Fournisseur AS nom_fournisseur,
                f.id              AS fournisseur_id,
                m.code            AS money_code,
                m.label           AS money_label,
                m.id              AS money_id,
                cnt.id            AS contrat_id,
                ov.region_dpid,
                -- Dernier statut
                (
                    SELECT sov.label
                    FROM historique_status_ov hov
                    JOIN statut_ov sov ON hov.statut_OVid = sov.id
                    WHERE hov.ordres_virementid = ov.id
                    ORDER BY hov.date_status_OV DESC
                    LIMIT 1
                ) AS dernier_statut,
                (
                    SELECT hov2.date_status_OV
                    FROM historique_status_ov hov2
                    WHERE hov2.ordres_virementid = ov.id
                    ORDER BY hov2.date_status_OV DESC
                    LIMIT 1
                ) AS date_dernier_statut,
                -- Montant total factures liées
                (
                    SELECT COALESCE(SUM(fc.Montant), 0)
                    FROM facture_ordres_virement fov
                    JOIN facture fc ON fov.Factureid = fc.id
                    WHERE fov.ordres_virementid = ov.id
                ) AS montant_total
            FROM ordres_virement ov
            LEFT JOIN nature_ov   nov ON ov.nature_OVid    = nov.id
            LEFT JOIN region_dp   rdp ON ov.region_dpid    = rdp.id
            LEFT JOIN contrat     cnt ON ov.Contratid       = cnt.id
            LEFT JOIN fournisseur f   ON cnt.Fournisseur_id = f.id
            LEFT JOIN money       m   ON ov.moneyid         = m.id
            WHERE ov.id = :ov_id
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ov_id', $ov_id, PDO::PARAM_INT);
    $stmt->execute();
    $ov = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ov) {
        echo json_encode(['success' => false, 'message' => 'OV introuvable.']);
        exit();
    }

    // ── 2. Factures liées à cet OV ──────────────────────────────
    $sqlLiees = "SELECT
                    f.id,
                    f.Num_facture,
                    f.date_facture,
                    f.Montant,
                    m.code AS devise
                 FROM facture_ordres_virement fov
                 JOIN facture f ON fov.Factureid = f.id
                 JOIN money   m ON f.money_id    = m.id
                 WHERE fov.ordres_virementid = :ov_id
                 ORDER BY f.date_facture ASC";

    $stmtL = $db->prepare($sqlLiees);
    $stmtL->bindParam(':ov_id', $ov_id, PDO::PARAM_INT);
    $stmtL->execute();
    $factures_liees = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    // ── 3. Factures éligibles (même fournisseur, contrat, devise, structure) ──
    $sqlElig = "SELECT
                    f.id,
                    f.Num_facture,
                    f.date_facture,
                    f.Montant
                FROM facture f
                JOIN bordereau b ON f.Bordereau_id = b.id
                JOIN utilisateur u ON b.emeteur_id = u.id
                JOIN contrat c ON b.Contrat_id = c.id
                -- Dernier statut de la facture = RECU
                JOIN historique_facture hf ON f.id = hf.Factureid
                    AND hf.date_statuts = (
                        SELECT MAX(hf2.date_statuts)
                        FROM historique_facture hf2
                        WHERE hf2.Factureid = f.id
                    )
                JOIN statut_facture sf ON hf.statut_factureid = sf.id
                WHERE c.id              = :contrat_id
                  AND f.money_id        = :money_id
                  AND u.region_dp_id    = :region_id
                  AND sf.code           = 'RECU'
                  -- Pas déjà dans un OV
                  AND f.id NOT IN (
                      SELECT fov2.Factureid
                      FROM facture_ordres_virement fov2
                  )
                ORDER BY f.date_facture ASC";

    $stmtE = $db->prepare($sqlElig);
    $stmtE->execute([
        ':contrat_id' => $ov['contrat_id'],
        ':money_id'   => $ov['money_id'],
        ':region_id'  => $ov['region_dpid'],
    ]);
    $factures_eligibles = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    // ── 4. Historique des statuts ────────────────────────────────
    $histoClass = new HistoriqueStatusOV($db);
    $historique  = $histoClass->getHistoryByOV($ov_id);

    // ── 5. Statuts disponibles (tous sauf le statut actuel) ──────
    $sqlStatuts = "SELECT id, code, label FROM statut_ov ORDER BY id ASC";
    $stmtS      = $db->prepare($sqlStatuts);
    $stmtS->execute();
    $statuts_disponibles = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    // ── Réponse JSON ─────────────────────────────────────────────
    echo json_encode([
        'success'             => true,
        'ov'                  => $ov,
        'factures_liees'      => $factures_liees,
        'factures_eligibles'  => $factures_eligibles,
        'historique'          => $historique,
        'statuts_disponibles' => $statuts_disponibles,
    ]);

} catch (PDOException $e) {
    error_log("get_ov_details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
}
