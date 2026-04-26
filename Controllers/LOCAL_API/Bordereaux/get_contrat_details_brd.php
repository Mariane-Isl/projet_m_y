<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['contrat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Requête non autorisée (méthode ou paramètre manquant).']);
    exit();
}

$contrat_id = intval($_POST['contrat_id']);

if ($contrat_id <= 0) {
    echo json_encode(['success' => false, 'message' => "ID contrat invalide ou nul (reçu: '{$_POST['contrat_id']}').'"]);
    exit();
}

try {
    $database = new Database();
    $db       = $database->getConnection();

    // Vérifier d'abord que le contrat existe
    $stmtCheck = $db->prepare("SELECT id, num_Contrat, Fournisseur_id FROM contrat WHERE id = :id LIMIT 1");
    $stmtCheck->bindParam(':id', $contrat_id, PDO::PARAM_INT);
    $stmtCheck->execute();
    $contratExiste = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$contratExiste) {
        echo json_encode([
            'success' => false,
            'message' => "Aucun contrat trouvé avec l'ID $contrat_id dans la table 'contrat'."
        ]);
        exit();
    }

    // Requête complète avec jointures
    $query = "SELECT
                c.id              AS contrat_id,
                c.num_Contrat     AS num_contrat,
                f.Nom_Fournisseur AS nom_fournisseur,
                f.code            AS fournisseur_code,
                p.label           AS pays_label,
                u.nom             AS gestionnaire_nom,
                u.prenom          AS gestionnaire_prenom
              FROM contrat c
              LEFT JOIN fournisseur f ON f.id = c.Fournisseur_id
              LEFT JOIN paye        p ON p.id = f.paye_id
              LEFT JOIN affectation a ON a.Contratid = c.id
              LEFT JOIN utilisateur u ON u.id = a.utilisateurid
              WHERE c.id = :contrat_id
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $row['gestionnaire'] = trim(($row['gestionnaire_nom'] ?? '') . ' ' . ($row['gestionnaire_prenom'] ?? ''));
        if (empty(trim($row['gestionnaire']))) {
            $row['gestionnaire'] = 'Non affecté';
        }
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        // Contrat existe mais jointure a échoué - retourner données minimales
        echo json_encode([
            'success' => true,
            'data' => [
                'contrat_id'       => $contrat_id,
                'num_contrat'      => $contratExiste['num_Contrat'],
                'nom_fournisseur'  => 'Fournisseur ID: ' . $contratExiste['Fournisseur_id'],
                'fournisseur_code' => '—',
                'pays_label'       => '—',
                'gestionnaire'     => 'Non affecté'
            ]
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}