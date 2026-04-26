<?php

class OV
{
    private $pdo;

    // Properties mapping to columns in 'ordres_virement' table
    private $id;
    private $num_ov;
    private $num_ktp;
    private $date_ov;
    private $nature_ovid;
    private $region_dpid;
    private $contratid;
    private $moneyid;

    public function __construct($db)
    {
        $this->pdo = $db;
    }

    // ============================================
    // GETTERS & SETTERS
    // ============================================

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getNumOv()
    {
        return $this->num_ov;
    }
    public function setNumOv($num_ov)
    {
        $this->num_ov = $num_ov;
    }

    public function getNumKtp()
    {
        return $this->num_ktp;
    }
    public function setNumKtp($num_ktp)
    {
        $this->num_ktp = $num_ktp;
    }

    public function getDateOv()
    {
        return $this->date_ov;
    }
    public function setDateOv($date_ov)
    {
        $this->date_ov = $date_ov;
    }

    public function getNatureOvid()
    {
        return $this->nature_ovid;
    }
    public function setNatureOvid($nature_ovid)
    {
        $this->nature_ovid = $nature_ovid;
    }

    public function getRegionDpid()
    {
        return $this->region_dpid;
    }
    public function setRegionDpid($region_dpid)
    {
        $this->region_dpid = $region_dpid;
    }

    public function getContratid()
    {
        return $this->contratid;
    }
    public function setContratid($contratid)
    {
        $this->contratid = $contratid;
    }

    public function getMoneyid()
    {
        return $this->moneyid;
    }
    public function setMoneyid($moneyid)
    {
        $this->moneyid = $moneyid;
    }


    // ============================================
    // CRUD OPERATIONS
    // ============================================

    /**
     * READ - Get all Ordres de Virement
     */
    public function getAll()
    {
        $query = "SELECT * FROM ordres_virement ORDER BY id DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Get single Ordre de Virement by ID
     */
    public function getById($id)
    {
        $query = "SELECT * FROM ordres_virement WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * CREATE - Insert a new Ordre de Virement
     * Note: Expects Date_OV in 'YYYY-MM-DD HH:MM:SS' format.
     */
    public function insert($num_ov, $num_ktp, $nature_ovid, $region_dpid, $contratid, $moneyid)
    {
        $query = "INSERT INTO ordres_virement 
                    (Num_OV, Num_KTP, Date_OV, nature_OVid, region_dpid, Contratid, moneyid) 
                  VALUES 
                    (:Num_OV, :Num_KTP, NOW(), :nature_OVid, :region_dpid, :Contratid, :moneyid)";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':Num_OV'      => strtoupper(trim($num_ov)),
            ':Num_KTP'     => $num_ktp,
            ':nature_OVid' => $nature_ovid,
            ':region_dpid' => $region_dpid,
            ':Contratid'   => $contratid,
            ':moneyid'     => $moneyid
        ]);

        // RETOURNE L'ID GÉNÉRÉ AU LIEU DE TRUE/FALSE
        return $this->pdo->lastInsertId();
    }

    /**
     * UPDATE - Edit an existing Ordre de Virement
     */
    public function update($id, $num_ov, $num_ktp, $date_ov, $nature_ovid, $region_dpid, $contratid, $moneyid)
    {
        $query = "UPDATE ordres_virement 
                  SET Num_OV = :Num_OV, 
                      Num_KTP = :Num_KTP, 
                      Date_OV = :Date_OV, 
                      nature_OVid = :nature_OVid, 
                      region_dpid = :region_dpid, 
                      Contratid = :Contratid, 
                      moneyid = :moneyid 
                  WHERE id = :id";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':Num_OV'      => strtoupper(trim($num_ov)),
            ':Num_KTP'     => $num_ktp,
            ':Date_OV'     => $date_ov,
            ':nature_OVid' => $nature_ovid,
            ':region_dpid' => $region_dpid,
            ':Contratid'   => $contratid,
            ':moneyid'     => $moneyid,
            ':id'          => $id
        ]);
    }

    /**
     * DELETE - Remove an Ordre de Virement (Triggers CASCADE deletion on linked facture_ordres_virement & historique)
     */
    public function delete($id)
    {
        $query = "DELETE FROM ordres_virement WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }



    /**
     * Get the total COUNT of Ordres de Virement for a specific user
     */
    public function getCountByUserAndNature($user_id, $nature_id)
    {
        $query = "SELECT COUNT(ov.id) AS total_ov
                  FROM utilisateur u
                  JOIN affectation a ON u.id = a.utilisateurid
                  JOIN contrat c ON a.Contratid = c.id
                  JOIN ordres_virement ov ON c.id = ov.Contratid
                  WHERE u.id = :user_id 
                  AND ov.nature_OVid = :nature_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':nature_id', $nature_id, PDO::PARAM_INT);
        $stmt->execute();

        // Retourne un entier (int)
        return (int) $stmt->fetchColumn();
    }



    public function getAllWithDetails()
    {
        $query = "
            SELECT
                ov.id,
                ov.Num_OV,
                ov.Num_KTP,
                ov.Date_OV,
                ov.region_dpid,
                ov.Contratid      AS contrat_id,

                -- Nature OV
                nov.code          AS nature_code,
                nov.label         AS nature_label,

                -- Région / Structure
                rdp.code          AS region_code,
                rdp.label         AS region_label,

                -- Contrat
                cnt.num_Contrat,

                -- Fournisseur
                cnt.Fournisseur_id AS fournisseur_id,
                f.Nom_Fournisseur  AS nom_Fournisseur,

                -- Monnaie
                m.code            AS money_code,
                m.label           AS money_label,

                -- Dernier statut OV
                (
                    SELECT sov.label
                    FROM historique_status_ov hov
                    JOIN statut_ov sov ON hov.statut_OVid = sov.id
                    WHERE hov.ordres_virementid = ov.id
                    ORDER BY hov.date_status_OV DESC
                    LIMIT 1
                ) AS dernier_statut,

                -- Montant total des factures liées
                (
                    SELECT COALESCE(SUM(fc.Montant), 0)
                    FROM facture_ordres_virement fov
                    JOIN facture fc ON fov.Factureid = fc.id
                    WHERE fov.ordres_virementid = ov.id
                ) AS montant_total,

                -- Type OV (construit à partir de la nature et du fournisseur)
                CONCAT('Ordre de virement ', nov.label, ' Fournisseurs Étrangers') AS type_ov

            FROM ordres_virement ov
            LEFT JOIN nature_ov   nov ON ov.nature_OVid = nov.id
            LEFT JOIN region_dp   rdp ON ov.region_dpid  = rdp.id
            LEFT JOIN contrat     cnt ON ov.Contratid     = cnt.id
            LEFT JOIN fournisseur f   ON cnt.Fournisseur_id = f.id
            LEFT JOIN money       m   ON ov.moneyid        = m.id
            ORDER BY ov.id DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getAllWithDetailsByUser($user_id)
    {
        $query = "
                    SELECT
                        ov.id,
                        ov.Num_OV,
                        ov.Num_KTP,
                        ov.Date_OV,
                        ov.region_dpid,
                        ov.Contratid      AS contrat_id,
                        nov.code          AS nature_code,
                        nov.label         AS nature_label,
                        rdp.code          AS region_code,
                        rdp.label         AS region_label,
                        cnt.num_Contrat,
                        cnt.Fournisseur_id AS fournisseur_id,
                        f.Nom_Fournisseur  AS nom_Fournisseur,
                        m.code            AS money_code,
                        m.label           AS money_label,
                        (
                            SELECT sov.label
                            FROM historique_status_ov hov
                            JOIN statut_ov sov ON hov.statut_OVid = sov.id
                            WHERE hov.ordres_virementid = ov.id
                            ORDER BY hov.date_status_OV DESC
                            LIMIT 1
                        ) AS dernier_statut,
                        (
                            SELECT COALESCE(SUM(fc.Montant), 0)
                            FROM facture_ordres_virement fov
                            JOIN facture fc ON fov.Factureid = fc.id
                            WHERE fov.ordres_virementid = ov.id
                        ) AS montant_total,
                        CONCAT('Ordre de virement ', nov.label, ' Fournisseurs Étrangers') AS type_ov
                    FROM ordres_virement ov
                    JOIN contrat cnt        ON ov.Contratid      = cnt.id
                    JOIN affectation a      ON cnt.id             = a.Contratid
                    LEFT JOIN nature_ov nov ON ov.nature_OVid    = nov.id
                    LEFT JOIN region_dp rdp ON ov.region_dpid    = rdp.id
                    LEFT JOIN fournisseur f ON cnt.Fournisseur_id = f.id
                    LEFT JOIN money m       ON ov.moneyid         = m.id
                    WHERE a.utilisateurid = :user_id
                    ORDER BY ov.id DESC
                ";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
