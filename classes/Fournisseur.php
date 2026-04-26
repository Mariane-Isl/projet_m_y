<?php


class Fournisseur
{
    // 1. Propriétés privées (correspondant aux colonnes de la table)
    private $id;
    private $code;
    private $nom_Fournisseur;
    private $paye_id;
    private $paye;

    // 2. Constructeur (optionnel mais très pratique pour hydrater l'objet)
    public function __construct($id = null, $code = null, $nom_Fournisseur = null, $paye_id = null)
    {
        $this->id   = $id;
        $this->code = $code;
        $this->nom_Fournisseur  = $nom_Fournisseur;
        $this->paye_id = $paye_id;
    }
    // ==========================================
    // 3. GETTERS & SETTERS (Encapsulation)
    // ==========================================

    public function getId()   { return $this->id; }
    public function getCode() { return $this->code; }
    public function getnom_Fournisseur()  { return $this->nom_Fournisseur; }
    public function getpaye_id() { return $this->paye_id; }
    public function getPaye(): ?Paye
    {
        return $this->paye;
    }
    public function setPays(?Paye $paye)
    {
        $this->paye = $paye;
    }
    public function setCode($code) { $this->code = $code; }
    public function setnom_Fournisseur($nom_Fournisseur)   { $this->nom_Fournisseur  = $nom_Fournisseur; }
    public function setpaye_id($paye_id) { $this->paye_id = $paye_id; }

    // ==========================================
    // 4. MÉTHODES STATIQUES (Interactions BDD)
    // ==========================================

    /**
     * Insérer un nouveau fournisseur dans la base de données
     * @param PDO $db
     * @param string $code Le code du fournisseur (ex: 'F001')
     * @param string $nom_Fournisseur  Le nom_Fournisseur du fournisseur (ex: 'SARL InfoTech')
     * @param string $paye_id Le paye_id (ex: 'Algérie')
     * @return Fournisseur|false
     */
    public static function insert(PDO $db, $code, $nom_Fournisseur, $paye_id)
    {
        try {
            $query = "INSERT INTO fournisseur (code, Nom_Fournisseur, paye_id) VALUES (:code, :nom_Fournisseur, :paye_id)";
            $stmt  = $db->prepare($query);

            // Sécurisation des données
            $code = strtoupper(htmlspecialchars(strip_tags(trim($code))));
            $nom_Fournisseur  = htmlspecialchars(strip_tags(trim($nom_Fournisseur)));
            $paye_id = htmlspecialchars(strip_tags(trim($paye_id)));

            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':nom_Fournisseur',  $nom_Fournisseur);
            $stmt->bindParam(':paye_id', $paye_id);

            if ($stmt->execute()) {
                $id = $db->lastInsertId();
                return new self($id, $code, $nom_Fournisseur, $paye_id);
            }
            return false;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Récupérer la liste de TOUS les fournisseurs
     * @param PDO $db
     * @return Fournisseur[]
     */
    public static function getAll(PDO $db)
    {
        $query = "SELECT id, code, Nom_Fournisseur AS nom_Fournisseur, paye_id FROM fournisseur ORDER BY Nom_Fournisseur ASC";
        $stmt  = $db->prepare($query);
        $stmt->execute();

        $fournisseurs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fournisseurs[] = new self($row['id'], $row['code'], $row['nom_Fournisseur'], $row['paye_id']);
        }
        return $fournisseurs;
    }

    /**
     * Récupérer un fournisseur par son ID
     * @param PDO $db
     * @param int $id
     * @return Fournisseur|null
     */
    public static function getById(PDO $db, $id)
    {
        $query = "SELECT id, code, Nom_Fournisseur AS nom_Fournisseur, paye_id FROM fournisseur WHERE id = :id LIMIT 1";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new self($row['id'], $row['code'], $row['nom_Fournisseur'], $row['paye_id']);
        }
        return null;
    }

    /**
     * ✅ [CORRECTION MVC] Mettre à jour un fournisseur existant
     * Cette méthode centralise le SQL ici dans le Modèle (plus jamais dans le Contrôleur).
     *
     * @param PDO    $db
     * @param int    $id       ID du fournisseur à modifier
     * @param string $code     Nouveau code
     * @param string $nom      Nouveau nom
     * @param int    $paye_id  Nouvel ID pays
     * @return bool|string     true si succès, 'duplicate' si code dupliqué, false si erreur
     */
    public static function update(PDO $db, int $id, string $code, string $nom, int $paye_id)
    {
        try {
            // Sécurisation des données entrantes
            $code = strtoupper(htmlspecialchars(strip_tags(trim($code))));
            $nom  = htmlspecialchars(strip_tags(trim($nom)));

            $query = "UPDATE fournisseur 
                      SET code = :code, Nom_Fournisseur = :nom, paye_id = :paye_id 
                      WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':code',    $code,    PDO::PARAM_STR);
            $stmt->bindParam(':nom',     $nom,     PDO::PARAM_STR);
            $stmt->bindParam(':paye_id', $paye_id, PDO::PARAM_INT);
            $stmt->bindParam(':id',      $id,      PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Violation de contrainte d'unicité (ex : code déjà utilisé par un autre fournisseur)
            if ($e->getCode() == 23000) {
                return 'duplicate';
            }
            error_log("Erreur dans Fournisseur::update : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un code fournisseur existe déjà (pour la validation AJAX)
     * @param PDO $db
     * @param string $code
     * @return bool
     */
    public static function codeExists(PDO $db, $code)
    {
        $query = "SELECT COUNT(*) as total FROM fournisseur WHERE code = :code";
        $stmt  = $db->prepare($query);
        $code  = strtoupper(trim($code));
        $stmt->bindParam(':code', $code);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }
    public static function getAllWithPaye(PDO $db)
    {
        // Requête avec jointure. On donne des alias (f_code, p_code) car les deux tables ont une colonne "code"
        $query = "SELECT 
                    f.id AS f_id, 
                    f.code AS f_code,
                    p.code AS p_code,
                    f.Nom_Fournisseur AS nom_Fournisseur, 
                    f.paye_id,
                    p.label AS p_label
                  FROM fournisseur f
                  INNER JOIN paye p ON f.paye_id = p.id
                  ORDER BY f.Nom_Fournisseur DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $fournisseurs = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 1. On crée d'abord l'objet Pays
            $pays = new Paye($row['paye_id'], $row['p_code'], $row['p_label']);

            // 2. On crée l'objet Fournisseur
            $fournisseur = new self(
                $row['f_id'],
                $row['f_code'],
                $row['nom_Fournisseur'],
                
                $row['paye_id']
            );

            // 3. On associe le Pays au Fournisseur
            $fournisseur->setPays($pays);

            // 4. On ajoute à la liste
            $fournisseurs[] = $fournisseur;
        }

        return $fournisseurs;
    }
}
?>
