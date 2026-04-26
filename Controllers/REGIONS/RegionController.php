<?php
// On démarre la session pour pouvoir stocker les messages de succès/erreur
session_start();

// Attention aux chemins : vu qu'on est dans le dossier 'controllers', 
// il faut remonter d'un cran ('../') pour atteindre 'config' et 'models'
require_once '../../classes/Database.php';
require_once '../../classes/region.php';

// Vérification : est-ce que le formulaire a bien été soumis ?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_region') {
    
    // 1. Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();

    // 2. Nettoyage des données
    $code = strtoupper(trim($_POST['code']));
    $label = trim($_POST['label']);

    // 3. Validation et Traitement
    if (!empty($code) && !empty($label)) {
        try {
            // Appel au Modèle pour insérer en BDD
            $nouvelleRegion = Region::insert($db, $code, $label);
            
            if ($nouvelleRegion) {
                // Succès : on prépare un message vert
                $_SESSION['flash_message'] = "La région a été ajoutée avec succès !";
                $_SESSION['flash_type'] = "success";
            } else {
                // Erreur d'insertion
                $_SESSION['flash_message'] = "Erreur lors de l'ajout de la région.";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (PDOException $e) {
            // Erreur SQL (ex: le code ou label existe déjà)
            $_SESSION['flash_message'] = "Erreur : Ce code ou ce label existe déjà dans la base de données.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // Erreur de saisie
        $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
        $_SESSION['flash_type'] = "warning";
    }

    // 4. Redirection vers la page d'affichage (Pattern Post/Redirect/Get)
    // Cela évite le bug du "Voulez-vous renvoyer le formulaire ?" si l'utilisateur actualise la page
    header("Location: ../../pages/regions/Liste_regions.php");
    exit();
} else {
    // Si quelqu'un accède à ce fichier directement par l'URL sans POST, on le renvoie
    header("Location: ../../pages/regions/Liste_regions.php");
    exit();
}
?>