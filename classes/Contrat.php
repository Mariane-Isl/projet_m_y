<?php

class Contrat
{
    // 1. Propriétés privées (correspondant aux colonnes de la table)
    private $id;
    private $fournisseur_id; // Clé étrangère → lien avec la table fournisseur
    private $num_contrat;
    private $utilisateur = null;// Chargée via JOIN avec affectation + utilisateur
    

    // 2. Constructeur
    public function __construct($id = null, $fournisseur_id = null, $num_contrat = null)
    {
        $this->id             = $id;
        $this->fournisseur_id = $fournisseur_id;
        $this->num_contrat         = $num_contrat;
        
       
       
    }

    // ==========================================
    // 3. GETTERS & SETTERS (Encapsulation)
    // ==========================================

    public function getId()            { return $this->id; }
    public function getFournisseurId() { return $this->fournisseur_id; }
    public function getnum_contrat()        { return $this->num_contrat; }
    public function getUtilisateur()             { return $this->utilisateur; }
    public function setUtilisateur($utilisateur) { $this->utilisateur = $utilisateur; }

   

    // ==========================================
    // 4. MÉTHODES STATIQUES (Interactions BDD)
    // ==========================================

    /**
     * Insérer un nouveau contrat dans la base de données
     * @param PDO    $db
     * @param int    $fournisseur_id
     * @param string $num_contrat     ex: 'C-2025-001'
     *
     * @return Contrat|false
     */
    public static function insert(PDO $db, $fournisseur_id, $num_contrat )
    {
        try {
            $query = "INSERT INTO contrat (fournisseur_id, num_contrat)
                      VALUES (:fournisseur_id, :num_contrat )";
            $stmt  = $db->prepare($query);

            // Sécurisation des données
            $num_contrat = strtoupper(htmlspecialchars(strip_tags(trim($num_contrat))));

            $stmt->bindParam(':fournisseur_id', $fournisseur_id, PDO::PARAM_INT);
            $stmt->bindParam(':num_contrat',         $num_contrat);
            

            if ($stmt->execute()) {
                $id = $db->lastInsertId();
                return new self($id, $fournisseur_id, $num_contrat);
            }
            return false;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Récupérer TOUS les contrats d'UN fournisseur spécifique
     * C'est la requête centrale : WHERE fournisseur_id = ?
     * @param PDO $db
     * @param int $fournisseur_id
     * @return Contrat[]
     */
    public static function getByFournisseur(PDO $db, $fournisseur_id)
{
    $query = "SELECT c.id, c.fournisseur_id, c.num_contrat,
                     u.id AS u_id, u.nom AS u_nom, u.prenom AS u_prenom
              FROM contrat c
              LEFT JOIN affectation a ON a.Contratid = c.id
              LEFT JOIN utilisateur u ON u.id = a.utilisateurid
              WHERE c.fournisseur_id = :fournisseur_id
              ORDER BY c.id DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fournisseur_id', $fournisseur_id, PDO::PARAM_INT);
    $stmt->execute();

    $contrats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contrat = new self($row['id'], $row['fournisseur_id'], $row['num_contrat']);

        // Comme Pays dans Fournisseur, on crée l'objet Utilisateur et on l'attache
        if ($row['u_id']) {
            $user = new Utilisateur($row['u_id'], $row['u_nom'], $row['u_prenom']);
            $contrat->setUtilisateur($user);
        }

        $contrats[] = $contrat;
    }
    return $contrats;
}

    /**
     * Vérifier si un numéro de contrat existe déjà (pour la validation AJAX)
     * @param PDO $db
     * @param string $num_contrat
     * @return bool
     */
    public static function numeroExists(PDO $db, string $numero): bool 
    {
        try {
            // 1. On prépare la requête (Sécurité anti-injection SQL)
            // On utilise COUNT(*) qui est plus rapide et léger que de récupérer toute la ligne
            $query = "SELECT COUNT(*) FROM Contrat WHERE num_Contrat = :num_contrat";
            
            $stmt = $db->prepare($query);
            
            // 2. On attache la valeur en précisant que c'est une chaîne de caractères (PARAM_STR)
            $stmt->bindParam(':num_contrat', $numero, PDO::PARAM_STR);
            
            // 3. On exécute la requête
            $stmt->execute();
            
            // 4. On récupère le résultat (le nombre de lignes trouvées)
            $count = $stmt->fetchColumn();
            
            // 5. On retourne true si le compte est supérieur à 0, sinon false
            return $count > 0;

        } catch (PDOException $e) {
            // En cas d'erreur base de données, on log l'erreur (idéalement) 
            // et on retourne false par sécurité, ou on lève une exception.
            error_log("Erreur dans Contrat::numeroExists : " . $e->getMessage());
            return false; 
        }
    }

    // =========================
    // GET CONTRAT BY NUMERO
    // =========================

    /**
     * Récupère un contrat spécifique à partir de son numéro unique.
     * 
     * @param PDO $db La connexion à la base de données
     * @param string $num_contrat Le numéro du contrat recherché
     * @return self|null Retourne un objet Contrat si trouvé, sinon null
     */
    public static function getByNumContrat(PDO $db, string $num_contrat)
    {
        try {
            // 1. Préparation de la requête avec LIMIT 1 pour optimiser la recherche
            $query = "SELECT id, num_Contrat, Fournisseur_id FROM Contrat WHERE num_Contrat = :num_contrat LIMIT 1";
            
            $stmt = $db->prepare($query);
            
            // On nettoie la chaîne et on l'attache (sécurité anti-injection SQL)
            $num_contrat = trim($num_contrat);
            $stmt->bindParam(':num_contrat', $num_contrat, PDO::PARAM_STR);
            
            // 2. Exécution de la requête
            $stmt->execute();
            
            // 3. Récupération du résultat
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Si on trouve une ligne, on instancie et retourne l'objet Contrat
            if ($row) {
                // Assure-toi que les paramètres correspondent au constructeur de ta classe Contrat
                return new self(
    $row['id'],
    $row['Fournisseur_id'],
    $row['num_Contrat']
);
            }

            // Si aucun contrat ne correspond à ce numéro
            return null;

        } catch (PDOException $e) {
            // En cas d'erreur de la BDD, on log l'erreur pour le débogage
            error_log("Erreur dans Contrat::getByNumContrat : " . $e->getMessage());
            return null;
        }
    }
    // ==========================================
    // MÉTHODE POUR MODIFIER UN CONTRAT
    // ==========================================
    
    public static function update(PDO $db, $id, $num_contrat)
    {
        try {
            $query = "UPDATE contrat SET num_contrat = :num_contrat WHERE id = :id";
            $stmt  = $db->prepare($query);

            // Sécurisation
            $num_contrat = strtoupper(htmlspecialchars(strip_tags(trim($num_contrat))));

            $stmt->bindParam(':num_contrat', $num_contrat);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Si le numéro de contrat existe déjà (Violation de contrainte d'unicité)
            if ($e->getCode() == 23000) {
                return 'duplicate';
            }
            error_log("Erreur dans Contrat::update : " . $e->getMessage());
            return false;
        }
    }


 public static function getAssignedUserId(PDO $db, $contrat_id)
    {
        $query = "SELECT utilisateurid FROM affectation WHERE Contratid = :contrat_id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn(); // Retourne l'ID ou false si non trouvé
    }
    
    }
?>