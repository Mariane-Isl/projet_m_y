<?php
// On force le format JSON
header('Content-Type: application/json; charset=utf-8');

// Inclusion de la connexion BDD
require_once '../../../classes/Database.php';

// On vérifie qu'on a bien reçu l'ID du contrat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contrat_id'])) {
    
    $contrat_id = intval($_POST['contrat_id']);

    if ($contrat_id > 0) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Requête pour récupérer les infos du contrat + fournisseur + gestionnaire
            $query = "SELECT 
                        c.id AS contrat_id, 
                        c.num_contrat, 
                        f.Nom_Fournisseur AS nom_fournisseur, 
                        f.paye_id AS pays_code,
                        a.utilisateurid AS utilisateur_id
                      FROM contrat c
                      LEFT JOIN fournisseur f ON c.fournisseur_id = f.id
                      LEFT JOIN affectation a ON c.id = a.Contratid
                      WHERE c.id = :contrat_id 
                      LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Si on trouve le contrat, on renvoie les données (success = true)
                echo json_encode([
                    'success' => true, 
                    'data' => $row
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contrat introuvable dans la base de données.']);
            }

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête non autorisée.']);
}
?>