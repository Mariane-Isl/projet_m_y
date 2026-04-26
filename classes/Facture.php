<?php

class Facture
{
    private $Num_facture;
    private $date_facture;
    private $montant_total;


    public function __construct($Num_facture = null, $date_facture = null, $montant_total = null)
    {
        $this->Num_facture = $Num_facture;
        $this->date_facture = $date_facture;
        $this->montant_total = $montant_total;
    }

    // --- GETTERS & SETTERS ---

    public function getNumFacture()
    {
        return $this->Num_facture;
    }
    public function getDateFacture()
    {
        return $this->date_facture;
    }
    public function getMontantTotal()
    {
        return $this->montant_total;
    }


    public function setNumFacture($Num_facture)
    {
        $this->Num_facture = $Num_facture;
    }
    public function setDateFacture($date_facture)
    {
        $this->date_facture = $date_facture;
    }
    public function setMontantTotal($montant_total)
    {
        $this->montant_total = $montant_total;
    }


    // --- CHECK EXISTING ---

    public static function checkIfExists(PDO $pdo, $num_facture)
    {
        $sql = "SELECT COUNT(*) FROM facture WHERE Num_facture = :num_facture";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num_facture' => $num_facture]);
        return $stmt->fetchColumn() > 0;
    }

    // --- 1. CREATE (INSERT) ---
    public function insertWithBordereau(PDO $pdo, $money_id, $bordereau_id, $date_facture)
    {
        // Suppression de date_facture
        // Ajout de la valeur par défaut pour 'ordres _virementid' (souvent 0 si pas encore de virement)
        $sql = "INSERT INTO facture (Num_facture, Montant,date_facture, money_id, Bordereau_id) 
                VALUES (:num_facture, :montant,:date_facture, :money_id, :bordereau_id)";

        $stmt = $pdo->prepare($sql);

        $success = $stmt->execute([
            ':num_facture'  => $this->Num_facture,
            ':montant'      => $this->montant_total,
            ':money_id'     => $money_id,
            ':bordereau_id' => $bordereau_id,
            ':date_facture' => $date_facture
        ]);

        return $success ? $pdo->lastInsertId() : false;
    }

    /**
     * Inserts the current object's data into the database.
     */
    public function insert(PDO $pdo)
    {
        $sql = "INSERT INTO facture (Num_facture, date_facture, montant_total) 
                VALUES (:num_facture, :date_facture, :montant_total)";

        $stmt = $pdo->prepare($sql);

        // Execute returns true on success, false on failure
        return $stmt->execute([
            ':num_facture'   => $this->Num_facture,
            ':date_facture'  => $this->date_facture,
            ':montant_total' => $this->montant_total,
        ]);
    }

    // --- 2. READ (SELECT) ---

    /**
     * Fetches all factures from the database.
     */
    public static function getAll(PDO $pdo)
    {
        $sql = "SELECT * FROM facture";
        $stmt = $pdo->query($sql);
        // Returns an associative array of all records
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a single facture by its Num_facture.
     */
    public static function getByNumFacture(PDO $pdo, $num_facture)
    {
        $sql = "SELECT * FROM facture WHERE Num_facture = :num_facture";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num_facture' => $num_facture]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Returns the array of data, or false if not found
    }

    // --- 3. UPDATE ---

    /**
     * Updates an existing facture in the database based on its Num_facture.
     */
    public function update(PDO $pdo)
    {
        $sql = "UPDATE facture 
                SET date_facture = :date_facture, 
                    montant_total = :montant_total,
                WHERE Num_facture = :num_facture";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':date_facture'  => $this->date_facture,
            ':montant_total' => $this->montant_total,
            ':num_facture'   => $this->Num_facture
        ]);
    }

    // --- 4. DELETE ---

    /**
     * Deletes the current facture from the database based on its Num_facture.
     */
    public function delete(PDO $pdo)
    {
        $sql = "DELETE FROM facture WHERE Num_facture = :num_facture";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':num_facture' => $this->Num_facture]);
    }

    public static function getFilteredForVirement(PDO $pdo, $fournisseur_id, $contrat_id, $region_dp_id, $devise_code)
    {
        $sql = "SELECT
                f.id,
                f.Num_facture,
                f.date_facture,
                f.Montant AS montant_total,
                b.num_bordereau AS Num_Bordereaux,
                m.code AS devise,
                fo.Nom_Fournisseur AS fournisseur,
                sf.label AS statut
            FROM facture f
            JOIN bordereau b ON f.Bordereau_id = b.id
            JOIN utilisateur u ON b.emeteur_id = u.id
            JOIN region_dp rdp ON u.region_dp_id = rdp.id
            JOIN contrat c ON b.Contrat_id = c.id
            JOIN fournisseur fo ON c.Fournisseur_id = fo.id
            JOIN money m ON f.money_id = m.id
            
            -- Liaison avec l'historique et le statut
            JOIN historique_facture hf ON f.id = hf.Factureid
            JOIN statut_facture sf ON hf.statut_factureid = sf.id
            
            WHERE c.id = :contrat_id
              AND fo.id = :fournisseur_id
              AND rdp.id = :region_dp_id
              AND m.code = :devise_code
              
              -- ✅ RÈGLE 1 : Seul le statut 'RECU' est accepté
              AND sf.code = 'RECU'
              
              -- ✅ RÈGLE 2 : On s'assure de ne lire QUE le tout dernier statut de cette facture
              AND hf.date_statuts = (
                  SELECT MAX(date_statuts)
                  FROM historique_facture
                  WHERE Factureid = f.id
              )
              
              -- ✅ RÈGLE 3 : Exclure les factures déjà affectées à un OV (Ultra-rapide via NOT EXISTS)
              AND NOT EXISTS (
                  SELECT 1
                  FROM facture_ordres_virement fov
                  WHERE fov.Factureid = f.id
              )
              
            ORDER BY f.date_facture DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contrat_id'     => $contrat_id,
            ':fournisseur_id' => $fournisseur_id,
            ':region_dp_id'   => $region_dp_id,
            ':devise_code'    => $devise_code,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getFacturesPourOV(PDO $db, $fournisseur_id, $contrat_id, $devise, $structure_id)
    {
        $sql = "SELECT
                f.id,
                f.Num_facture,
                f.date_facture,
                f.Montant AS montant_total,
                b.num_bordereau AS Num_Bordereaux,
                m.code AS devise,
                fo.Nom_Fournisseur AS fournisseur,
                sf.label AS statut
            FROM facture f
            JOIN bordereau      b   ON f.Bordereau_id      = b.id
            JOIN utilisateur    u   ON b.emeteur_id         = u.id
            JOIN region_dp      rdp ON u.region_dp_id       = rdp.id
            JOIN contrat        c   ON b.Contrat_id         = c.id
            JOIN fournisseur    fo  ON c.Fournisseur_id     = fo.id
            JOIN money          m   ON f.money_id           = m.id
            -- ✅ Uniquement le dernier statut de chaque facture
            JOIN historique_facture hf ON f.id = hf.Factureid
                AND hf.date_statuts = (
                    SELECT MAX(hf2.date_statuts)
                    FROM historique_facture hf2
                    WHERE hf2.Factureid = f.id
                )
            JOIN statut_facture sf ON hf.statut_factureid = sf.id
            WHERE c.id            = :contrat_id
              AND fo.id           = :fournisseur_id
              AND rdp.id          = :structure_id
              AND m.code          = :devise
              -- ✅ Statut actuel = RECU uniquement
              AND sf.code         = 'RECU'
              -- ✅ Pas déjà dans un OV
              AND f.id NOT IN (
                    SELECT fov.Factureid
                    FROM facture_ordres_virement fov
              )
            ORDER BY f.date_facture DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':contrat_id'     => $contrat_id,
            ':fournisseur_id' => $fournisseur_id,
            ':structure_id'   => $structure_id,
            ':devise'         => $devise,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getFullDetails(PDO $db, int $id)
    {
        $sql = "SELECT f.*, 
                       m.code AS monnaie_code, m.label AS monnaie_label,
                       b.num_bordereau, b.date_bordereau,
                       c.num_Contrat,
                       fr.Nom_Fournisseur,
                       reg.label AS structure_nom,
                       u.nom AS gestionnaire_nom, u.prenom AS gestionnaire_prenom,
                       DATEDIFF(NOW(), f.date_facture) AS jours_SLA
                FROM facture f
                JOIN money m ON f.money_id = m.id
                JOIN bordereau b ON f.Bordereau_id = b.id
                JOIN contrat c ON b.Contrat_id = c.id
                JOIN fournisseur fr ON c.Fournisseur_id = fr.id
                LEFT JOIN utilisateur u ON b.emeteur_id = u.id
                LEFT JOIN region_dp reg ON u.region_dp_id = reg.id
                WHERE f.id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── NOUVELLE MÉTHODE : Historique des statuts de la facture ──
    public static function getHistoriqueStatuts(PDO $db, int $id)
    {
        $sql = "SELECT h.date_statuts, s.label AS statut_label, s.code AS statut_code
                FROM historique_facture h
                JOIN statut_facture s ON h.statut_factureid = s.id
                WHERE h.Factureid = :id
                ORDER BY h.date_statuts DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getDossierComplet(PDO $db, int $id)
    {
        $sql = "SELECT f.*, 
                   m.code AS monnaie_code, m.label AS monnaie_label,
                   b.num_bordereau, b.date_bordereau,
                   c.num_Contrat,
                   fr.Nom_Fournisseur,
                   reg.label AS structure_nom,
                   u.nom AS gestionnaire_nom, u.prenom AS gestionnaire_prenom,
                   DATEDIFF(NOW(), f.date_facture) AS jours_SLA
            FROM facture f
            JOIN money m ON f.money_id = m.id
            JOIN bordereau b ON f.Bordereau_id = b.id
            JOIN contrat c ON b.Contrat_id = c.id
            JOIN fournisseur fr ON c.Fournisseur_id = fr.id
            LEFT JOIN utilisateur u ON b.emeteur_id = u.id
            LEFT JOIN region_dp reg ON u.region_dp_id = reg.id
            WHERE f.id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getHistoriqueComplet(PDO $db, int $id)
    {
        $sql = "SELECT 
                hf.date_statuts, 
                sf.label AS statut_label, 
                sf.code AS statut_code,
                /* LOGIQUE : On va chercher l'utilisateur dans la table correspondante au statut */
                CASE 
                    /* 1. Si la facture est RECU (En cours de traitement), l'utilisateur est celui qui a RÉCEPTIONNÉ le bordereau */
                    WHEN sf.code = 'RECU' THEN (
                        SELECT CONCAT(u.nom, ' ', u.prenom) 
                        FROM historique_borderau hb
                        JOIN utilisateur u ON hb.traitant_id = u.id
                        JOIN facture f ON f.Bordereau_id = hb.Bordereauid
                        WHERE f.id = :id1 AND hb.statut_borderauid = 6 /* ID 6 = RECEPTIONNE */
                        ORDER BY hb.date_historique DESC LIMIT 1
                    )
                    /* 2. Si la facture est REJETER, l'utilisateur est celui qui a CRÉÉ le rejet */
                    WHEN sf.code = 'REJETER' THEN (
                        SELECT CONCAT(u.nom, ' ', u.prenom)
                        FROM historique_rejet hr
                        JOIN facture_rejer frj ON hr.Rejetid = frj.Rejetid
                        JOIN utilisateur u ON hr.traitant_id = u.id
                        WHERE frj.Factureid = :id2 AND hr.statut_rejetid = 1 /* ID 1 = CREE */
                        ORDER BY hr.date_rejet DESC LIMIT 1
                    )
                    /* 3. Si la facture est PAYE, l'utilisateur est celui qui a EXÉCUTÉ l'OV */
                    WHEN sf.code = 'PAYE' THEN (
                        SELECT CONCAT(u.nom, ' ', u.prenom)
                        FROM historique_status_ov hov
                        JOIN facture_ordres_virement fov ON hov.ordres_virementid = fov.ordres_virementid
                        JOIN utilisateur u ON hov.traitant_id = u.id
                        WHERE fov.Factureid = :id3 AND hov.statut_OVid = 4 /* ID 4 = EXEC */
                        ORDER BY hov.date_status_OV DESC LIMIT 1
                    )
                    /* Par défaut, on affiche le créateur du bordereau */
                    ELSE (
                        SELECT CONCAT(u.nom, ' ', u.prenom)
                        FROM utilisateur u
                        JOIN bordereau b ON b.emeteur_id = u.id
                        JOIN facture f ON f.Bordereau_id = b.id
                        WHERE f.id = :id4
                    )
                END AS utilisateur_nom
            FROM historique_facture hf
            JOIN statut_facture sf ON hf.statut_factureid = sf.id
            WHERE hf.Factureid = :id_main
            ORDER BY hf.date_statuts DESC";

        $stmt = $db->prepare($sql);
        // On lie l'ID de la facture à tous les marqueurs
        $stmt->execute([
            'id1' => $id,
            'id2' => $id,
            'id3' => $id,
            'id4' => $id,
            'id_main' => $id
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchFactures(PDO $db, array $f)
    {
        $sql = "SELECT f.*, m.code AS monnaie, c.num_Contrat, fr.Nom_Fournisseur, 
                reg.label AS structure, sf.label AS statut_actuel
            FROM facture f
            JOIN money m ON f.money_id = m.id
            JOIN bordereau b ON f.Bordereau_id = b.id
            JOIN contrat c ON b.Contrat_id = c.id
            JOIN fournisseur fr ON c.Fournisseur_id = fr.id
            -- LE BON CHEMIN : Facture -> Bordereau -> Utilisateur -> Region
            JOIN utilisateur u ON b.emeteur_id = u.id
            JOIN region_dp reg ON u.region_dp_id = reg.id
            LEFT JOIN (
                SELECT h1.Factureid, h1.statut_factureid, h1.date_statuts
                FROM historique_facture h1
                WHERE h1.date_statuts = (SELECT MAX(date_statuts) FROM historique_facture h2 WHERE h2.Factureid = h1.Factureid)
            ) last_h ON f.id = last_h.Factureid
            LEFT JOIN statut_facture sf ON last_h.statut_factureid = sf.id
            WHERE 1=1";

        $params = [];
        if (!empty($f['fournisseur'])) {
            $sql .= " AND fr.id = :fr";
            $params['fr'] = $f['fournisseur'];
        }
        if (!empty($f['contrat'])) {
            $sql .= " AND c.id = :ct";
            $params['ct'] = $f['contrat'];
        }
        if (!empty($f['num_facture'])) {
            $sql .= " AND f.Num_facture LIKE :nf";
            $params['nf'] = "%" . $f['num_facture'] . "%";
        }
        if (!empty($f['montant'])) {
            $sql .= " AND f.Montant = :mt";
            $params['mt'] = $f['montant'];
        }

        $sql .= " ORDER BY f.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
