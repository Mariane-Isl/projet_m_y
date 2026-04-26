<?php
session_start();
require_once __DIR__ . '/../../../Classes/Database.php';
require_once __DIR__ . '/../../../Classes/historique_borderau.php';

error_log("[Accuse] Début du traitement.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bordereau_id'])) {
    $bordereau_id = (int) $_POST['bordereau_id'];
    error_log("[Accuse] ID reçu : " . $bordereau_id);

    try {
        $db = (new Database())->getConnection();
        $emetteur_id = (int)$_SESSION['user_id'];
        // Récupération du statut
        $statut = historique_borderau::getStatusByCode($db, 'ARRIVE');
       

        // Tentative d'insertion
        if (historique_borderau::insertHistorique($db, $bordereau_id, $statut['id'], $_SESSION['user_id'])) {
            $_SESSION['message'] = "L'accusé de réception a été enregistré avec succès.";
            $_SESSION['message_type'] = "success"; // 'success' pour SweetAlert
            error_log("[Accuse] Succès pour le bordereau : " . $bordereau_id);
        } else {
            throw new Exception("L'insertion dans l'historique a échoué.");
        }

    } catch (Exception $e) {
        error_log("[Accuse] ERREUR : " . $e->getMessage());
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
        $_SESSION['message_type'] = "error"; // 'error' pour SweetAlert
    }
} else {
    error_log("[Accuse] Accès invalide ou données manquantes.");
    $_SESSION['message'] = "Requête invalide.";
    $_SESSION['message_type'] = "error";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../../index.php'));
exit;