<?php

class historique_facture
{
    private $Factureid;
    private $statut_factureid;

    // ==========================================
    // GETTERS & SETTERS
    // ==========================================

    public function getFactureid()
    {
        return $this->Factureid;
    }
    public function setFactureid($Factureid)
    {
        $this->Factureid = $Factureid;
    }

    public function getStatut_factureid()
    {
        return $this->statut_factureid;
    }
    public function setStatut_factureid($statut_factureid)
    {
        $this->statut_factureid = $statut_factureid;
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Updates a Facture's status using the Status CODE.
     * La date est insérée automatiquement par MySQL via NOW().
     */
    public static function updateStatusByCode(PDO $pdo, $factureId, $statusCode)
    {
        $stmt = $pdo->prepare("SELECT id FROM statut_facture WHERE code = ?");
        $stmt->execute([$statusCode]);
        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new Exception("Status code '$statusCode' inconnu.");
        }

        // Insertion automatique de la date via NOW()
        $sql = "INSERT INTO historique_facture (Factureid, statut_factureid, date_statuts) 
                VALUES (:fid, :side, NOW())";

        $insertStmt = $pdo->prepare($sql);

        return $insertStmt->execute([
            ':fid' => $factureId,
            ':side' => $statusId
        ]);
    }

    /**
     * Retrieves the most recent status code and label for a specific Facture
     */
    public static function getCurrentStatus(PDO $pdo, $factureId)
    {
        $sql = "SELECT s.code, s.label, h.date_statuts 
                FROM historique_facture h
                JOIN statut_facture s ON h.statut_factureid = s.id
                WHERE h.Factureid = ?
                ORDER BY h.date_statuts DESC, h.statut_factureid DESC 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$factureId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the full history of a Facture
     */
    public static function getFullHistory(PDO $pdo, $factureId)
    {
        $sql = "SELECT s.label, h.date_statuts 
                FROM historique_facture h
                JOIN statut_facture s ON h.statut_factureid = s.id
                WHERE h.Factureid = ?
                ORDER BY h.date_statuts DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$factureId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
