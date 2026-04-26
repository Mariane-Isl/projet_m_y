<?php
class Monnaie {
    private $pdo;
    public function __construct($db) { $this->pdo = $db; }

    public function getAll() {
        return $this->pdo->query("SELECT * FROM money ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($code, $label) {
        $stmt = $this->pdo->prepare("INSERT INTO money (code, label) VALUES (?, ?)");
        return $stmt->execute([strtoupper($code), $label]);
    }

   
    public function update($id, $code, $label) {
        $stmt = $this->pdo->prepare("UPDATE money SET code = ?, label = ? WHERE id = ?");
        return $stmt->execute([strtoupper($code), $label, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM money WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function codeExists($code) {
    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM money WHERE code = ?");
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetchColumn() > 0;
    }

    // Vérifier si le code existe pour une AUTRE monnaie (Utile pour l'Edit)
    public function codeExistsForUpdate($code, $id) {
    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM money WHERE code = ? AND id != ?");
    $stmt->execute([strtoupper(trim($code)), $id]);
    return $stmt->fetchColumn() > 0;
    }

     public function  getByCode($code) {
        $stmt = $this->pdo->prepare("SELECT * FROM money WHERE code = ?");
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetchColumn();
    }

     public function getCodeById($id) {
        $stmt = $this->pdo->prepare("SELECT code FROM money WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }
}