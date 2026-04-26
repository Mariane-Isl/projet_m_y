<?php
// On indique que la réponse sera du JSON
header('Content-Type: application/json');

// On remonte de deux dossiers (local_api -> controllers -> racine) pour trouver classes
require_once '../../classes/Database.php';
require_once '../../classes/Fournisseur.php';

// On vérifie qu'un code a bien été envoyé en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {

    $code = trim($_POST['code']);

    // Connexion BDD
    $database = new Database();
    $db = $database->getConnection();

    // Appel au modèle pour vérifier
    $exists = Fournisseur::codeExists($db, $code);

    // On renvoie le résultat en JSON (ex: {"exists": true})
    echo json_encode(['exists' => $exists]);
    exit();
}

// Sécurité : si on accède à ce fichier sans POST valide
echo json_encode(['error' => 'Requête invalide']);
?>
