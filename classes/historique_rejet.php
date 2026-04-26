<?php

class historique_rejet
{
    private $Rejetid;
    private $statut_rejetid;

    // ══════════════════════════════════════════════
    // GETTERS & SETTERS
    // ══════════════════════════════════════════════

    public function getRejetid()
    {
        return $this->Rejetid;
    }
    public function setRejetid($Rejetid)
    {
        $this->Rejetid = $Rejetid;
    }

    public function getStatut_rejetid()
    {
        return $this->statut_rejetid;
    }
    public function setStatut_rejetid($statut_rejetid)
    {
        $this->statut_rejetid = $statut_rejetid;
    }

    // ══════════════════════════════════════════════
    // MÉTHODES STATIQUES
    // ══════════════════════════════════════════════

    /**
     * Insert a new status entry using the status CODE (e.g. 'CREE', 'RECUP').
     */
    public static function updateStatusByCode(PDO $pdo, int $rejetId, string $statusCode, int $userId): bool
    {
        // 1. On cherche l'ID du statut
        $stmt = $pdo->prepare("SELECT id FROM statut_rejet WHERE code = ? LIMIT 1");
        $stmt->execute([$statusCode]);
        $statusId = $stmt->fetchColumn();

        if (!$statusId) return false;

        // 2. Requête corrigée avec des noms de paramètres UNIQUES
        $sql = "INSERT INTO historique_rejet (statut_rejetid, Rejetid, date_rejet, traitant_id)
            VALUES (:sid, :rid, NOW(), :uid)
            ON DUPLICATE KEY UPDATE date_rejet = NOW(), traitant_id = :uid_update";

        // 3. On passe bien 4 valeurs pour 4 marqueurs
        return $pdo->prepare($sql)->execute([
            ':sid'        => $statusId,
            ':rid'        => $rejetId,
            ':uid'        => $userId,
            ':uid_update' => $userId // On répète la valeur ici avec un autre nom
        ]);
    }

    /**
     * Get status row by CODE (returns full row as associative array).
     */
    public static function getStatusByCode(PDO $pdo, string $statusCode): array
    {
        $stmt = $pdo->prepare("SELECT * FROM statut_rejet WHERE code = ? LIMIT 1");
        $stmt->execute([$statusCode]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            throw new Exception("Le code statut rejet '$statusCode' n'existe pas dans la base de données.");
        }

        return $status;
    }

    /**
     * Insert a historique row using a known status ID.
     */
    public static function insertHistorique(PDO $pdo, int $rejetId, int $statusId): bool
    {
        $sql = "INSERT INTO historique_rejet (statut_rejetid, Rejetid, date_rejet)
                VALUES (:sid, :rid, CURDATE())";

        return $pdo->prepare($sql)->execute([':sid' => $statusId, ':rid' => $rejetId]);
    }

    /**
     * Get the most recent status of a rejet.
     */
    public static function getCurrentStatus(PDO $pdo, int $rejetId): ?array
    {
        $sql = "SELECT sr.code, sr.label, hr.date_rejet
                FROM historique_rejet hr
                JOIN statut_rejet sr ON hr.statut_rejetid = sr.id
                WHERE hr.Rejetid = ?
                ORDER BY hr.date_rejet DESC, hr.statut_rejetid DESC
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rejetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get full status history for a rejet.
     */
    public static function getHistory(PDO $pdo, int $rejetId): array
    {
        $sql = "SELECT sr.label, hr.date_rejet
                FROM historique_rejet hr
                JOIN statut_rejet sr ON hr.statut_rejetid = sr.id
                WHERE hr.Rejetid = ?
                ORDER BY hr.date_rejet DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rejetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
