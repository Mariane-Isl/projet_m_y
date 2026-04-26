<?php
/**
 * CONTRÔLEUR : Fournisseur
 * =========================
 * Gère les actions POST liées aux fournisseurs :
 *   - add_fournisseur    → insertion d'un nouveau fournisseur
 *   - update_fournisseur → mise à jour (✅ CORRIGÉ : SQL déplacé dans Fournisseur::update())
 *
 * Pattern : Post → Redirect → Get (PRG)
 */
session_start();

require_once '../../classes/Database.php';
require_once '../../classes/Fournisseur.php';

// Vérification de la méthode HTTP et de l'action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Connexion à la base de données
    $database = new Database();
    $db       = $database->getConnection();

    // ════════════════════════════════════════════════
    // ACTION : AJOUTER UN FOURNISSEUR
    // ════════════════════════════════════════════════
    if ($_POST['action'] === 'add_fournisseur') {

        // Nettoyage des données POST
        $code            = strtoupper(trim($_POST['code'] ?? ''));
        $nom_fournisseur = trim($_POST['nom_fournisseur'] ?? '');
        $paye_id         = intval($_POST['paye_id'] ?? 0);

        // Validation
        if (!empty($code) && !empty($nom_fournisseur) && $paye_id > 0) {
            try {
                // Appel au Modèle — aucun SQL dans ce contrôleur
                $nouveauFournisseur = Fournisseur::insert($db, $code, $nom_fournisseur, $paye_id);

                if ($nouveauFournisseur) {
                    $_SESSION['flash_message'] = "Le fournisseur a été ajouté avec succès !";
                    $_SESSION['flash_type']    = "success";
                } else {
                    $_SESSION['flash_message'] = "Erreur lors de l'ajout du fournisseur.";
                    $_SESSION['flash_type']    = "danger";
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erreur : Ce code fournisseur existe déjà dans la base de données.";
                $_SESSION['flash_type']    = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['flash_type']    = "warning";
        }

        header("Location: ../../Pages/Fournisseur/listeFournisseur.php");
        exit();

    // ════════════════════════════════════════════════
    // ACTION : METTRE À JOUR UN FOURNISSEUR
    // ✅ CORRIGÉ : le SQL est désormais dans Fournisseur::update()
    // ════════════════════════════════════════════════
    } elseif ($_POST['action'] === 'update_fournisseur') {

        // ✅ [CORRECTION MVC] Récupération et validation des données POST
        $fournisseur_id  = isset($_POST['fournisseur_id']) ? intval($_POST['fournisseur_id']) : 0;
        $code            = trim($_POST['code'] ?? '');
        $nom_fournisseur = trim($_POST['nom_fournisseur'] ?? '');
        $paye_id         = intval($_POST['paye_id'] ?? 0);

        if ($fournisseur_id > 0 && !empty($code) && !empty($nom_fournisseur) && $paye_id > 0) {

            // ✅ [CORRECTION MVC] Tout le SQL est désormais dans le Modèle Fournisseur::update()
            $resultat = Fournisseur::update($db, $fournisseur_id, $code, $nom_fournisseur, $paye_id);

            if ($resultat === true) {
                $_SESSION['flash_message'] = "Le fournisseur a été mis à jour avec succès !";
                $_SESSION['flash_type']    = "success";
            } elseif ($resultat === 'duplicate') {
                $_SESSION['flash_message'] = "Erreur : Ce code fournisseur est déjà utilisé par un autre fournisseur.";
                $_SESSION['flash_type']    = "danger";
            } else {
                $_SESSION['flash_message'] = "Erreur système lors de la mise à jour.";
                $_SESSION['flash_type']    = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['flash_type']    = "warning";
        }

        header("Location: ../../Pages/Fournisseur/listeFournisseur.php");
        exit();

    } else {
        // Action inconnue → redirection sécurisée
        header("Location: ../../Pages/Fournisseur/listeFournisseur.php");
        exit();
    }

} else {
    // Accès direct sans POST → redirection
    header("Location: ../../Pages/Fournisseur/listeFournisseur.php");
    exit();
}
?>
