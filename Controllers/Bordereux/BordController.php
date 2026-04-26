<?php
session_start();

require_once '../../classes/Database.php';
require_once '../../classes/Bordereau.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) 
{

    $database = new Database();
    $db       = $database->getConnection();

    // ════════════════════════════════════════════════
    // ACTION : ACCUSÉ DE RÉCEPTION (statut NON_CONTROLE)
    // ════════════════════════════════════════════════
    if ($_POST['action'] === 'accuser_bordereau') 
    {

        $bordereau_id = isset($_POST['bordereau_id']) ? intval($_POST['bordereau_id'])  : 0;


        error_log("POST data received for accuser_bordereau: " . print_r($_POST, true));
        $date_accuse  = isset($_POST['date_accuse'])  ? trim($_POST['date_accuse'])      : '';

        if ($bordereau_id > 0 && !empty($date_accuse)) {

            // Vérifier que le bordereau existe
            $brd = Bordereau::getByIdWithDetails($db, $bordereau_id);

            if (!$brd) {
                $_SESSION['flash_message'] = "Bordereau introuvable.";
                $_SESSION['flash_type']    = "danger";
                header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
                exit();
            }

            // Vérifier qu'il n'est pas déjà accusé (code NON_CONTROLE)
            if (Bordereau::hasStatut($db, $bordereau_id, 'ARRIVE')) {
                $_SESSION['flash_message'] = "Ce bordereau a déjà été accusé de réception.";
                $_SESSION['flash_type']    = "warning";
                header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
                exit();
            }

            try {
                $db->beginTransaction();

                // Récupérer l'ID du statut 'NON_CONTROLE' = "Accuser Reception"
                $statut_id = Bordereau::getStatutIdByCode($db, 'ARRIVE');

                if (!$statut_id) {
                    $stmtCodes = $db->query("SELECT code FROM statut_borderau ORDER BY id");
                    $codes = implode(', ', array_column($stmtCodes->fetchAll(PDO::FETCH_ASSOC), 'code'));
                    throw new Exception("Statut 'NON_CONTROLE' introuvable. Codes disponibles : $codes");
                }

                $result = Bordereau::insererHistorique($db, $bordereau_id, $statut_id, $date_accuse);

                if ($result) {
                    $db->commit();
                    $_SESSION['flash_message'] = "Accusé de réception enregistré avec succès !";
                    $_SESSION['flash_type']    = "success";
                    $_SESSION['propose_reception'] = $bordereau_id; // proposer de réceptionner
                } else {
                    $db->rollBack();
                    $_SESSION['flash_message'] = "Erreur lors de l'enregistrement.";
                    $_SESSION['flash_type']    = "danger";
                }
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['flash_message'] = "Erreur système : " . $e->getMessage();
                $_SESSION['flash_type']    = "danger";
                error_log("Erreur accuser_bordereau : " . $e->getMessage());
            }
        } else {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['flash_type']    = "warning";
        }

        header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
        exit();


        // ════════════════════════════════════════════════
        // ACTION : RÉCEPTION BORDEREAU (statut RECEPTION)
        // ════════════════════════════════════════════════
    } elseif ($_POST['action'] === 'receptionner_bordereau') {

        $bordereau_id   = isset($_POST['bordereau_id'])   ? intval($_POST['bordereau_id']) : 0;
        $date_reception = isset($_POST['date_reception']) ? trim($_POST['date_reception'])  : '';

        if ($bordereau_id > 0 && !empty($date_reception)) {

            // Règle métier : doit être accusé d'abord (NON_CONTROLE)
            if (!Bordereau::hasStatut($db, $bordereau_id, 'NON_CONTROLE')) {
                $_SESSION['flash_message'] = "Vous devez d'abord effectuer l'Accusé de Réception avant de réceptionner ce bordereau.";
                $_SESSION['flash_type']    = "danger";
                header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
                exit();
            }

            // Vérifier qu'il n'est pas déjà réceptionné
            if (Bordereau::hasStatut($db, $bordereau_id, 'RECEPTION')) {
                $_SESSION['flash_message'] = "Ce bordereau a déjà été réceptionné.";
                $_SESSION['flash_type']    = "warning";
                header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
                exit();
            }

            try {
                $db->beginTransaction();

                // Récupérer l'ID du statut 'RECEPTION' = "Réceptionné"
                $statut_id = Bordereau::getStatutIdByCode($db, 'RECEPTION');

                if (!$statut_id) {
                    $stmtCodes = $db->query("SELECT code FROM statut_borderau ORDER BY id");
                    $codes = implode(', ', array_column($stmtCodes->fetchAll(PDO::FETCH_ASSOC), 'code'));
                    throw new Exception("Statut 'RECEPTION' introuvable. Codes disponibles : $codes");
                }

                $result = Bordereau::insererHistorique($db, $bordereau_id, $statut_id, $date_reception);

                if ($result) {
                    $db->commit();
                    $_SESSION['flash_message'] = "Bordereau réceptionné avec succès !";
                    $_SESSION['flash_type']    = "success";
                } else {
                    $db->rollBack();
                    $_SESSION['flash_message'] = "Erreur lors de la réception du bordereau.";
                    $_SESSION['flash_type']    = "danger";
                }
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['flash_message'] = "Erreur système : " . $e->getMessage();
                $_SESSION['flash_type']    = "danger";
                error_log("Erreur receptionner_bordereau : " . $e->getMessage());
            }
        } else {
            $_SESSION['flash_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['flash_type']    = "warning";
        }

        header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
        exit();
    } else {
        header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
        exit();
    }
} else {
    header("Location: ../../Pages/Bordereaux/Reception_bordereaux.php");
    exit();
}
