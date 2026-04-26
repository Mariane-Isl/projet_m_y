<?php

class historique_borderau
{
    private $Bordereauid;
    private $statut_borderauid;

    // ==========================================
    // GETTERS & SETTERS
    // ==========================================

    public function getBordereauid()
    {
        return $this->Bordereauid;
    }
    public function setBordereauid($Bordereauid)
    {
        $this->Bordereauid = $Bordereauid;
    }

    public function getStatut_borderauid()
    {
        return $this->statut_borderauid;
    }
    public function setStatut_borderauid($statut_borderauid)
    {
        $this->statut_borderauid = $statut_borderauid;
    }



    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Updates a Bordereau's status using the Status CODE
     */
    public static function updateStatusByCode(PDO $pdo, $bordereauId, $statusCode, $userId)
    {
        $stmt = $pdo->prepare("SELECT id FROM statut_borderau WHERE code = ?");
        $stmt->execute([$statusCode]);
        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new Exception("Bordereau status code '$statusCode' does not exist.");
        }

        $sql = "INSERT INTO historique_borderau (Bordereauid, statut_borderauid, date_historique, traitant_id) 
                VALUES (:bid, :sid , NOW(), :uid)";

        $insertStmt = $pdo->prepare($sql);

        return $insertStmt->execute([
            ':bid' => $bordereauId,
            ':sid' => $statusId,
            ':uid' => $userId,
        ]);
    }

    /**
     * Gets the current (most recent) status of a specific Bordereau
     */
    public static function getCurrentStatus(PDO $pdo, $bordereauId)
    {
        $sql = "SELECT s.code, s.label, h.`date` 
                FROM historique_borderau h
                JOIN statut_borderau s ON h.statut_borderauid = s.id
                WHERE h.Bordereauid = ?
                ORDER BY h.`date` DESC, h.statut_borderauid DESC 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bordereauId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the complete timeline for a Bordereau
     */
    public static function getHistory(PDO $pdo, $bordereauId)
    {
        $sql = "SELECT s.label, h.`date` 
                FROM historique_borderau h
                JOIN statut_borderau s ON h.statut_borderauid = s.id
                WHERE h.Bordereauid = ?
                ORDER BY h.`date` DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bordereauId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // amie fucntions 
    /**
     * 1. Fonction pour récupérer l'ID du statut à partir de son code (ex: 'ARRIVE')
     */
    public static function getStatusByCode(PDO $pdo, $statusCode)
    {
        $stmt = $pdo->prepare("SELECT * FROM statut_borderau WHERE code = ?");
        $stmt->execute([$statusCode]);

        // fetch(PDO::FETCH_ASSOC) récupère la ligne sous forme de tableau
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            throw new Exception("Le code statut '$statusCode' n'existe pas dans la base de données.");
        }

        return $status;
    }
    /**
     * 2. Fonction pour insérer la nouvelle ligne dans l'historique (avec l'ID du statut)
     */
    public static function insertHistorique(PDO $pdo, $bordereauId, $statusId,$userId)
    {
        // Remarque : j'ai gardé "date_historique" comme dans votre fonction, 
        // assurez-vous que c'est bien le nom de la colonne dans votre table.
        $sql = "INSERT INTO historique_borderau (Bordereauid, statut_borderauid, date_historique, traitant_id) 
                VALUES (:bid, :sid , NOW(), :uid)";

        $insertStmt = $pdo->prepare($sql);

        return $insertStmt->execute([
            ':bid' => $bordereauId,
            ':sid' => $statusId,
            ':uid' => $userId,
        ]);
    }

    public static function getDetailsBordereau(PDO $pdo, int $id_bordereau): ?array
    {
        $sql = "SELECT 
                    -- Informations du Bordereau
                    b.id AS bordereau_id,
                    b.num_bordereau,
                    b.date_bordereau,
                    b.emeteur_id,
                    
                    -- Informations de l'Émetteur (Utilisateur)
                    u.nom AS emetteur_nom,
                    u.prenom AS emetteur_prenom,
                    u.user_name AS emetteur_username,
                    u.region_dp_id,


                     rdp.code AS region_code,
                    rdp.label AS region_label,
                    
                    -- Informations du Contrat
                    c.id AS contrat_id,
                    c.num_Contrat,
                    
                    -- Informations du Fournisseur
                    f.id AS fournisseur_id,
                    f.Nom_Fournisseur,
                    f.code AS code_fournisseur,
                    f.paye_id,
                    
                    -- Informations du Dernier Statut (Historique)
                    hb.date_historique AS derniere_date_statut,
                    sb.id AS statut_id,
                    sb.code AS statut_code,
                    sb.label AS statut_label
                    
                FROM Bordereau b
                -- Jointure pour récupérer les infos de l'émetteur
                LEFT JOIN utilisateur u ON b.emeteur_id = u.id

                LEFT JOIN region_dp rdp ON u.region_dp_id = rdp.id
                
                -- Jointures pour le contrat et le fournisseur
                LEFT JOIN Contrat c ON b.Contrat_id = c.id
                LEFT JOIN Fournisseur f ON c.Fournisseur_id = f.id
                
                -- Jointure avec l'historique et le statut
                LEFT JOIN historique_borderau hb ON hb.Bordereauid = b.id
                LEFT JOIN statut_borderau sb ON hb.statut_borderauid = sb.id
                
                WHERE b.id = :id_bordereau
                
                -- Trie par date d'historique la plus récente en premier
                ORDER BY hb.date_historique DESC 
                
                -- On ne garde que la première ligne
                LIMIT 1";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_bordereau', $id_bordereau, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du bordereau : " . $e->getMessage());
            return null;
        }
    }


     public static function getDetailsBordereaubyNum(PDO $pdo, int $num): ?array
    {
        $sql = "SELECT 
                    -- Informations du Bordereau
                    b.id AS bordereau_id,
                    b.num_bordereau,
                    b.date_bordereau,
                    b.emeteur_id,
                    
                    -- Informations de l'Émetteur (Utilisateur)
                    u.nom AS emetteur_nom,
                    u.prenom AS emetteur_prenom,
                    u.user_name AS emetteur_username,
                    u.region_dp_id,


                     rdp.code AS region_code,
                    rdp.label AS region_label,
                    
                    -- Informations du Contrat
                    c.id AS contrat_id,
                    c.num_Contrat,
                    
                    -- Informations du Fournisseur
                    f.id AS fournisseur_id,
                    f.Nom_Fournisseur,
                    f.code AS code_fournisseur,
                    f.paye_id,
                    
                    -- Informations du Dernier Statut (Historique)
                    hb.date_historique AS derniere_date_statut,
                    sb.id AS statut_id,
                    sb.code AS statut_code,
                    sb.label AS statut_label
                    
                FROM Bordereau b
                -- Jointure pour récupérer les infos de l'émetteur
                LEFT JOIN utilisateur u ON b.emeteur_id = u.id

                LEFT JOIN region_dp rdp ON u.region_dp_id = rdp.id
                
                -- Jointures pour le contrat et le fournisseur
                LEFT JOIN Contrat c ON b.Contrat_id = c.id
                LEFT JOIN Fournisseur f ON c.Fournisseur_id = f.id
                
                -- Jointure avec l'historique et le statut
                LEFT JOIN historique_borderau hb ON hb.Bordereauid = b.id
                LEFT JOIN statut_borderau sb ON hb.statut_borderauid = sb.id
                
                WHERE b.num_bordereau = :num_bordereau
                
                -- Trie par date d'historique la plus récente en premier
                ORDER BY hb.date_historique DESC 
                
                -- On ne garde que la première ligne
                LIMIT 1";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':num_bordereau', $num, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du bordereau : " . $e->getMessage());
            return null;
        }
    }
}
