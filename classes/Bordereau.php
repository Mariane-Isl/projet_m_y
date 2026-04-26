<?php

class Bordereau
{
    // ==========================================
    // 1. Propriétés privées (correspondant aux colonnes de la table)
    // ==========================================
    private $pdo = null; // Pour compatibilité version instance
    private $id;
    private $num_bordereau;
    private $date_bordereau;
    private $emeteur_id;
    private $Contrat_id;

    // ==========================================
    // 2. Constructeur
    // ==========================================
    // Accepte soit new Bordereau($db) soit new Bordereau($id, $num, ...)
    public function __construct(
        $id_or_db       = null,
        $num_bordereau  = null,
        $date_bordereau = null,
        $emeteur_id     = null,
        $Contrat_id     = null
    ) {
        // Si le premier argument est une connexion PDO → mode instance avec BDD
        if ($id_or_db instanceof PDO) {
            $this->pdo = $id_or_db;
        } else {
            $this->id             = $id_or_db;
            $this->num_bordereau  = $num_bordereau;
            $this->date_bordereau = $date_bordereau;
            $this->emeteur_id     = $emeteur_id;
            $this->Contrat_id     = $Contrat_id;
        }
    }

    // ==========================================
    // 3. GETTERS & SETTERS (Encapsulation)
    // ==========================================

    public function getId()
    {
        return $this->id;
    }
    public function getNumBordereau()
    {
        return $this->num_bordereau;
    }
    public function getDateBordereau()
    {
        return $this->date_bordereau;
    }
    public function getEmeteurId()
    {
        return $this->emeteur_id;
    }
    public function getContratId()
    {
        return $this->Contrat_id;
    }

    // ==========================================
    // 4. MÉTHODES STATIQUES (Interactions BDD)
    // ==========================================

    /**
     * Récupérer TOUS les bordereaux avec leur dernier statut (depuis historique_borderau)
     * Sous-requête dans le FROM pour éviter les sous-requêtes corrélées (MyISAM)
     * @param PDO $db
     * @return array
     */
    public static function getAllWithDetails(PDO $db)
    {
        // Étape 1 : récupérer tous les bordereaux avec leurs jointures de base
        $query = "SELECT
                    b.id,
                    b.num_bordereau,
                    b.date_bordereau,
                    b.emeteur_id,
                    b.Contrat_id,
                    c.num_Contrat     AS num_contrat,
                    f.code            AS fournisseur_code,
                    f.Nom_Fournisseur AS nom_Fournisseur,
                    u.nom             AS emetteur_nom,
                    u.prenom          AS emetteur_prenom
                  FROM bordereau b
                  LEFT JOIN contrat     c ON c.id = b.Contrat_id
                  LEFT JOIN fournisseur f ON f.id = c.Fournisseur_id
                  LEFT JOIN utilisateur u ON u.id = b.emeteur_id
                  ORDER BY b.date_bordereau DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Étape 2 : pour chaque bordereau, récupérer le dernier statut séparément
        $stmtStatut = $db->prepare(
            "SELECT h.date, s.code, s.label
             FROM historique_borderau h
             INNER JOIN statut_borderau s ON s.id = h.statut_borderauid
             WHERE h.Bordereauid = :bid
             ORDER BY h.date DESC
             LIMIT 1"
        );

        foreach ($rows as &$row) {
            $stmtStatut->execute([':bid' => $row['id']]);
            $statut = $stmtStatut->fetch(PDO::FETCH_ASSOC);
            $row['statut_code']  = $statut['code']  ?? 'TRANSMIS';
            $row['statut_label'] = $statut['label'] ?? 'Transmis';
            $row['date_statut']  = $statut['date']  ?? null;
        }
        unset($row);

        return $rows;
    }


    public static function getAllWithDetails_new($db)
    {
        // La requête récupère les infos du bordereau, du contrat, du fournisseur
        // et fait une jointure avec une sous-requête qui isole LE dernier statut de l'historique.
        $sql = "
        SELECT 
            b.id,
            b.num_bordereau,
            b.date_bordereau,
            b.Contrat_id,
            c.num_Contrat,
            f.code AS fournisseur_code,
            f.Nom_Fournisseur AS nom_Fournisseur,
            u.nom AS emetteur_nom,
            u.prenom AS emetteur_prenom,
            sb.code AS statut_code,
            sb.label AS statut_label,
            hb.date_historique AS date_statut
        FROM Bordereau b
        INNER JOIN Contrat c ON b.Contrat_id = c.id
        INNER JOIN Fournisseur f ON c.Fournisseur_id = f.id
        LEFT JOIN utilisateur u ON b.emeteur_id = u.id -- Jointure pour l'émetteur
        
        -- JOINTURE CRUCIALE : Récupérer uniquement le DERNIER historique
        LEFT JOIN (
            SELECT hb1.Bordereauid, hb1.statut_borderauid, hb1.date_historique
            FROM historique_borderau hb1
            INNER JOIN (
                SELECT Bordereauid, MAX(date_historique) AS max_date
                FROM historique_borderau
                GROUP BY Bordereauid
            ) hb2 ON hb1.Bordereauid = hb2.Bordereauid AND hb1.date_historique = hb2.max_date
        ) hb ON b.id = hb.Bordereauid
        
        -- Récupérer le nom du statut
        LEFT JOIN statut_borderau sb ON hb.statut_borderauid = sb.id
        
        ORDER BY b.id DESC
    ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En cas d'erreur de base de données, vous pouvez logguer l'erreur ou la retourner
            error_log("Erreur dans Bordereau::getAllWithDetails : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un bordereau par son ID avec toutes ses jointures
     * @param PDO $db
     * @param int $id
     * @return array|null
     */
    public static function getByIdWithDetails(PDO $db, $id)
    {
        // Étape 1 : données de base du bordereau
        $query = "SELECT
                    b.id,
                    b.num_bordereau,
                    b.date_bordereau,
                    b.emeteur_id,
                    b.Contrat_id,
                    c.num_Contrat     AS num_contrat,
                    f.code            AS fournisseur_code,
                    f.Nom_Fournisseur AS nom_Fournisseur,
                    u.nom             AS emetteur_nom,
                    u.prenom          AS emetteur_prenom
                  FROM bordereau b
                  LEFT JOIN contrat     c ON c.id = b.Contrat_id
                  LEFT JOIN fournisseur f ON f.id = c.Fournisseur_id
                  LEFT JOIN utilisateur u ON u.id = b.emeteur_id
                  WHERE b.id = :id
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Étape 2 : dernier statut séparément
        $stmtStatut = $db->prepare(
            "SELECT h.date_historique, s.code, s.label
             FROM historique_borderau h
             INNER JOIN statut_borderau s ON s.id = h.statut_borderauid
             WHERE h.Bordereauid = :bid
             ORDER BY h.date_historique DESC
             LIMIT 1"
        );
        $stmtStatut->execute([':bid' => $id]);
        $statut = $stmtStatut->fetch(PDO::FETCH_ASSOC);

        $row['statut_code']  = $statut['code']  ?? 'TRANSMIS';
        $row['statut_label'] = $statut['label'] ?? 'Transmis';
        $row['date_statut']  = $statut['date_historique']  ?? null;

        return $row;
    }

    /**
     * Récupérer l'ID d'un statut_borderau par son code
     * @param PDO    $db
     * @param string $code
     * @return int|null
     */
    public static function getStatutIdByCode(PDO $db, string $code)
    {
        $query = "SELECT id FROM statut_borderau WHERE code = :code LIMIT 1";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['id']) : null;
    }

    /**
     * Vérifier si un bordereau possède déjà un statut donné dans son historique
     * @param PDO    $db
     * @param int    $bordereau_id
     * @param string $statut_code
     * @return bool
     */
    public static function hasStatut(PDO $db, int $bordereau_id, string $statut_code): bool
    {
        $query = "SELECT COUNT(*) FROM historique_borderau hb
                  INNER JOIN statut_borderau sb ON sb.id = hb.statut_borderauid
                  WHERE hb.Bordereauid = :bordereau_id
                  AND   sb.code        = :statut_code";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':bordereau_id', $bordereau_id, PDO::PARAM_INT);
        $stmt->bindParam(':statut_code',  $statut_code,  PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Insérer un nouveau statut dans historique_borderau
     * @param PDO    $db
     * @param int    $bordereau_id
     * @param int    $statut_id
     * @param string $date
     * @return bool
     */
    public static function insererHistorique(PDO $db, int $bordereau_id, int $statut_id, string $date): bool
    {
        $query = "INSERT INTO historique_borderau (Bordereauid, statut_borderauid, date)
                  VALUES (:bordereau_id, :statut_id, :date)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':bordereau_id', $bordereau_id, PDO::PARAM_INT);
        $stmt->bindParam(':statut_id',    $statut_id,    PDO::PARAM_INT);
        $stmt->bindParam(':date',         $date);

        return $stmt->execute();
    }

    // ==========================================
    // 5. MÉTHODES D'INSTANCE
    // ==========================================

    /**
     * Récupérer les détails complets d'un bordereau avec ses factures.
     */
    public function getFullDetails(int $id): ?array
    {
        $db = $this->pdo;
        if (!$db) return null;

        try {
            $sql = "SELECT b.*,
                       u.nom        AS user_nom,
                       u.prenom     AS user_prenom,
                       r.label      AS structure_nom,
                       r.code       AS region_code,
                       c.num_Contrat,
                       f.Nom_Fournisseur,
                       h.date       AS date_etat,
                       sb.code      AS code_etat,
                       sb.label     AS label_etat
                    FROM bordereau b
                    LEFT JOIN utilisateur   u  ON b.emeteur_id     = u.id
                    LEFT JOIN region_dp     r  ON u.region_dp_id   = r.id
                    LEFT JOIN contrat       c  ON b.Contrat_id     = c.id
                    LEFT JOIN fournisseur   f  ON c.Fournisseur_id = f.id
                    LEFT JOIN statut_borderau sb ON sb.id = (
                        SELECT hh.statut_borderauid
                        FROM historique_borderau hh
                        WHERE hh.Bordereauid = b.id
                        ORDER BY hh.date DESC, hh.statut_borderauid DESC
                        LIMIT 1
                    )
                    LEFT JOIN historique_borderau h ON h.Bordereauid = b.id
                        AND h.statut_borderauid = sb.id
                        AND h.date = (
                            SELECT MAX(hh2.date) FROM historique_borderau hh2
                            WHERE hh2.Bordereauid = b.id
                        )
                    WHERE b.id = :id
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $header = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$header) return null;

            $sqlFac = "SELECT f.*, m.code AS monnaie_code, m.label AS monnaie_label
                       FROM Facture f
                       LEFT JOIN money m ON f.money_id = m.id
                       WHERE f.Bordereau_id = :id
                       ORDER BY f.id ASC";
            $stmtFac = $db->prepare($sqlFac);
            $stmtFac->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtFac->execute();
            $factures = $stmtFac->fetchAll(PDO::FETCH_ASSOC);

            return ['header' => $header, 'factures' => $factures];
        } catch (PDOException $e) {
            error_log("Erreur getFullDetails: " . $e->getMessage());
            return null;
        }
    }

    public function insert(PDO $db)
    {
        $sql = "INSERT INTO bordereau (num_bordereau, date_bordereau, emeteur_id, Contrat_id) 
                VALUES (:num_bordereau, :date_bordereau, :emeteur_id, :Contrat_id)";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':num_bordereau'  => $this->num_bordereau,
            ':date_bordereau' => $this->date_bordereau,
            ':emeteur_id'     => $this->emeteur_id,
            ':Contrat_id'     => $this->Contrat_id
        ]);
        return $success ? $db->lastInsertId() : false;
    }

    public static function countBordereauxByRegionAndYear(PDO $db, int $region_id, int $annee): int
    {
        $stmtCount = $db->prepare("
        SELECT COUNT(*)
        FROM bordereau b
        JOIN utilisateur u ON b.emeteur_id = u.id
        WHERE u.region_dp_id = :rid 
        AND YEAR(b.date_bordereau) = :annee
        ");
        $stmtCount->execute([':rid' => $region_id, ':annee' => $annee]);
        return (int)$stmtCount->fetchColumn() + 1;
    }

    /**
     * ✅ [NOUVELLE MÉTHODE] Récupérer toutes les factures d'un bordereau avec leur statut actuel
     * Utilisée pour la page de détail et la génération du PDF.
     *
     * @param PDO $db
     * @param int $bordereau_id
     * @return array  Tableau associatif de factures avec : num_facture, date_facture,
     *                montant, devise, statut_code, statut_label
     */
    public static function getFacturesWithStatutByBordereauId(PDO $db, int $bordereau_id): array
    {
        // On récupère chaque facture + son DERNIER statut via une sous-requête sur l'historique
        $query = "SELECT
                    f.id              AS facture_id,
                    f.Num_facture,
                    f.date_facture,
                    f.Montant,
                    m.code            AS devise,
                    m.label           AS devise_label,
                    sf.code           AS statut_code,
                    sf.label          AS statut_label,
                    hf.date_statuts   AS date_statut
                  FROM facture f
                  -- Jointure monnaie
                  LEFT JOIN money m ON f.money_id = m.id
                  -- Récupération du DERNIER statut uniquement (sous-requête corrélée)
                  LEFT JOIN historique_facture hf ON hf.Factureid = f.id
                      AND hf.date_statuts = (
                          SELECT MAX(hf2.date_statuts)
                          FROM historique_facture hf2
                          WHERE hf2.Factureid = f.id
                      )
                  LEFT JOIN statut_facture sf ON sf.id = hf.statut_factureid
                  -- Filtrer sur le bordereau demandé
                  WHERE f.Bordereau_id = :bordereau_id
                  ORDER BY f.id ASC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':bordereau_id', $bordereau_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ [NOUVELLE MÉTHODE] Récupérer tous les bordereaux qui ont le statut 'ARRIVE' ou 'RECEPTION'
     * (c'est-à-dire tous ceux qui ont été réceptionnés par le siège)
     *
     * @param PDO $db
     * @return array
     */
    public static function getAllReceptionnes(PDO $db): array
    {
        $query = "SELECT
                    b.id,
                    b.num_bordereau,
                    b.date_bordereau,
                    c.num_Contrat      AS num_contrat,
                    f.Nom_Fournisseur  AS nom_fournisseur,
                    f.code             AS code_fournisseur,
                    u.nom              AS emetteur_nom,
                    u.prenom           AS emetteur_prenom,
                    rdp.label          AS region_label,
                    rdp.code           AS region_code,
                    sb.code            AS statut_code,
                    sb.label           AS statut_label,
                    hb.date_historique AS date_statut,
                    -- Compte total des factures du bordereau
                    (SELECT COUNT(*) FROM facture fct WHERE fct.Bordereau_id = b.id) AS nb_factures
                  FROM bordereau b
                  INNER JOIN contrat     c   ON c.id              = b.Contrat_id
                  INNER JOIN fournisseur f   ON f.id              = c.Fournisseur_id
                  LEFT  JOIN utilisateur u   ON u.id              = b.emeteur_id
                  LEFT  JOIN region_dp   rdp ON rdp.id            = u.region_dp_id
                  -- Jointure avec le DERNIER historique du bordereau (sous-requête dans FROM)
                  LEFT JOIN (
                      SELECT hb1.Bordereauid, hb1.statut_borderauid, hb1.date_historique
                      FROM historique_borderau hb1
                      INNER JOIN (
                          SELECT Bordereauid, MAX(date_historique) AS max_date
                          FROM historique_borderau
                          GROUP BY Bordereauid
                      ) hb2 ON hb1.Bordereauid = hb2.Bordereauid
                             AND hb1.date_historique = hb2.max_date
                  ) hb ON hb.Bordereauid = b.id
                  LEFT JOIN statut_borderau sb ON sb.id = hb.statut_borderauid
                  -- On ne garde que les bordereaux ayant été accusés réception (ARRIVE) ou plus
                  WHERE sb.code IN ('ARRIVE', 'RECEPTION', 'CONTROLE', 'NON_CONTROLE')
                  ORDER BY b.date_bordereau DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function getById(PDO $db, $id)
    {
        $query = "SELECT b.*, 
                         sb.label AS dernier_statut, 
                         hb.date AS date_dernier_statut,
                         c.num_Contrat, 
                         fr.Nom_Fournisseur AS nom_fournisseur,
                         r.code AS code_structure_head
                  FROM bordereau b
                  -- Récupération du dernier statut via l'historique
                  LEFT JOIN historique_bordereau hb ON b.id = hb.Bordereauid 
                      AND hb.date = (SELECT MAX(date) FROM historique_bordereau WHERE Bordereauid = b.id)
                  LEFT JOIN statut_bordereau sb ON hb.statut_bordereauid = sb.id
                  -- Jointures pour les informations du contrat et fournisseur
                  LEFT JOIN contrat c ON b.Contrat_id = c.id 
                  LEFT JOIN fournisseur fr ON c.Fournisseur_id = fr.id
                  LEFT JOIN utilisateur u ON b.emeteur_id = u.id
                  LEFT JOIN region_dp r ON u.region_dp_id = r.id
                  WHERE b.id = :id";

        $stmt = $db->prepare($query);
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) return null;
        return new self($db, $res);
    }
}
