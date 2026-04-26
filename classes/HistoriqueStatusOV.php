<?php

class HistoriqueStatusOV
{
    private $pdo;

    // Properties mapping to columns in 'historique_status_ov'
    private $ordres_virementid;
    private $statut_ovid;
    private $date_status_ov;

    public function __construct($db)
    {
        $this->pdo = $db;
    }

    // ============================================
    // GETTERS & SETTERS
    // ============================================

    public function getOrdresVirementId()
    {
        return $this->ordres_virementid;
    }
    public function setOrdresVirementId($ov_id)
    {
        $this->ordres_virementid = $ov_id;
    }

    public function getStatutOvId()
    {
        return $this->statut_ovid;
    }
    public function setStatutOvId($statut_id)
    {
        $this->statut_ovid = $statut_id;
    }

    public function getDateStatusOv()
    {
        return $this->date_status_ov;
    }
    public function setDateStatusOv($date_val)
    {
        $this->date_status_ov = $date_val;
    }


    // ============================================
    // CRUD OPERATIONS
    // ============================================

    /**
     * READ - Get All History 
     */
    public function getAll()
    {
        $query = "SELECT * FROM historique_status_ov ORDER BY date_status_OV DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Get ALL status history specifically for one OV 
     */
    public function getHistoryByOV($ordres_virementid)
    {
        $query = "SELECT h.*, s.label as statut_label 
                  FROM historique_status_ov h
                  JOIN statut_ov s ON h.statut_OVid = s.id
                  WHERE h.ordres_virementid = :ov_id 
                  ORDER BY h.date_status_OV DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':ov_id', $ordres_virementid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * CREATE - Add a new status historical record
     */
    public function create($ordres_virementid, $statut_ovid, $date_status_ov = null)
    {
        // Automatically default to now() if date is not provided
        if (is_null($date_status_ov)) {
            $date_status_ov = date('Y-m-d H:i:s');
        }

        $query = "INSERT IGNORE INTO historique_status_ov (ordres_virementid, statut_OVid, date_status_OV) 
                  VALUES (:ov_id, :status_id, NOW())";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':ov_id'    => $ordres_virementid,
            ':status_id' => $statut_ovid,

        ]);
    }

    /**
     * UPDATE - Modify the date of a specific historical state
     * (Normally histories are read-only, but since it's CRUD, here it is updating via composite keys)
     */
    public function updateDate($ordres_virementid, $statut_ovid, $new_date_status_ov)
    {
        $query = "UPDATE historique_status_ov 
                  SET date_status_OV = :new_date 
                  WHERE ordres_virementid = :ov_id AND statut_OVid = :status_id";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':new_date'  => $new_date_status_ov,
            ':ov_id'     => $ordres_virementid,
            ':status_id' => $statut_ovid
        ]);
    }

    /**
     * DELETE - Remove ONE specific status history 
     */
    public function deleteSpecificStatus($ordres_virementid, $statut_ovid)
    {
        $query = "DELETE FROM historique_status_ov 
                  WHERE ordres_virementid = :ov_id AND statut_OVid = :status_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':ov_id', $ordres_virementid, PDO::PARAM_INT);
        $stmt->bindParam(':status_id', $statut_ovid, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * DELETE - Clean ALL history belonging to a specific Ordre de Virement 
     */
    public function clearAllHistoryForOV($ordres_virementid)
    {
        $query = "DELETE FROM historique_status_ov WHERE ordres_virementid = :ov_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':ov_id', $ordres_virementid, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Récupère l'ID d'un statut OV à partir de son code unique.
     * 
     * @param PDO $pdo L'objet de connexion à la base de données
     * @param string $code Le code du statut (ex: 'TRAIT', 'ATF')
     * @return int|false Retourne l'ID du statut ou false s'il n'existe pas
     */
    public static function getStatusIdByCode(PDO $pdo, $code)
    {
        $query = "SELECT id FROM statut_ov WHERE code = :code LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':code' => strtoupper(trim($code))]);

        $id = $stmt->fetchColumn();

        return $id ? (int)$id : false;
    }


    public static function updateOVStatusByCode(PDO $pdo, $ovId, $statusCode, $userId)
    {
        // 1. Get the status ID from the code (e.g., 'TRAIT' -> 1)
        $stmt = $pdo->prepare("SELECT id FROM statut_ov WHERE code = ?");
        $stmt->execute([$statusCode]);
        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new Exception("OV status code '$statusCode' does not exist.");
        }

        // 2. Insert into the history table
        // Note: Use backticks if your table/column names actually contain spaces
        $sql = "INSERT INTO historique_status_ov (ordres_virementid, statut_OVid, date_status_OV, traitant_id) 
            VALUES (:ovid, :sid, NOW(), :uid)";

        $insertStmt = $pdo->prepare($sql);

        return $insertStmt->execute([
            ':ovid' => $ovId,
            ':sid'  => $statusId,
            ':uid'  => $userId,
        ]);
    }
}
