<?php

class paye
{
    // 1. Propriétés privées (colonnes : id, code, label)
    private $id;
    private $code;
    private $label;

    // 2. Constructeur (Paramètres mis à null par défaut pour la flexibilité)
    public function __construct($id = null, $code = null, $label = null)
    {
        $this->id = $id;
        $this->code = $code;
        $this->label = $label;
    }
    
    // ==========================================
    // 3. GETTERS & SETTERS (Encapsulation)
    // ==========================================

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getCode() { return $this->code; }
    public function setCode($code) { $this->code = $code; }

    public function getLabel() { return $this->label; }
    public function setLabel($label) { $this->label = $label; }

    
    // ==========================================
    // 4. MÉTHODES STATIQUES (Interactions BDD)
    // ==========================================

    /**
     * Insérer un nouveau paye
     * @param PDO $db Connexion
     * @param string $code (ex: DZ)
     * @param string $label (ex: Algérie)
     */
    public static function insert(PDO $db, $code, $label)
    {
        try {
            $query = "INSERT INTO paye (code, label) VALUES (:code, :label)";
            $stmt = $db->prepare($query);

            // Sécurisation des données
            $code = strtoupper(htmlspecialchars(strip_tags($code)));
            $label = htmlspecialchars(strip_tags($label));

            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':label', $label);

            if ($stmt->execute()) {
                $id = $db->lastInsertId();
                // On retourne l'objet créé avec son ID
                return new self($id, $code, $label);
            }
            return false;
            
        } catch (PDOException $e) {
            throw $e; 
        }
    }

    /**
     * Récupérer la liste de TOUS les paye (pour remplir ton <select>)
     */
    public static function getAll(PDO $db)
    {
        $query = "SELECT id, code, label FROM paye ORDER BY label ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $liste = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Création d'un objet paye pour chaque ligne SQL
            $liste[] = new self(
                $row['id'], 
                $row['code'], 
                $row['label']
            );
        }

        return $liste;
    }

    /**
     * Récupérer un paye par son ID
     */
    public static function getById(PDO $db, $id)
    {
        $query = "SELECT id, code, label FROM paye WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new self($row['id'], $row['code'], $row['label']);
        }
        return null;
    }

    /**
     * Vérifier si un code paye existe déjà (pour éviter les doublons)
     */
    public static function codeExists(PDO $db, $code) {
        $query = "SELECT COUNT(*) as total FROM paye WHERE code = :code";
        $stmt = $db->prepare($query);
        
        $code = strtoupper(trim($code));
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'] > 0;
    }
}
?>