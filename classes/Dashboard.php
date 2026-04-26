<?php

class Dashboard {

    /**
     * Récupère les 4 statistiques principales pour le haut du tableau de bord
     */
    public static function getGlobalStats(PDO $db): array {
        $stats = [];

        // 1. Nombre total de TOUTES les factures
        $stmt = $db->query("SELECT COUNT(*) AS total FROM facture");
        $stats['total_factures'] = (int)$stmt->fetch()['total'];

        // 2. Chiffre d'affaires total (UNIQUEMENT les factures PAYÉES et en DZD)
        $sqlCA = "
            SELECT COALESCE(SUM(f.Montant), 0) AS ca_total
            FROM facture f
            JOIN money m ON m.id = f.money_id
            JOIN (
                SELECT Factureid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS last_statut_id
                FROM historique_facture 
                GROUP BY Factureid
            ) hf_last ON hf_last.Factureid = f.id
            JOIN statut_facture sf ON sf.id = hf_last.last_statut_id
            WHERE m.code = 'DZD' AND sf.code = 'PAYE'
        ";
        $stmt = $db->query($sqlCA);
        $stats['ca_total_dzd'] = (float)$stmt->fetch()['ca_total'];

        // 3. Fournisseurs actifs
        $stmt = $db->query("SELECT COUNT(DISTINCT Fournisseur_id) AS total FROM contrat");
        $stats['fournisseurs_actifs'] = (int)$stmt->fetch()['total'];

        // 4. Contrats en cours
        $stmt = $db->query("SELECT COUNT(*) AS total FROM contrat");
        $stats['contrats_en_cours'] = (int)$stmt->fetch()['total'];

        return $stats;
    }

    // --- METHODES DE LISTES (FILTRES) ---

    public static function getFournisseurs(PDO $db): array {
        return $db->query("SELECT id, Nom_Fournisseur, code FROM fournisseur ORDER BY Nom_Fournisseur")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getContratsByFournisseur(PDO $db, int $fournisseur_id): array {
        $stmt = $db->prepare("SELECT id, num_Contrat FROM contrat WHERE Fournisseur_id = :fid ORDER BY num_Contrat");
        $stmt->execute([':fid' => $fournisseur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getMonnaies(PDO $db): array {
        return $db->query("SELECT id, code, label FROM money ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStructures(PDO $db): array {
        return $db->query("SELECT id, code, label FROM region_dp ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStatutsFacture(PDO $db): array {
        return $db->query("SELECT id, code, label FROM statut_facture ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStatutsOV(PDO $db): array {
        return $db->query("SELECT id, code, label FROM statut_ov ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getGestionnaires(PDO $db): array {
        return $db->query("SELECT id, CONCAT(nom,' ',COALESCE(prenom,'')) AS nom_complet FROM utilisateur WHERE active=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- METHODES DE RAPPORTS COMPLEXES ---

    /**
     * Rapport principal des factures (Correction effectuée sur le gestionnaire)
     */
    public static function getRapportFactures(PDO $db, array $filtres): array {
        
        $sql = "
            SELECT
                fac.id, 
                fac.Num_facture, 
                fac.date_facture, 
                fac.Montant,
                m.code AS monnaie, 
                cnt.num_Contrat, 
                f.Nom_Fournisseur, 
                rdp.label AS structure, 
                last_sf.label AS statut_facture, 
                last_sf.code AS statut_code,
                latest_hf.max_date AS date_statut, 
                ov.Num_OV, 
                last_sov.label AS statut_ov,
                last_sov.code AS statut_ov_code, 
                latest_hov.max_date AS date_statut_ov,
                CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS gestionnaire
            FROM facture fac
            LEFT JOIN money m ON m.id = fac.money_id
            LEFT JOIN bordereau brd ON brd.id = fac.Bordereau_id
            LEFT JOIN contrat cnt ON cnt.id = brd.Contrat_id
            LEFT JOIN fournisseur f ON f.id = cnt.Fournisseur_id
            
            /* --- EXTRACTION DU DERNIER STATUT ET DU DERNIER UTILISATEUR DE L'HISTORIQUE --- */
            LEFT JOIN (
                SELECT Factureid, 
                       CAST(SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS UNSIGNED) AS last_statut_id,
                       CAST(SUBSTRING_INDEX(GROUP_CONCAT(utilisateur_id ORDER BY date_statuts DESC), ',', 1) AS UNSIGNED) AS last_gestionnaire_id,
                       MAX(date_statuts) AS max_date
                FROM historique_facture 
                GROUP BY Factureid
            ) latest_hf ON latest_hf.Factureid = fac.id
            LEFT JOIN statut_facture last_sf ON last_sf.id = latest_hf.last_statut_id
            
            /* --- JOINTURE O.V --- */
            LEFT JOIN facture_ordres_virement fov ON fov.Factureid = fac.id
            LEFT JOIN ordres_virement ov ON ov.id = fov.ordres_virementid
            LEFT JOIN region_dp rdp ON rdp.id = ov.region_dpid
            
            /* --- EXTRACTION DU DERNIER STATUT OV --- */
            LEFT JOIN (
                SELECT ordres_virementid, 
                       CAST(SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS UNSIGNED) AS last_statut_id,
                       MAX(date_status_OV) AS max_date
                FROM historique_status_ov 
                GROUP BY ordres_virementid
            ) latest_hov ON latest_hov.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = latest_hov.last_statut_id
            
            /* --- GESTIONNAIRE : Jointure sur l'utilisateur récupéré de l'historique --- */
            LEFT JOIN utilisateur u ON u.id = latest_hf.last_gestionnaire_id
            
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filtres['fournisseur_id'])) {
            $sql .= " AND f.id = :fournisseur_id";
            $params[':fournisseur_id'] = $filtres['fournisseur_id'];
        }
        if (!empty($filtres['contrat_id'])) {
            $sql .= " AND cnt.id = :contrat_id";
            $params[':contrat_id'] = $filtres['contrat_id'];
        }
        if (!empty($filtres['structure_id'])) {
            $sql .= " AND (ov.region_dpid = :structure_id OR ov.id IS NULL)";
            $params[':structure_id'] = $filtres['structure_id'];
        }
        if (!empty($filtres['monnaie_id'])) {
            $sql .= " AND fac.money_id = :monnaie_id";
            $params[':monnaie_id'] = $filtres['monnaie_id'];
        }
        if (!empty($filtres['statut_id'])) {
            $sql .= " AND last_sf.id = :statut_id";
            $params[':statut_id'] = $filtres['statut_id'];
        }
        if (!empty($filtres['gestionnaire'])) {
            $sql .= " AND (u.nom LIKE :gestionnaire OR u.prenom LIKE :gestionnaire2)";
            $params[':gestionnaire'] = '%' . $filtres['gestionnaire'] . '%';
            $params[':gestionnaire2'] = '%' . $filtres['gestionnaire'] . '%';
        }

        $sql .= " GROUP BY fac.id";
        $sql .= " ORDER BY fac.date_facture DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totaux = [];
        foreach ($rows as $r) {
            $mon = $r['monnaie'] ?? 'DZD';
            $totaux[$mon] = ($totaux[$mon] ?? 0) + (float)$r['Montant'];
        }

        return [
            'count' => count($rows), 
            'totaux' => $totaux, 
            'data' => $rows
        ];
    }

    public static function getRecapOV(PDO $db, array $filters): array {
        $conditions = ['1=1'];
        $params     = [];

        if (!empty($filters['fournisseur_id'])) { $conditions[] = 'f.id = :fournisseur_id'; $params[':fournisseur_id'] = (int)$filters['fournisseur_id']; }
        if (!empty($filters['contrat_id'])) { $conditions[] = 'ov.Contratid = :contrat_id'; $params[':contrat_id'] = (int)$filters['contrat_id']; }
        if (!empty($filters['monnaie_id'])) { $conditions[] = 'ov.moneyid = :monnaie_id'; $params[':monnaie_id'] = (int)$filters['monnaie_id']; }
        if (!empty($filters['structure_id'])) { $conditions[] = 'ov.region_dpid = :structure_id'; $params[':structure_id'] = (int)$filters['structure_id']; }
        if (!empty($filters['statut_ov_id'])) { $conditions[] = 'last_sov.id = :statut_ov_id'; $params[':statut_ov_id'] = (int)$filters['statut_ov_id']; }
        if (!empty($filters['agent'])) { $conditions[] = "(u.nom LIKE :agent OR u.prenom LIKE :agent2)"; $params[':agent'] = '%' . $filters['agent'] . '%'; $params[':agent2'] = '%' . $filters['agent'] . '%'; }

        $where = implode(' AND ', $conditions);
        $sql = "
            SELECT
                ov.id, ov.Num_OV, DATE(ov.Date_OV) AS date_ov, f.Nom_Fournisseur, cnt.num_Contrat,
                rdp.label AS structure, m.code AS monnaie,
                COALESCE((SELECT SUM(fac2.Montant) FROM facture_ordres_virement fov2 JOIN facture fac2 ON fac2.id = fov2.Factureid WHERE fov2.ordres_virementid = ov.id), 0) AS montant_total,
                last_sov.label AS statut_ov, last_sov.code AS statut_ov_code,
                CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS agent,
                COUNT(fov.Factureid) AS nb_factures
            FROM ordres_virement ov
            JOIN contrat cnt ON cnt.id = ov.Contratid
            JOIN fournisseur f ON f.id = cnt.Fournisseur_id
            JOIN money m ON m.id = ov.moneyid
            JOIN region_dp rdp ON rdp.id = ov.region_dpid
            LEFT JOIN (SELECT ordres_virementid, SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS last_statut_id, SUBSTRING_INDEX(GROUP_CONCAT(traitant_id ORDER BY date_status_OV DESC), ',', 1) AS last_traitant_id FROM historique_status_ov GROUP BY ordres_virementid) hov_last ON hov_last.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = hov_last.last_statut_id
            LEFT JOIN utilisateur u ON u.id = hov_last.last_traitant_id
            LEFT JOIN facture_ordres_virement fov ON fov.ordres_virementid = ov.id
            WHERE $where
            GROUP BY ov.id
            ORDER BY ov.Date_OV DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totaux = [];
        foreach ($rows as $r) {
            $mon = $r['monnaie'] ?? 'DZD';
            $totaux[$mon] = ($totaux[$mon] ?? 0) + (float)$r['montant_total'];
        }

        return ['count' => count($rows), 'totaux' => $totaux, 'data' => $rows];
    }
}