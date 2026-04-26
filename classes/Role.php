<?php

class Role
{
    // 1. Propriétés privées (correspondant aux colonnes de la table)
    private $id;
    private $code;
    private $label;

    // 2. Constructeur
    public function __construct($id = null, $code = null, $label = null)
    {
        $this->id = $id;
        $this->code = $code;
        $this->label = $label;
    }

    // ==========================================
    // 3. GETTERS & SETTERS
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
    // 4. MÉTHODES STATIQUES (BDD)
    // ==========================================

    /**
     * Insérer un nouveau rôle
     */
    public static function insert(PDO $db, $code, $label)
    {
        try {

            $query = "INSERT INTO role (code, label)
                      VALUES (:code, :label)";

            $stmt = $db->prepare($query);

            // sécurisation
            $code = strtoupper(trim(htmlspecialchars(strip_tags($code))));
            $label = htmlspecialchars(strip_tags($label));

            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':label', $label);

            if ($stmt->execute()) {

                $id = $db->lastInsertId();

                return new self($id, $code, $label);
            }

            return false;
        } catch (PDOException $e) {

            throw $e;
        }
    }

    /**
     * Récupérer un rôle par ID
     */
    public static function getById(PDO $db, $id)
    {
        $query = "SELECT id, code, label
                  FROM role
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $db->prepare($query);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            return new self(
                $row['id'],
                $row['code'],
                $row['label']
            );
        }

        return null;
    }

    /**
     * Récupérer tous les rôles
     */
    public static function getAll(PDO $db)
    {
        $query = "SELECT id, code, label
                  FROM role
                  ORDER BY label ASC";

        $stmt = $db->prepare($query);

        $stmt->execute();

        $roles = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $roles[] = new self(
                $row['id'],
                $row['code'],
                $row['label']
            );
        }

        return $roles;
    }

    /**
     * Vérifier si un code rôle existe
     */
    public static function codeExists(PDO $db, $code)
    {
        $query = "SELECT COUNT(*) as total
                  FROM role
                  WHERE code = :code";

        $stmt = $db->prepare($query);

        $code = strtoupper(trim($code));

        $stmt->bindParam(':code', $code);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] > 0;
    }


    public static function getHtmlOptions( $roles , $selectedId = null)
    {
        // 1. On récupère tous les rôles via votre méthode existante
       

        // 2. On initialise la première option par défaut
        $html = '<option value="">-- Sélectionner --</option>' . "\n";

        // 3. On boucle pour construire les options
        foreach ($roles as $role) {
            // Sécurité : on échappe les données pour éviter les failles XSS
            $id = htmlspecialchars($role->getId());
            $label = htmlspecialchars($role->getLabel());

            // Vérification si cette option doit être sélectionnée (utile pour l'édition)
            $selected = ($selectedId !== null && $selectedId == $id) ? 'selected' : '';

            // Concaténation de l'option
            $html .= "<option value=\"{$id}\" {$selected}>{$label}</option>\n";
        }

        // 4. On retourne le texte HTML généré
        return $html;
    }
}
