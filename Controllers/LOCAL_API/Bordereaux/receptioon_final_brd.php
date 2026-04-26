<?php
session_start();
require_once __DIR__ . '/../../../Classes/Database.php';
require_once __DIR__ . '/../../../Classes/historique_borderau.php';
require_once __DIR__ . '/../../../Classes/historique_facture.php'; // ← ajouter

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bordereau_id'])) {
    $bordereau_id = (int) $_POST['bordereau_id'];
    $emetteur_id = (int)$_SESSION['user_id'];
    try {
        $db = (new Database())->getConnection();

        // ── 1. Enregistrer RECEPTION du bordereau ──────────────────
        $statut = historique_borderau::getStatusByCode($db, 'RECEPTION');
        historique_borderau::insertHistorique($db, $bordereau_id, $statut['id'],$emetteur_id);

        // ── 2. Enregistrer INTROUVABLE pour les factures non cliquées ──
        $facturesIntrouvables = $_POST['factures_introuvables'] ?? [];

        if (!empty($facturesIntrouvables)) {

            foreach ($facturesIntrouvables as $facture_id) {
                $facture_id = (int) $facture_id;
                if ($facture_id > 0) {
                    // updateStatusByCode gère l'INSERT proprement
                    historique_facture::updateStatusByCode($db, $facture_id, 'INTROUVABLE');
                }
            }
        }

        $nbIntrouvables = count($facturesIntrouvables);
        $_SESSION['message']      = "Bordereau réceptionné. $nbIntrouvables facture(s) marquée(s) comme Introuvable.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['message']      = "Erreur : " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message']      = "Requête invalide.";
    $_SESSION['message_type'] = "error";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../../index.php'));
exit;
