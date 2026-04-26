<?php
session_start();

require_once '../../classes/Database.php';
require_once '../../classes/Contrat.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $database = new Database();
    $db = $database->getConnection();

    // ════════════════════════════════════════════════
    // ACTION : AJOUT D'UN CONTRAT
    // ════════════════════════════════════════════════
    if ($_POST['action'] === 'add_contrat') {

        $fournisseur_id = isset($_POST['fournisseur_id']) ? intval($_POST['fournisseur_id']) : 0;
        $num_contrat    = isset($_POST['num_contrat'])    ? strtoupper(trim($_POST['num_contrat'])) : '';
        $utilisateur_id = isset($_POST['utilisateur_id']) ? intval($_POST['utilisateur_id']) : 0;

        if (!empty($num_contrat) && $fournisseur_id > 0 && $utilisateur_id > 0) {

            try {
                $db->beginTransaction();

                $nouveauContrat = Contrat::insert($db, $fournisseur_id, $num_contrat);

                if ($nouveauContrat) {
                  // Récupération directe, plus rapide et sans risque
                   $contrat_id = $nouveauContrat->getId();

                    $queryAffectation = "INSERT INTO affectation (Contratid, utilisateurid) VALUES (:contrat_id, :utilisateur_id)";
                    $stmtAffectation  = $db->prepare($queryAffectation);
                    $stmtAffectation->bindParam(':contrat_id',    $contrat_id,    PDO::PARAM_INT);
                    $stmtAffectation->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
                    $stmtAffectation->execute();

                    $db->commit();

                    $_SESSION['flash_message'] = "Le contrat et son affectation ont été enregistrés avec succès !";
                    $_SESSION['flash_type']    = "success";

                } else {
                    $db->rollBack();
                    $_SESSION['flash_message'] = "Erreur lors de la création du contrat.";
                    $_SESSION['flash_type']    = "danger";
                }

            } catch (PDOException $e) {
                $db->rollBack();
                if ($e->getCode() == 23000) {
                    $_SESSION['flash_message'] = "Erreur : Ce numéro de contrat existe déjà.";
                } else {
                    $_SESSION['flash_message'] = "Erreur système lors de l'enregistrement.";
                    error_log("Erreur ajout contrat: " . $e->getMessage());
                }
                $_SESSION['flash_type'] = "danger";
            }

        } else {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires (Fournisseur, Numéro et Utilisateur).";
            $_SESSION['flash_type']    = "warning";
        }

        $_SESSION['fournisseur_id'] = $fournisseur_id;
        header("Location: ../../Pages/Fournisseur/listeContrats.php");
        exit();


    // ════════════════════════════════════════════════
    // ACTION : MODIFICATION D'UN CONTRAT
    // ════════════════════════════════════════════════
    } elseif ($_POST['action'] === 'update_contrat') {

        $contrat_id      = isset($_POST['contrat_id'])       ? intval($_POST['contrat_id'])           : 0;
        $fournisseur_id  = isset($_POST['fournisseur_id'])   ? intval($_POST['fournisseur_id'])        : 0;
        $num_contrat     = isset($_POST['num_contrat'])      ? strtoupper(trim($_POST['num_contrat'])) : '';
        $nom_fournisseur = isset($_POST['nom_fournisseur'])  ? trim($_POST['nom_fournisseur'])         : '';
        $pays_code       = isset($_POST['pays_fournisseur']) ? trim($_POST['pays_fournisseur'])        : '';
        $utilisateur_id  = isset($_POST['utilisateur_id'])   ? intval($_POST['utilisateur_id'])        : 0;

        if ($contrat_id <= 0 || empty($num_contrat) || $utilisateur_id <= 0 || $fournisseur_id <= 0) {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['flash_type']    = "warning";
            $_SESSION['fournisseur_id'] = $fournisseur_id;
            header("Location: ../../Pages/Fournisseur/listeContrats.php");
            exit();
        }

        try {
            $db->beginTransaction();

            // Mettre à jour le numéro du contrat
            $queryContrat = "UPDATE contrat SET num_contrat = :num_contrat WHERE id = :contrat_id";
            $stmtContrat  = $db->prepare($queryContrat);
            $stmtContrat->bindParam(':num_contrat', $num_contrat);
            $stmtContrat->bindParam(':contrat_id',  $contrat_id, PDO::PARAM_INT);
            $stmtContrat->execute();

            // Mettre à jour le fournisseur (nom + pays)
            if (!empty($nom_fournisseur)) {
                $queryFourn = "UPDATE fournisseur SET Nom_Fournisseur = :nom_fournisseur, paye_id = :pays_code WHERE id = :fournisseur_id";
                $stmtFourn  = $db->prepare($queryFourn);
                $stmtFourn->bindParam(':nom_fournisseur', $nom_fournisseur);
                $stmtFourn->bindParam(':pays_code',       $pays_code);
                $stmtFourn->bindParam(':fournisseur_id',  $fournisseur_id, PDO::PARAM_INT);
                $stmtFourn->execute();
            }

            // Mettre à jour ou créer l'affectation
            $queryCheckAff = "SELECT id FROM affectation WHERE Contratid = :contrat_id LIMIT 1";
            $stmtCheckAff  = $db->prepare($queryCheckAff);
            $stmtCheckAff->bindParam(':contrat_id', $contrat_id, PDO::PARAM_INT);
            $stmtCheckAff->execute();
            $affExistante = $stmtCheckAff->fetch(PDO::FETCH_ASSOC);

            if ($affExistante) {
                $queryAff = "UPDATE affectation SET utilisateurid = :utilisateur_id WHERE Contratid = :contrat_id";
              
            } else {
                $queryAff = "INSERT INTO affectation (utilisateurid, Contratid) VALUES (:utilisateur_id, :contrat_id)";
            }

            $stmtAff = $db->prepare($queryAff);
            $stmtAff->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
            $stmtAff->bindParam(':contrat_id',     $contrat_id,     PDO::PARAM_INT);
            $stmtAff->execute();

            $db->commit();

            $_SESSION['flash_message'] = "Le contrat a été mis à jour avec succès !";
            $_SESSION['flash_type']    = "success";

        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == 23000) {
                $_SESSION['flash_message'] = "Erreur : Ce numéro de contrat est déjà utilisé par un autre contrat.";
            } else {
                $_SESSION['flash_message'] = "Erreur système lors de la mise à jour.";
                error_log("Erreur update_contrat : " . $e->getMessage());
            }
            $_SESSION['flash_type'] = "danger";
        }

        $_SESSION['fournisseur_id'] = $fournisseur_id;
        header("Location: ../../Pages/Fournisseur/listeContrats.php");
        exit();

    } else {
        header("Location: ../../Pages/Fournisseur/listeFournisseurs.php");
        exit();
    }

} else {
    header("Location: ../../Pages/Fournisseur/listeFournisseurs.php");
    exit();
}
?>