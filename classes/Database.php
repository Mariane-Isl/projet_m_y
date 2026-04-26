<?php

class Database
{
    // Paramètres de connexion
    private $host = "localhost";
    private $db_name = "suiviefactures";
    private $username = "root";
    private $password = ""; // Pas de mot de passe selon tes instructions
    public $conn;

    // Méthode pour obtenir la connexion à la base de données
    public function getConnection()
    {
        $this->conn = null;

        try {
            // Création de l'instance PDO
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Configuration des options PDO (très important pour un code propre)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Affiche les erreurs SQL
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Retourne les résultats sous forme de tableaux associatifs
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Sécurité supplémentaire contre les injections

        } catch (PDOException $exception) {
            // En cas d'erreur, on arrête tout et on affiche le message
            die("Erreur de connexion à la base de données : " . $exception->getMessage());
        }

        return $this->conn;
    }
}
