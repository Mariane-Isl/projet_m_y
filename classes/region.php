<?php

class Region
{
    // 1. Propriétés privées (correspondant aux colonnes de la table)
    private $id;
    private $code;
    private $label;

    // 2. Constructeur (optionnel mais très pratique pour hydrater l'objet)
    public function __construct($id = null, $code = null, $label = null)
    {
        $this->id = $id;
        $this->code = $code;
        $this->label = $label;
    }

    // ==========================================
    // 3. GETTERS & SETTERS (Encapsulation)
    // ==========================================

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    // ==========================================
    // 4. MÉTHODES STATIQUES (Interactions BDD)
    // ==========================================

    /**
     * Insérer une nouvelle région dans la base de données
     * @param PDO $db L'objet de connexion PDO
     * @param string $code Le code de la région (ex: 'ALG')
     * @param string $label Le label de la région (ex: 'Alger')
     * @return Region|false Retourne l'objet Region créé, ou false en cas d'échec
     */
    public static function insert(PDO $db, $code, $label)
    {
        try {
            $query = "INSERT INTO region_dp (code, label) VALUES (:code, :label)";
            $stmt = $db->prepare($query);

            // Sécurisation des données
            $code = htmlspecialchars(strip_tags($code));
            $label = htmlspecialchars(strip_tags($label));

            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':label', $label);

            if ($stmt->execute()) {
                // Récupère l'ID généré par l'AUTO_INCREMENT
                $id = $db->lastInsertId();
                // Retourne la nouvelle instance de la région
                return new self($id, $code, $label);
            }
            return false;
        } catch (PDOException $e) {
            // Gère l'erreur (ex: code ou label déjà existant car UNIQUE)
           // die("Erreur lors de l'insertion de la région : " . $e->getMessage());
             throw $e; 
        }
    }

    /**
     * Récupérer un objet Region par son ID
     * @param PDO $db L'objet de connexion PDO
     * @param int $id L'ID de la région
     * @return Region|null Retourne un objet Region, ou null si introuvable
     */
    public static function getById(PDO $db, $id)
    {
        $query = "SELECT id, code, label FROM region_dp WHERE id = :id LIMIT 1";
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
     * Récupérer la liste de TOUS les objets Region
     * @param PDO $db L'objet de connexion PDO
     * @return Region[] Retourne un tableau d'objets Region
     */
    public static function getAll(PDO $db)
    {
        $query = "SELECT id, code, label FROM region_dp ORDER BY label ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $regions = [];

        // Boucle sur les résultats pour créer un tableau d'objets Region
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $regions[] = new self($row['id'], $row['code'], $row['label']);
        }

        return $regions;
    }



    public static function getHtmlOptions($regions, $selectedId = null)
{
    // 1. On récupère toutes les régions via votre méthode getAll()
   

    // 2. Initialisation avec l'option par défaut
    $html = '<option value="">-- Sélectionner --</option>' . "\n";

    // 3. Boucle sur chaque objet de région
    foreach ($regions as $region) {
        // Sécurisation contre les failles XSS
        $id = htmlspecialchars($region->getId());
        $label = htmlspecialchars($region->getLabel());

        // Vérification pour la pré-sélection (utile pour l'édition)
        $selected = ($selectedId !== null && $selectedId == $id) ? ' selected' : '';

        // Ajout de la balise <option> générée
        $html .= "<option value=\"{$id}\"{$selected}>{$label}</option>\n";
    }

    // 4. On retourne la chaîne HTML complète
    return $html;
}


    /**
     * Vérifier si un code région existe déjà
     * @param PDO $db
     * @param string $code
     * @return bool
     */
    public static function codeExists(PDO $db, $code) {
        $query = "SELECT COUNT(*) as total FROM region_dp WHERE code = :code";
        $stmt = $db->prepare($query);
        $code = strtoupper(trim($code));
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0; // Retourne true si le code existe, sinon false
    }
}
