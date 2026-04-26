<?php

class NatureOv
{
    // Database connection and table name
    private $conn;
    private $table_name = "nature_ov";

    // Object properties corresponding to table columns
    public $id;
    public $code;
    public $label;

    // Constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * CREATE: Insert a new record
     * @return bool
     */
    public function create()
    {
        try {
            $query = "INSERT INTO " . $this->table_name . " SET code=:code, label=:label";
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->code = htmlspecialchars(strip_tags($this->code));
            $this->label = htmlspecialchars(strip_tags($this->label));

            // Bind values
            $stmt->bindParam(":code", $this->code);
            $stmt->bindParam(":label", $this->label);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Check for duplicate entry error (code is UNIQUE)
            if ($e->getCode() == 23000) {
                error_log("Error: The code '{$this->code}' already exists.");
            } else {
                error_log("Create Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * READ ALL: Fetch all records
     * @return PDOStatement
     */
    public function readAll()
    {
        $query = "SELECT id, code, label FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    /**
     * READ ONE: Fetch a single record by ID
     * @return bool
     */
    public function readOne()
    {
        $query = "SELECT id, code, label FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->code = $row['code'];
            $this->label = $row['label'];
            return true;
        }
        return false;
    }

    /**
     * UPDATE: Update an existing record
     * @return bool
     */
    public function update()
    {
        try {
            $query = "UPDATE " . $this->table_name . " SET code=:code, label=:label WHERE id=:id";
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->code = htmlspecialchars(strip_tags($this->code));
            $this->label = htmlspecialchars(strip_tags($this->label));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind values
            $stmt->bindParam(":code", $this->code);
            $stmt->bindParam(":label", $this->label);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE: Delete a record
     * @return bool
     */
    public function delete()
    {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind id
            $stmt->bindParam(1, $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère uniquement le code (ex: 'EXP') à partir de l'ID
     */
    public static function getCodeById(PDO $db, $id)
    {
        $stmt = $db->prepare("SELECT code FROM nature_ov WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetchColumn(); // Retourne le code ou false
    }

}
