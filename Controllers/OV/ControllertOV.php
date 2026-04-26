<?php
session_start();
header('Content-Type: application/json');

// Inclusions de vos classes
require_once '../../Classes/Database.php';
require_once '../../Classes/NatureOv.php';
require_once '../../Classes/Monnaie.php';
require_once '../../Classes/OV.php';
require_once '../../Classes/HistoriqueStatusOV.php';
require_once '../../Classes/historique_facture.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Vérification de la session utilisateur
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
        exit;
    }
    $user_id = (int) $_SESSION['user_id'];

    // 2. Récupération des données POST
    $header_info = isset($_POST['header_info']) ? json_decode($_POST['header_info'], true) : null;
    $factures_list = isset($_POST['factures_list']) ? json_decode($_POST['factures_list'], true) : null;
    $num_ktp = isset($_POST['num_ktp']) ? trim($_POST['num_ktp']) : '';

    if (!$header_info || empty($factures_list) || empty($num_ktp)) {
        echo json_encode(['success' => false, 'message' => 'Données incomplètes (KTP ou Factures manquantes).']);
        exit;
    }

    $db = (new Database())->getConnection();

    // ==============================================================
    // 🚨 3. VÉRIFICATION DU NUMÉRO KTP 🚨
    // ==============================================================
    /* 
     * REMPLACEZ la requête ci-dessous par la table où sont stockés vos KTP.
     * Exemple : on vérifie si le KTP existe dans une table `users` ou `codes_ktp`.
     */
    if (!is_numeric($num_ktp)) {
        echo json_encode(['success' => false, 'message' => 'Erreur : Le numéro KTP doit être uniquement composé de chiffres.']);
        exit;
    }

    // Vérification de l'unicité (pour ne pas crasher sur l'index UNIQUE 'Num_KTP')
    $stmtKtp = $db->prepare("SELECT id FROM ordres_virement WHERE Num_KTP = :ktp LIMIT 1");
    $stmtKtp->execute([':ktp' => $num_ktp]);
    $ktp_deja_pris = $stmtKtp->fetchColumn();

    if ($ktp_deja_pris) {
        echo json_encode(['success' => false, 'message' => 'Erreur : Ce numéro KTP a déjà été utilisé pour un autre Ordre de Virement.']);
        exit;
    }
    // ==============================================================


    try {
        // 4. DÉBUT DE LA TRANSACTION
        $db->beginTransaction();

        // 5. RÉCUPÉRATION DES CODES ET IDS (Devise & Nature)
        $year = date('Y');

        // A. Pour la Devise (Monnaie)
        $money_code = $header_info['devise_id']; // Contient "DZD" envoyé par le formulaire
        $monnaieObj = new Monnaie($db);
        $money_id = $monnaieObj->getByCode($money_code); // Utilise VOTRE fonction pour récupérer l'ID (ex: 1)

        if (!$money_id) throw new Exception("La devise '$money_code' est introuvable.");

        // B. Pour la Nature OV
        $nature_code = $header_info['nature_ov_id']; // Contient "EXPL" envoyé par le formulaire
        // Requête directe pour trouver l'ID à partir du code (au cas où vous n'avez pas de fonction getIdByCode)
        $stmtNat = $db->prepare("SELECT id FROM nature_ov WHERE code = :code LIMIT 1");
        $stmtNat->execute([':code' => $nature_code]);
        $nature_id = $stmtNat->fetchColumn();

        if (!$nature_id) throw new Exception("La nature '$nature_code' est introuvable.");

        // 6. GÉNÉRATION DU NUMÉRO OV
        $ovObj = new OV($db);

        $current_count = $ovObj->getCountByUserAndNature($user_id, $nature_id);
        $sequence = str_pad($current_count + 1, 4, '0', STR_PAD_LEFT);

        // On utilise les CODES pour le format visuel (ex: 2024/EXPL/DZD/0001)
        $num_ov = "{$year}/{$nature_code}/{$money_code}/{$sequence}";

        // 7. INSERTION DE L'OV DANS LA BASE
        // On insère avec les IDS trouvés (nature_id et money_id)
        $ov_id = $ovObj->insert(
            $num_ov,
            $num_ktp,
            $nature_id,
            $header_info['structure_id'],
            $header_info['contrat_id'],
            $money_id
        );

        if (!$ov_id) {
            throw new Exception("Erreur lors de la création de l'Ordre de Virement dans la base de données.");
        }

        $historyOV = new HistoriqueStatusOV($db);

        // On récupère dynamiquement l'ID du statut "Traitement en cours" (code: TRAIT)
        $status_id = HistoriqueStatusOV::getStatusIdByCode($db, 'TRAIT');
        try {
            HistoriqueStatusOV::updateOVStatusByCode($db, $ov_id, 'TRAIT', $user_id);
        } catch (Exception $e) {
            throw new Exception("Erreur Historique OV : " . $e->getMessage());
        }

        if (!$status_id) {
            throw new Exception("Erreur : Le statut 'TRAIT' n'existe pas dans la base de données.");
        }

        // On crée l'historique avec l'ID trouvé
        $historyOV->create($ov_id, $status_id);

        // 9. LIAISON DES FACTURES ET MISE À JOUR DE LEUR STATUT
        $linkQuery = "INSERT INTO facture_ordres_virement (Factureid, ordres_virementid) VALUES (:fid, :ovid)";
        $linkStmt = $db->prepare($linkQuery);

        foreach ($factures_list as $facture) {
            $facture_id = $facture['idFacture'];

            // A. Insérer dans la table de liaison
            $linkStmt->execute([
                ':fid'  => $facture_id,
                ':ovid' => $ov_id
            ]);

            // B. Mettre à jour le statut de la facture à "PAYE"
            historique_facture::updateStatusByCode($db, $facture_id, 'EN COURS');
        }

        // 10. TOUT S'EST BIEN PASSÉ -> ON VALIDE LA TRANSACTION
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "L'Ordre de Virement $num_ov a été généré avec succès !",
            'ov_id'   => $ov_id
        ]);
    } catch (Exception $e) {
        $db->rollBack(); // En cas d'erreur, on annule tout
        echo json_encode([
            'success' => false,
            'message' => 'Erreur Serveur: ' . $e->getMessage()
        ]);
    }
}
