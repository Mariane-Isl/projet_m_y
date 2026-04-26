<?php
// On force le format JSON (Empêche l'erreur Unexpected token '<')
header('Content-Type: application/json; charset=utf-8');

// Inclusion des fichiers (vérifiez bien vos chemins selon l'arborescence de votre projet)
require_once '../../../classes/Database.php';
require_once '../../../classes/Contrat.php';

// Si la requête est bien un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des données envoyées par le formulaire de la modale
    $id = isset($_POST['contrat_id']) ? intval($_POST['contrat_id']) : 0;
    $num_contrat = isset($_POST['num_contrat']) ? trim($_POST['num_contrat']) : '';
    // (Ajoutez ici la récupération de fournisseur et utilisateur si vous gérez aussi leur modification)

    if ($id > 0 && !empty($num_contrat)) {
        
        try {
            $database = new Database();
            $db = $database->getConnection();

            // REQUÊTE DE MISE À JOUR
            $query = "UPDATE contrat SET num_contrat = :num_contrat WHERE id = :id";
            $stmt = $db->prepare($query);
            
            // Sécurisation
            $num_contrat = strtoupper(htmlspecialchars(strip_tags($num_contrat)));
            $stmt->bindParam(':num_contrat', $num_contrat);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // SUCCÈS : On renvoie l'information en JSON
                echo json_encode(['success' => true, 'message' => 'Le contrat a été mis à jour.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
            }

        } catch (PDOException $e) {
            // ERREUR SQL (Violation de contrainte unique = code 23000)
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Erreur : Ce numéro de contrat est déjà utilisé ailleurs.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Données invalides ou incomplètes.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>