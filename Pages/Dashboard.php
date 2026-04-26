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
                CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS gestionnaire,
                DATEDIFF(NOW(), fac.date_facture) AS delai_jours
            FROM facture fac
            LEFT JOIN money m ON m.id = fac.money_id
            LEFT JOIN bordereau brd ON brd.id = fac.Bordereau_id
            LEFT JOIN contrat cnt ON cnt.id = brd.Contrat_id
            LEFT JOIN fournisseur f ON f.id = cnt.Fournisseur_id
            
            /* --- EXTRACTION SÉCURISÉE DU DERNIER STATUT FACTURE (Erreur ID corrigée) --- */
            LEFT JOIN (
                SELECT Factureid, 
                       CAST(SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS UNSIGNED) AS last_statut_id,
                       MAX(date_statuts) AS max_date
                FROM historique_facture 
                GROUP BY Factureid
            ) latest_hf ON latest_hf.Factureid = fac.id
            LEFT JOIN statut_facture last_sf ON last_sf.id = latest_hf.last_statut_id
            
            /* --- JOINTURE O.V --- */
            LEFT JOIN facture_ordres_virement fov ON fov.Factureid = fac.id
            LEFT JOIN ordres_virement ov ON ov.id = fov.ordres_virementid
            LEFT JOIN region_dp rdp ON rdp.id = ov.region_dpid
            
            /* --- EXTRACTION SÉCURISÉE DU DERNIER STATUT OV (Erreur ID corrigée) --- */
            LEFT JOIN (
                SELECT ordres_virementid, 
                       CAST(SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS UNSIGNED) AS last_statut_id,
                       MAX(date_status_OV) AS max_date
                FROM historique_status_ov 
                GROUP BY ordres_virementid
            ) latest_hov ON latest_hov.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = latest_hov.last_statut_id
            
            /* --- GESTIONNAIRE --- */
            LEFT JOIN utilisateur u ON u.id = brd.emeteur_id
            
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
            // CORRECTION DU FILTRE STRUCTURE :
            // Accepte si la structure correspond à l'OV... OU si la facture n'a pas encore d'OV (donc pas de structure définie).
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
            'data' => $rows,
            'debug_sql' => $sql,
            'debug_params' => $params
        ];
    }

    public static function getRecapOV(PDO $db, array $filters): array {
        $conditions = ['1=1'];
        $params     = [];

        if (!empty($filters['fournisseur_id'])) {
            $conditions[] = 'f.id = :fournisseur_id';
            $params[':fournisseur_id'] = (int)$filters['fournisseur_id'];
        }
        if (!empty($filters['contrat_id'])) {
            $conditions[] = 'ov.Contratid = :contrat_id';
            $params[':contrat_id'] = (int)$filters['contrat_id'];
        }
        if (!empty($filters['monnaie_id'])) {
            $conditions[] = 'ov.moneyid = :monnaie_id';
            $params[':monnaie_id'] = (int)$filters['monnaie_id'];
        }
        if (!empty($filters['structure_id'])) {
            $conditions[] = 'ov.region_dpid = :structure_id';
            $params[':structure_id'] = (int)$filters['structure_id'];
        }
        if (!empty($filters['statut_ov_id'])) {
            $conditions[] = 'last_sov.id = :statut_ov_id';
            $params[':statut_ov_id'] = (int)$filters['statut_ov_id'];
        }
        if (!empty($filters['agent'])) {
            $conditions[] = "(u.nom LIKE :agent OR u.prenom LIKE :agent2)";
            $params[':agent']  = '%' . $filters['agent'] . '%';
            $params[':agent2'] = '%' . $filters['agent'] . '%';
        }

        $where = implode(' AND ', $conditions);
        $sql = "
            SELECT
                ov.id, ov.Num_OV, DATE(ov.Date_OV) AS date_ov, f.Nom_Fournisseur, cnt.num_Contrat,
                rdp.label AS structure, m.code AS monnaie,
                COALESCE((SELECT SUM(fac2.Montant) FROM facture_ordres_virement fov2 JOIN facture fac2 ON fac2.id = fov2.Factureid WHERE fov2.ordres_virementid = ov.id), 0) AS montant_total,
                last_sov.label AS statut_ov, last_sov.code AS statut_ov_code,
                hov_last.date_statut_ov AS dernier_traitement,
                CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS agent,
                COUNT(fov.Factureid) AS nb_factures
            FROM ordres_virement ov
            JOIN contrat cnt ON cnt.id = ov.Contratid
            JOIN fournisseur f ON f.id = cnt.Fournisseur_id
            JOIN money m ON m.id = ov.moneyid
            JOIN region_dp rdp ON rdp.id = ov.region_dpid
            
            LEFT JOIN (
                SELECT ordres_virementid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS last_statut_id,
                       SUBSTRING_INDEX(GROUP_CONCAT(traitant_id ORDER BY date_status_OV DESC), ',', 1) AS last_traitant_id,
                       MAX(date_status_OV) AS date_statut_ov
                FROM historique_status_ov 
                GROUP BY ordres_virementid
            ) hov_last ON hov_last.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = hov_last.last_statut_id
            
            LEFT JOIN utilisateur u ON u.id = hov_last.last_traitant_id
            LEFT JOIN facture_ordres_virement fov ON fov.ordres_virementid = ov.id
            WHERE $where
            GROUP BY ov.id, ov.Num_OV, ov.Date_OV, f.Nom_Fournisseur, cnt.num_Contrat, rdp.label, m.code, last_sov.label, last_sov.code, hov_last.date_statut_ov, u.nom, u.prenom
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

    public static function getPerformanceGestionnaires(PDO $db, array $filters): array {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['date_debut'])) {
            $conditions[] = 'hf_last.date_statuts >= :date_debut';
            $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $conditions[] = 'hf_last.date_statuts <= :date_fin';
            $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['structure_id'])) {
            $conditions[] = 'rdp.id = :structure_id';
            $params[':structure_id'] = (int)$filters['structure_id'];
        }
        if (!empty($filters['gestionnaire_id'])) {
            $conditions[] = 'u.id = :gestionnaire_id';
            $params[':gestionnaire_id'] = (int)$filters['gestionnaire_id'];
        }

        $statut_code = $filters['statut_code'] ?? '';
        $statut_filter_sf = '';  
        $statut_filter_ov = '';  

        if ($statut_code !== '') {
            if (in_array($statut_code, ['EN COURS', 'PAYE'])) {
                $statut_filter_sf = $statut_code;
            } elseif (in_array($statut_code, ['ATF', 'ADB_ATF', 'TRAIT'])) {
                $statut_filter_ov = $statut_code;
            }
        }

        if ($statut_filter_sf !== '') {
            $conditions[] = 'last_sf.code = :sf_code';
            $params[':sf_code'] = $statut_filter_sf;
        } elseif ($statut_filter_ov !== '') {
            $conditions[] = 'last_sov.code = :sov_code';
            $params[':sov_code'] = $statut_filter_ov;
        } else {
            $conditions[] = "(last_sf.code IN ('EN COURS','PAYE') OR last_sov.code IN ('ATF','ADB_ATF','TRAIT'))";
        }

        $where = implode(' AND ', $conditions);
        $sql = "
            SELECT
                u.id AS gestionnaire_id, CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS gestionnaire,
                last_sf.code AS statut_sf_code, last_sf.label AS statut_sf_label,
                last_sov.code AS statut_ov_code, last_sov.label AS statut_ov_label,
                rdp.id AS structure_id, rdp.label AS structure, COUNT(fac.id) AS nb_factures
            FROM facture fac
            JOIN bordereau brd ON brd.id = fac.Bordereau_id
            JOIN contrat cnt ON cnt.id = brd.Contrat_id
            JOIN utilisateur u ON u.id = brd.emeteur_id
            
            LEFT JOIN (
                SELECT Factureid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS last_statut_id,
                       MAX(date_statuts) AS date_statuts
                FROM historique_facture 
                GROUP BY Factureid
            ) hf_last ON hf_last.Factureid = fac.id
            LEFT JOIN statut_facture last_sf ON last_sf.id = hf_last.last_statut_id
            
            LEFT JOIN facture_ordres_virement fov ON fov.Factureid = fac.id
            LEFT JOIN ordres_virement ov ON ov.id = fov.ordres_virementid
            LEFT JOIN region_dp rdp ON rdp.id = ov.region_dpid
            
            LEFT JOIN (
                SELECT ordres_virementid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS last_statut_id,
                       MAX(date_status_OV) AS date_status_OV
                FROM historique_status_ov 
                GROUP BY ordres_virementid
            ) hov_last ON hov_last.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = hov_last.last_statut_id
            
            WHERE $where
            GROUP BY u.id, u.nom, u.prenom, last_sf.code, last_sf.label, last_sov.code, last_sov.label, rdp.id, rdp.label
            ORDER BY gestionnaire
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gestionnaires = [];
        $statuts_found = [];
        foreach ($rows as $r) {
            $g = trim($r['gestionnaire']);
            $gid = $r['gestionnaire_id'];
            if (!isset($gestionnaires[$gid])) {
                $gestionnaires[$gid] = ['nom' => $g, 'statuts' => [], 'total' => 0];
            }
            $code  = $r['statut_sf_code']  ?: $r['statut_ov_code']  ?: 'AUTRE';
            $label = $r['statut_sf_label'] ?: $r['statut_ov_label'] ?: 'Autre';
            if (!in_array($code, ['EN COURS', 'PAYE', 'ATF', 'ADB_ATF', 'TRAIT'])) continue;
            if (!isset($gestionnaires[$gid]['statuts'][$code])) {
                $gestionnaires[$gid]['statuts'][$code] = 0;
            }
            $gestionnaires[$gid]['statuts'][$code] += (int)$r['nb_factures'];
            $gestionnaires[$gid]['total'] += (int)$r['nb_factures'];
            $statuts_found[$code] = $label;
        }

        return [
            'data'        => array_values($gestionnaires),
            'statuts'     => $statuts_found,
            'statut_code' => $statut_code,
        ];
    }

    public static function getRepartitionRegionale(PDO $db, array $filters): array {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['date_debut'])) {
            $conditions[] = 'hf_last.date_statuts >= :date_debut';
            $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $conditions[] = 'hf_last.date_statuts <= :date_fin';
            $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['structure_id'])) {
            $conditions[] = 'rdp.id = :structure_id';
            $params[':structure_id'] = (int)$filters['structure_id'];
        }

        $statut_code = $filters['statut_code'] ?? '';
        if ($statut_code !== '') {
            if (in_array($statut_code, ['EN COURS', 'PAYE'])) {
                $conditions[] = 'last_sf.code = :sf_code';
                $params[':sf_code'] = $statut_code;
            } elseif (in_array($statut_code, ['ATF', 'ADB_ATF', 'TRAIT'])) {
                $conditions[] = 'last_sov.code = :sov_code';
                $params[':sov_code'] = $statut_code;
            }
        } else {
            $conditions[] = "(last_sf.code IN ('EN COURS','PAYE') OR last_sov.code IN ('ATF','ADB_ATF','TRAIT'))";
        }

        $where = implode(' AND ', $conditions);
        $sql = "
            SELECT
                rdp.id AS structure_id, rdp.label AS structure,
                last_sf.code AS statut_sf_code, last_sf.label AS statut_sf_label,
                last_sov.code AS statut_ov_code, last_sov.label AS statut_ov_label,
                COUNT(fac.id) AS nb_factures
            FROM facture fac
            JOIN bordereau brd ON brd.id = fac.Bordereau_id
            JOIN contrat cnt ON cnt.id = brd.Contrat_id
            
            LEFT JOIN (
                SELECT Factureid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS last_statut_id,
                       MAX(date_statuts) AS date_statuts
                FROM historique_facture 
                GROUP BY Factureid
            ) hf_last ON hf_last.Factureid = fac.id
            LEFT JOIN statut_facture last_sf ON last_sf.id = hf_last.last_statut_id
            
            LEFT JOIN facture_ordres_virement fov ON fov.Factureid = fac.id
            LEFT JOIN ordres_virement ov ON ov.id = fov.ordres_virementid
            LEFT JOIN region_dp rdp ON rdp.id = ov.region_dpid
            
            LEFT JOIN (
                SELECT ordres_virementid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_OVid ORDER BY date_status_OV DESC), ',', 1) AS last_statut_id,
                       MAX(date_status_OV) AS date_status_OV
                FROM historique_status_ov 
                GROUP BY ordres_virementid
            ) hov_last ON hov_last.ordres_virementid = ov.id
            LEFT JOIN statut_ov last_sov ON last_sov.id = hov_last.last_statut_id
            
            WHERE $where AND rdp.id IS NOT NULL
            GROUP BY rdp.id, rdp.label, last_sf.code, last_sf.label, last_sov.code, last_sov.label
            ORDER BY rdp.label
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $structures = [];
        $statuts_found = [];
        foreach ($rows as $r) {
            $sid = $r['structure_id'];
            if (!isset($structures[$sid])) {
                $structures[$sid] = ['nom' => $r['structure'], 'statuts' => [], 'total' => 0];
            }
            $code  = $r['statut_sf_code']  ?: $r['statut_ov_code']  ?: 'AUTRE';
            $label = $r['statut_sf_label'] ?: $r['statut_ov_label'] ?: 'Autre';
            if (!in_array($code, ['EN COURS', 'PAYE', 'ATF', 'ADB_ATF', 'TRAIT'])) continue;
            if (!isset($structures[$sid]['statuts'][$code])) {
                $structures[$sid]['statuts'][$code] = 0;
            }
            $structures[$sid]['statuts'][$code] += (int)$r['nb_factures'];
            $structures[$sid]['total'] += (int)$r['nb_factures'];
            $statuts_found[$code] = $label;
        }

        return [
            'data'         => array_values($structures),
            'statuts'      => $statuts_found,
            'statut_code'  => $statut_code,
            'structure_id' => $filters['structure_id'] ?? '',
        ];
    }

    public static function getLeadTime(PDO $db, array $filters): array {
        $conditions = ["last_sf.code IN ('EN COURS','PAYE')"];
        $params = [];

        $statut_code = $filters['statut_code'] ?? '';
        if ($statut_code === 'PAYE') {
            $conditions = ["last_sf.code = 'PAYE'"];
        } elseif ($statut_code === 'EN COURS') {
            $conditions = ["last_sf.code = 'EN COURS'"];
        }

        if (!empty($filters['date_debut'])) {
            $conditions[] = 'hf_last.date_statuts >= :date_debut';
            $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $conditions[] = 'hf_last.date_statuts <= :date_fin';
            $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['structure_id'])) {
            $conditions[] = 'ov.region_dpid = :structure_id';
            $params[':structure_id'] = (int)$filters['structure_id'];
        }

        $where = implode(' AND ', $conditions);
        $sql = "
            SELECT
                u.id AS gestionnaire_id, CONCAT(u.nom, ' ', COALESCE(u.prenom,'')) AS gestionnaire,
                last_sf.code AS statut_code, last_sf.label AS statut_label,
                COUNT(fac.id) AS nb_factures,
                ROUND(AVG(DATEDIFF(hf_last.date_statuts, fac.date_facture)), 0) AS moyenne_jours
            FROM facture fac
            JOIN bordereau brd ON brd.id = fac.Bordereau_id
            JOIN utilisateur u ON u.id = brd.emeteur_id
            
            LEFT JOIN (
                SELECT Factureid, 
                       SUBSTRING_INDEX(GROUP_CONCAT(statut_factureid ORDER BY date_statuts DESC), ',', 1) AS last_statut_id,
                       MAX(date_statuts) AS date_statuts
                FROM historique_facture 
                GROUP BY Factureid
            ) hf_last ON hf_last.Factureid = fac.id
            LEFT JOIN statut_facture last_sf ON last_sf.id = hf_last.last_statut_id
            
            LEFT JOIN facture_ordres_virement fov ON fov.Factureid = fac.id
            LEFT JOIN ordres_virement ov ON ov.id = fov.ordres_virementid
            WHERE $where AND fac.date_facture IS NOT NULL
            GROUP BY u.id, u.nom, u.prenom, last_sf.code, last_sf.label
            ORDER BY moyenne_jours DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gestionnaires = [];
        foreach ($rows as $r) {
            $gid = $r['gestionnaire_id'];
            if (!isset($gestionnaires[$gid])) {
                $gestionnaires[$gid] = [
                    'nom' => trim($r['gestionnaire']), 'statuts' => [],
                    'total_factures' => 0, 'moyenne_globale' => 0, '_sum' => 0, '_count' => 0,
                ];
            }
            $gestionnaires[$gid]['statuts'][$r['statut_code']] = [
                'label' => $r['statut_label'], 'moyenne_jours' => (int)$r['moyenne_jours'], 'nb_factures' => (int)$r['nb_factures'],
            ];
            $gestionnaires[$gid]['total_factures'] += (int)$r['nb_factures'];
            $gestionnaires[$gid]['_sum']   += (int)$r['moyenne_jours'] * (int)$r['nb_factures'];
            $gestionnaires[$gid]['_count'] += (int)$r['nb_factures'];
        }

        $total_sum = 0; $total_count = 0;
        foreach ($gestionnaires as &$g) {
            $g['moyenne_globale'] = $g['_count'] > 0 ? round($g['_sum'] / $g['_count']) : 0;
            $total_sum   += $g['moyenne_globale'] * $g['total_factures'];
            $total_count += $g['total_factures'];
            unset($g['_sum'], $g['_count']);
        }

        $moyenne_globale = $total_count > 0 ? round($total_sum / $total_count) : 0;

        return [
            'data'            => array_values($gestionnaires),
            'moyenne_globale' => $moyenne_globale,
            'statut_code'     => $statut_code,
        ];
    }
}