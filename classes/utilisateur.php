<?php

class Utilisateur
{
    // Propriétés
    private $id;
    private $nom;
    private $prenom;
    private $user_name;
    private $password_user;
    private $active;
    private $role_id;
    private $region_dp_id;
    private $Role;


    // Constructeur
    public function __construct(
        $id = null,
        $nom = null,
        $prenom = null,
        $user_name = null,
        $password_user = null,
        $active = 0,
        $role_id = null,
        $region_dp_id = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->user_name = $user_name;
        $this->password_user = $password_user;
        $this->active = $active;
        $this->role_id = $role_id;
        $this->region_dp_id = $region_dp_id;
        
    }

    // =========================
    // GETTERS & SETTERS
    // =========================

    public function getId(){ return $this->id; }
    public function setId($id){ $this->id = $id; }

    public function getNom(){ return $this->nom; }
    public function setNom($nom){ $this->nom = $nom; }

    public function getPrenom(){ return $this->prenom; }
    public function setPrenom($prenom){ $this->prenom = $prenom; }

    public function getUserName(){ return $this->user_name; }
    public function setUserName($user_name){ $this->user_name = $user_name; }

    public function getPasswordUser(){ return $this->password_user; }
    public function setPasswordUser($password_user){ $this->password_user = $password_user; }

    public function getActive(){ return $this->active; }
    public function setActive($active){ $this->active = $active; }

    public function getRoleId(){ return $this->role_id; }
    public function setRoleId($role_id){ $this->role_id = $role_id; }

    public function getRegionDpId(){ return $this->region_dp_id; }
    public function setRegionDpId($region_dp_id){ $this->region_dp_id = $region_dp_id; }

    public function getRole(){ return $this->Role; }
    public function setRole($Role){ $this->Role = $Role; }
    // =========================
    // INSERT USER
    // =========================

    public static function insert(PDO $db, $nom, $prenom, $user_name, $password_user, $role_id, $region_dp_id, $active = 1)
    {
        $query = "INSERT INTO utilisateur 
        (nom, prenom, user_name, password_user, active, role_id, region_dp_id)
        VALUES (:nom, :prenom, :user_name, :password_user, :active, :role_id, :region_dp_id)";

        $stmt = $db->prepare($query);

        $nom = htmlspecialchars(strip_tags($nom));
        $prenom = htmlspecialchars(strip_tags($prenom));
        $user_name = htmlspecialchars(strip_tags($user_name));
        $password_user = password_hash($password_user, PASSWORD_DEFAULT);

        $stmt->bindParam(':nom',$nom);
        $stmt->bindParam(':prenom',$prenom);
        $stmt->bindParam(':user_name',$user_name);
        $stmt->bindParam(':password_user',$password_user);
        $stmt->bindParam(':active',$active,PDO::PARAM_INT);
        $stmt->bindParam(':role_id',$role_id,PDO::PARAM_INT);
        $stmt->bindParam(':region_dp_id',$region_dp_id,PDO::PARAM_INT);

        if($stmt->execute()){
            $id = $db->lastInsertId();

            return new self(
                $id,
                $nom,
                $prenom,
                $user_name,
                $password_user, // already hashed
                $active,
                $role_id,
                $region_dp_id
            );
        }

        return false;
    }

    // =========================
    // GET USER BY ID
    // =========================

    public static function getById(PDO $db,$id)
    {
        $query = "SELECT * FROM utilisateur  WHERE id = :id LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id',$id,PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            return new self(
                $row['id'],
                $row['nom'],
                $row['prenom'],
                $row['user_name'],
                $row['password_user'],
                $row['active'],
                $row['role_id'],
                $row['region_dp_id']
            );
        }

        return null;
    }

    // =========================
    // GET ALL USERS
    // =========================

    public static function getAll(PDO $db)
    {
        $query = "SELECT * FROM utilisateur ORDER BY nom ASC, prenom ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $users = [];

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $users[] = new self(
                $row['id'],
                $row['nom'],
                $row['prenom'],
                $row['user_name'],
                $row['password_user'],
                $row['active'],
                $row['role_id'],
                $row['region_dp_id']
            );
        }

        return $users;
    }

    // =========================
    // COUNT ALL USERS
    // =========================

    public static function countAll(PDO $db)
    {
        $query = "SELECT COUNT(*) as total FROM utilisateur";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $row['total'];
    }

    // =========================
    // COUNT ACTIVE USERS
    // =========================

    public static function countActive(PDO $db)
    {
        $query = "SELECT COUNT(*) as total FROM utilisateur WHERE active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $row['total'];
    }

    // =========================
    // CHECK USERNAME
    // =========================

    public static function userNameExists(PDO $db,$user_name)
    {
        $query = "SELECT COUNT(*) as total FROM utilisateur WHERE user_name = :user_name";
        $stmt = $db->prepare($query);

        $user_name = trim($user_name);

        $stmt->bindParam(':user_name',$user_name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] > 0;
    }

    // =========================
    // TOGGLE STATUS (active)
    // =========================

    public static function toggleStatus(PDO $db, $id)
    {
        $query = "UPDATE utilisateur SET active = NOT active WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }


    

    // =========================
    // UPDATE USER (role, statut, optional password)
    // =========================

    public static function update(PDO $db, $id, $role_id, $active, $new_password = null)
    {
        if ($new_password !== null && $new_password !== '') {
            $query = "UPDATE utilisateur SET role_id = :role_id, active = :active, password_user = :password_user WHERE id = :id";
        } else {
            $query = "UPDATE utilisateur SET role_id = :role_id, active = :active WHERE id = :id";
        }
        $stmt = $db->prepare($query);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->bindParam(':active', $active, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($new_password !== null && $new_password !== '') {
            $stmt->bindParam(':password_user', $new_password);
        }
        return $stmt->execute();
    }

   public static function getByUsername(PDO $db, $user_name)
    {
        $query = "SELECT 
                    u.id AS user_id, 
                    u.nom, 
                    u.prenom, 
                    u.user_name, 
                    u.password_user, 
                    u.active, 
                    u.role_id, 
                    u.region_dp_id,
                    r.id AS role_id_fk,
                    r.code AS role_code,
                    r.label AS role_label
                  FROM utilisateur u
                  INNER JOIN role r ON u.role_id = r.id
                  WHERE u.user_name = :user_name 
                  LIMIT 1";
                  
        $stmt  = $db->prepare($query);
        $stmt->bindParam(':user_name', $user_name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // 1. On instancie l'objet Utilisateur (Attention: on utilise 'user_id' à cause du AS dans le SQL)
            $user = new self(
                $row['user_id'],
                $row['nom'],
                $row['prenom'],
                $row['user_name'],
                $row['password_user'],
                $row['active'],
                $row['role_id'],
                $row['region_dp_id']
            );

            // 2. On instancie l'objet Role avec les données de la jointure
            $roleObj = new Role(
                $row['role_id_fk'],
                $row['role_code'],
                $row['role_label']
            );

            // 3. On attache l'objet Role à l'utilisateur grâce à votre setter
            $user->setRole($roleObj);

            // 4. On retourne l'utilisateur complet
            return $user;
        }
        return null;
    }
    
        public static function getAllActive(PDO $db)
    {
        // On ne sélectionne que les utilisateurs où active = 1
        $query = "SELECT * FROM utilisateur WHERE active = 1 ORDER BY nom ASC, prenom ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $users =[];

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // On instancie un objet Utilisateur pour chaque ligne
            $users[] = new self(
                $row['id'],
                $row['nom'],
                $row['prenom'],
                $row['user_name'],
                $row['password_user'],
                $row['active'],
                $row['role_id'],
                $row['region_dp_id']
            );
        }

        return $users; // Retourne un tableau d'objets Utilisateur
    }

}


?>