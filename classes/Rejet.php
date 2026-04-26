<?php
class Rejet
{

    private $id;
    private $num_rejet;
    private $cause;
    private $region_dpid;
    private $Contratid;

    public function __construct() {}

    // ==========================================================
    // Récupérer toutes les régions (Structures)
    // ==========================================================
    public static function getAllRegions($db)
    {
        $sql = "SELECT id, label FROM region_dp ORDER BY id ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // Récupérer tous les statuts
    // ==========================================================
    public static function getAllStatuts($db)
    {
        $sql = "SELECT id, label FROM statut_rejet ORDER BY id ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // Récupérer les rejets avec filtres
    // ==========================================================
    public static function getRejetsFiltres($db, $filtres)
    {

        $sql = "SELECT r.id, r.num_rejet, r.cause, 
                       reg.label as region, 
                       c.num_contrat as contrat,
                       sr.label as statut_actuel,
                       hr.date_rejet as date_statut
                FROM Rejet r
                LEFT JOIN region_dp reg ON r.region_dpid = reg.id
                LEFT JOIN contrat c ON r.Contratid = c.id
                LEFT JOIN historique_rejet hr ON r.id = hr.Rejetid 
                     AND hr.date_rejet = (SELECT MAX(date_rejet) FROM historique_rejet WHERE Rejetid = r.id)
                LEFT JOIN statut_rejet sr ON hr.statut_rejetid = sr.id
                WHERE 1=1";

        $params = [];

        // Filtre par numéro de rejet 
        if (!empty($filtres['num_rejet'])) {
            $sql .= " AND r.num_rejet LIKE :num_rejet";
            $params[':num_rejet'] = '%' . $filtres['num_rejet'] . '%';
        }

        // Filtre par région
        if (!empty($filtres['region_dpid']) && $filtres['region_dpid'] !== 'Toutes') {
            $sql .= " AND r.region_dpid = :region_dpid";
            $params[':region_dpid'] = $filtres['region_dpid'];
        }

        // Filtre par contrat
        if (!empty($filtres['Contratid']) && $filtres['Contratid'] !== 'Tous') {
            $sql .= " AND r.Contratid = :Contratid";
            $params[':Contratid'] = $filtres['Contratid'];
        }

        // Filtre par Statut 
        if (!empty($filtres['statut']) && $filtres['statut'] !== 'Toutes') {
            $sql .= " AND sr.label = :statut";
            $params[':statut'] = $filtres['statut'];
        }

        $sql .= " ORDER BY r.id DESC"; // Trie pour avoir les plus récents en premier

        // Exécution sécurisée avec PDO
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // Récupérer les contrats d'un fournisseur (AJAX)
    // ==========================================================
    public static function getContratsByFournisseur($db, $fournisseur_id)
    {
        $sql = "SELECT id, num_Contrat FROM Contrat WHERE Fournisseur_id = :fid";
        $stmt = $db->prepare($sql);
        $stmt->execute(['fid' => $fournisseur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // Récupérer les factures éligibles au rejet 
    // ==========================================================
    public static function getFacturesRejetables($db, $fournisseur_id, $contrat_id, $structure_id)
{
    if (empty($fournisseur_id) || empty($contrat_id) || empty($structure_id)) {
        return [];
    }

    $sql = "SELECT 
                f.id as facture_id,
                f.Num_facture, 
                f.date_facture as date_facture, 
                f.Montant, 
                m.code as monnaie, 
                c.num_Contrat, 
                fr.Nom_Fournisseur,
                str.label as nom_structure,
                sf.label as statut
            FROM facture f
            INNER JOIN bordereau b ON f.Bordereau_id = b.id
            INNER JOIN contrat c ON b.Contrat_id = c.id
            INNER JOIN fournisseur fr ON c.Fournisseur_id = fr.id
            INNER JOIN money m ON f.money_id = m.id
            
        
            INNER JOIN utilisateur u ON b.emeteur_id = u.id         
            INNER JOIN region_dp str ON u.region_dp_id = str.id      
            
            /*  On relie la FACTURE à son historique --- */
            INNER JOIN historique_facture hf ON f.id = hf.Factureid
            INNER JOIN statut_facture sf ON hf.statut_factureid = sf.id
            /* -------------------------------------------------------- */

            WHERE fr.id = :fournisseur_id 
              AND c.id = :contrat_id
              AND str.id = :structure_id
              
              /*  On vérifie le statut de la FACTURE --- */
              AND sf.code = 'RECU' 
              AND hf.date_statuts = (
                  SELECT MAX(date_statuts) 
                  FROM historique_facture 
                  WHERE Factureid = f.id
              )
              
              /* La facture ne doit pas être DÉJÀ dans un rejet */
              AND f.id NOT IN (
                  SELECT Factureid FROM facture_rejer
              )
              
              /* La sécurité reste active : Pas de rejet si un ordre de virement existe déjà */
              AND NOT EXISTS (
                  SELECT 1 FROM facture_ordres_virement WHERE Factureid = f.id
              )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'fournisseur_id' => $fournisseur_id,
        'contrat_id' => $contrat_id,
        'structure_id' => $structure_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public static function getAllWithDetails(PDO $db, array $filters = []): array
    {
        $sql = "SELECT
                r.id,
                r.num_rejet,
                r.cause,
                c.num_Contrat    AS contrat,
                f.Nom_Fournisseur AS fournisseur,
                reg.code         AS region_code,
                reg.label        AS structure_nom,
                sr.label         AS statut_actuel, 
                sr.code          AS statut_code,   
                hr.date_rejet    AS date_statut,
                /* ON RÉCUPÈRE LE NOM DE L'UTILISATEUR DE L'HISTORIQUE */
                CONCAT(u_actuel.nom, ' ', u_actuel.prenom) AS cree_par 
            FROM rejet r
            LEFT JOIN contrat c         ON r.Contratid       = c.id
            LEFT JOIN fournisseur f     ON c.Fournisseur_id  = f.id
            LEFT JOIN region_dp reg     ON r.region_dpid     = reg.id
            /* On isole la dernière ligne d'historique */
            LEFT JOIN (
                SELECT Rejetid, MAX(date_rejet) AS max_date
                FROM historique_rejet GROUP BY Rejetid
            ) last_h ON r.id = last_h.Rejetid
            LEFT JOIN historique_rejet hr ON hr.Rejetid = r.id AND hr.date_rejet = last_h.max_date
            LEFT JOIN statut_rejet sr   ON hr.statut_rejetid = sr.id
            /* JOINTURE AVEC L'UTILISATEUR QUI A FAIT L'ACTION DANS L'HISTORIQUE */
            LEFT JOIN utilisateur u_actuel ON hr.traitant_id = u_actuel.id";

        $conditions = [];
        $params     = [];

        if (!empty($filters['fournisseur']) && $filters['fournisseur'] !== 'Tous') {
            $conditions[] = "f.id = :fournisseur";
            $params['fournisseur'] = $filters['fournisseur'];
        }
        if (!empty($filters['contrat']) && $filters['contrat'] !== 'Tous') {
            $conditions[] = "c.id = :contrat";
            $params['contrat'] = $filters['contrat'];
        }
        if (!empty($filters['structure']) && $filters['structure'] !== 'Toutes') {
            $conditions[] = "r.region_dpid = :structure";
            $params['structure'] = $filters['structure'];
        }
        if (!empty($filters['num_rejet'])) {
            $conditions[] = "r.num_rejet LIKE :num_rejet";
            $params['num_rejet'] = "%" . $filters['num_rejet'] . "%";
        }
        if (!empty($filters['statut']) && $filters['statut'] !== 'Toutes') {
            $conditions[] = "sr.label = :statut";
            $params['statut'] = $filters['statut'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY r.id ORDER BY r.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur SQL getAllWithDetails : " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET DETAILS BY ID (infos + factures)
    // ─────────────────────────────────────────────────────────────────────
    public static function getDetailsById(PDO $db, int $id): array
    {
        $sql = "SELECT
                r.id,
                r.num_rejet,
                r.cause,
                c.num_Contrat,
                f.Nom_Fournisseur,
                reg.code        AS region_code,
                reg.label       AS structure_nom,
                sr.label        AS statut_label,
                sr.code         AS statut_code,
                hr.date_rejet,
                /* ON RÉCUPÈRE LE NOM DE CELUI QUI A FAIT LA DERNIÈRE ACTION */
                u_actuel.nom    AS createur_nom,
                u_actuel.prenom AS createur_prenom
            FROM rejet r
            LEFT JOIN contrat c         ON r.Contratid       = c.id
            LEFT JOIN fournisseur f     ON c.Fournisseur_id  = f.id
            LEFT JOIN region_dp reg     ON r.region_dpid     = reg.id
            LEFT JOIN (
                SELECT Rejetid, MAX(date_rejet) AS max_date
                FROM historique_rejet GROUP BY Rejetid
            ) last_h ON r.id = last_h.Rejetid
            LEFT JOIN historique_rejet hr ON hr.Rejetid = r.id AND hr.date_rejet = last_h.max_date
            LEFT JOIN statut_rejet sr   ON hr.statut_rejetid = sr.id
            /* JOINTURE AVEC L'UTILISATEUR DE L'HISTORIQUE */
            LEFT JOIN utilisateur u_actuel ON hr.traitant_id = u_actuel.id
            WHERE r.id = :id
            LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $rejet = $stmt->fetch(PDO::FETCH_ASSOC);

        // Factures liées au rejet (avec récupération du dernier statut)
        $sqlF = "SELECT f.id, f.Num_facture, f.Montant, f.date_facture,
                    m.code AS monnaie_code,
                    sf.label AS statut_label, sf.code AS statut_code
             FROM facture f
             JOIN facture_rejer fr   ON f.id       = fr.Factureid
             JOIN money m            ON f.money_id = m.id
             LEFT JOIN (
                 SELECT hf.Factureid, hf.statut_factureid
                 FROM historique_facture hf
                 INNER JOIN (
                     SELECT Factureid, MAX(date_statuts) AS max_d
                     FROM historique_facture GROUP BY Factureid
                 ) lf ON lf.Factureid = hf.Factureid AND lf.max_d = hf.date_statuts
                 GROUP BY hf.Factureid
             ) hfl ON hfl.Factureid = f.id
             LEFT JOIN statut_facture sf ON sf.id = hfl.statut_factureid
             WHERE fr.Rejetid = :id";

        $stmtF = $db->prepare($sqlF);
        $stmtF->execute(['id' => $id]);
        $factures = $stmtF->fetchAll(PDO::FETCH_ASSOC);

        return ['infos' => $rejet, 'factures' => $factures];
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET AVAILABLE FACTURES (not yet linked to this rejet)
    // ─────────────────────────────────────────────────────────────────────
   public static function getAvailableFactures(PDO $db, int $rejetId): array
{
    $sql = "SELECT f.id, f.Num_facture, f.Montant, f.date_facture,
                   m.code AS monnaie_code, 
                   sf.label AS statut_label, 
                   sf.code AS statut_code
            FROM facture f
            JOIN money m ON f.money_id = m.id
            
            /* 1. On rejoint le bordereau pour faire le lien avec le Contrat */
            JOIN bordereau b ON f.Bordereau_id = b.id
            
            /* 2. On rejoint le rejet actuel pour lire ses critères */
            JOIN rejet r ON r.id = :rejetId
            
            INNER JOIN historique_facture hf ON f.id = hf.Factureid
            INNER JOIN statut_facture sf ON hf.statut_factureid = sf.id
            WHERE 
                /* Statut actuel doit être RECU */
                sf.code = 'RECU' 
                
                AND hf.date_statuts = (
                    SELECT MAX(date_statuts) 
                    FROM historique_facture 
                    WHERE Factureid = f.id
                )
                
                /* La facture ne doit pas être déjà dans un rejet */
                AND f.id NOT IN (
                    SELECT Factureid FROM facture_rejer
                )
                
                /* ---------------------------------------------------------
                   3. LE FILTRE MAGIQUE : 
                   Le contrat du bordereau = le contrat du rejet 
                   --------------------------------------------------------- */
                AND b.Contrat_id = r.Contratid
                
            ORDER BY f.date_facture DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':rejetId', $rejetId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getAvailableFactures : " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // ADD facture to rejet
    // ─────────────────────────────────────────────────────────────────────
    public static function addFacture(PDO $db, int $rejetId, int $factureId): bool
    {
        try {
            $stmt = $db->prepare(
                "INSERT IGNORE INTO facture_rejer (Rejetid, Factureid) VALUES (:rejet_id, :facture_id)"
            );
            return $stmt->execute(['rejet_id' => $rejetId, 'facture_id' => $factureId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // REMOVE facture from rejet
    // ─────────────────────────────────────────────────────────────────────
    public static function removeFacture(PDO $db, int $rejetId, int $factureId): bool
    {
        $stmt = $db->prepare(
            "DELETE FROM facture_rejer WHERE Rejetid = :rejet_id AND Factureid = :facture_id"
        );
        return $stmt->execute(['rejet_id' => $rejetId, 'facture_id' => $factureId]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UPDATE cause
    // ─────────────────────────────────────────────────────────────────────
    public static function updateCause(PDO $db, int $id, string $cause): bool
    {
        $stmt = $db->prepare("UPDATE rejet SET cause = :cause WHERE id = :id");
        return $stmt->execute(['cause' => $cause, 'id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // RECUPERER (→ CLOS)
    // ─────────────────────────────────────────────────────────────────────
    public static function recuperer(PDO $db, int $rejetId): bool
    {
        $stmt = $db->prepare("SELECT id FROM statut_rejet WHERE code = 'CLOS' LIMIT 1");
        $stmt->execute();
        $statutId = $stmt->fetchColumn();
        if (!$statutId) return false;

        $stmt = $db->prepare(
            "INSERT INTO historique_rejet (statut_rejetid, Rejetid, date_rejet)
             VALUES (:statut_id, :rejet_id, CURDATE())
             ON DUPLICATE KEY UPDATE date_rejet = CURDATE()"
        );
        return $stmt->execute(['statut_id' => $statutId, 'rejet_id' => $rejetId]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE rejet + history + linked factures
    // ─────────────────────────────────────────────────────────────────────
    public static function deleteById(PDO $db, int $id): bool
    {
        try {
            $db->beginTransaction();

            // 1. Récupérer les factures liées pour nettoyer leur historique
            $stmt = $db->prepare("SELECT Factureid FROM facture_rejer WHERE Rejetid = :id");
            $stmt->execute(['id' => $id]);
            $factureIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // 2. Supprimer UNIQUEMENT le statut 'REJETER' pour ces factures dans l'historique
            if (!empty($factureIds)) {
                foreach ($factureIds as $fid) {
                    $sqlDelHist = "DELETE FROM historique_facture 
                               WHERE Factureid = :fid 
                               AND statut_factureid = (SELECT id FROM statut_facture WHERE code = 'REJETER' LIMIT 1)";
                    $db->prepare($sqlDelHist)->execute(['fid' => $fid]);
                }
            }

            // 3. Supprimer les liens dans facture_rejer
            $db->prepare("DELETE FROM facture_rejer WHERE Rejetid = :id")->execute(['id' => $id]);

            // 4. Supprimer tout l'historique lié à ce REJET
            $db->prepare("DELETE FROM historique_rejet WHERE Rejetid = :id")->execute(['id' => $id]);

            // 5. Supprimer le rejet lui-même
            $db->prepare("DELETE FROM rejet WHERE id = :id")->execute(['id' => $id]);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erreur suppression rejet : " . $e->getMessage());
            return false;
        }
    }

    // ==========================================================
    // Générer le prochain numéro de séquence (extrait du VARCHAR)
    // ==========================================================
    public static function getNextNumRejet(PDO $db): int
    {
        // On demande à MySQL de prendre ce qui est après le dernier '/' et de trouver le plus grand
        $stmt = $db->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(num_rejet, '/', -1) AS UNSIGNED)), 0) + 1 FROM rejet");
        return intval($stmt->fetchColumn());
    }


    // ==========================================================
    // Créer le rejet (numRejet est maintenant une STRING)
    // ==========================================================
    public static function create(PDO $db, string $numRejet, string $cause, int $regionId, int $contratId): int
    {
        // On force PDO à lancer une exception en cas d'erreur (doublon, etc.)
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "INSERT INTO rejet (num_rejet, cause, region_dpid, Contratid)
         VALUES (:num_rejet, :cause, :region_id, :contrat_id)"
        );

        $stmt->execute([
            'num_rejet'  => $numRejet,
            'cause'      => $cause,
            'region_id'  => $regionId,
            'contrat_id' => $contratId,
        ]);

        $newId = intval($db->lastInsertId());

        if ($newId === 0) {
            throw new Exception("L'insertion du rejet a échoué, aucun ID généré.");
        }

        return $newId;
    }
}
