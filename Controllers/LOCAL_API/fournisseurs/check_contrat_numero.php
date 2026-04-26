<?php
// On indique que la réponse sera du JSON
header('Content-Type: application/json');

require_once '../../classes/Database.php';
require_once '../../classes/Contrat.php';

// On vérifie qu'un numéro a bien été envoyé en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num_contrat'])) {

    $numero = trim($_POST['num_contrat']);

    // Connexion BDD
    $database = new Database();
    $db = $database->getConnection();

    // Appel au modèle pour vérifier
    $exists = Contrat::numeroExists($db, $numero);

    // On renvoie le résultat en JSON (ex: {"exists": true})
    echo json_encode(['exists' => $exists]);
    exit();
}

// Sécurité : si on accède à ce fichier sans POST valide
echo json_encode(['error' => 'Requête invalide']);
?>
