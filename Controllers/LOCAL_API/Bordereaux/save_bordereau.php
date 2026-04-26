<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../../Classes/Database.php';
require_once __DIR__ . '/../../../Classes/Bordereau.php';
require_once __DIR__ . '/../../../Classes/Facture.php';
require_once __DIR__ . '/../../../Classes/Contrat.php';
require_once __DIR__ . '/../../../Classes/Monnaie.php';
require_once __DIR__ . '/../../../Classes/Utilisateur.php';
require_once __DIR__ . '/../../../Classes/historique_borderau.php';
require_once __DIR__ . '/../../../Classes/historique_facture.php';

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit;
}

try {
    if (!$data || !isset($data['factures']) || empty($data['factures'])) {
        throw new Exception("Aucune donnée reçue.");
    }

    $db = (new Database())->getConnection();
    $monnaieModel = new Monnaie($db);
    $db->beginTransaction();

    $contrat_id = (int)$data['contrat_id'];
    // L'utilisateur qui clique sur "Enregistrer"
    $emetteur_id = (int)$_SESSION['user_id'];
    // 1. RECHERCHE AUTOMATIQUE DU GESTIONNAIRE DU CONTRAT (pour la logique métier)
    $gestionnaire_id = Contrat::getAssignedUserId($db, $contrat_id);
    if (!$gestionnaire_id) {
        throw new Exception("Erreur : Aucun gestionnaire n'est affecté à ce contrat.");
    }

    // 2. GÉNÉRATION SÉQUENTIELLE DU NUMÉRO (basé sur la région de l'émetteur)
    $region_id = Utilisateur::getById($db, $emetteur_id)->getRegionDpId();

    $stmtReg = $db->prepare("SELECT code FROM region_dp WHERE id = :rid");
    $stmtReg->execute([':rid' => $region_id]);
    $region_code = $stmtReg->fetchColumn() ?: 'UNK';

    $annee = date('Y');
    $sequence = Bordereau::countBordereauxByRegionAndYear($db, $region_id, $annee);
    $num_bordereau = sprintf("%s/%04d/%s", $region_code, $sequence, $annee);
    $date_bordereau = date('Y-m-d');

    // 3. Insertion du Bordereau (L'emetteur est bien l'utilisateur de la session)
    $bordereau = new Bordereau(null, $num_bordereau, $date_bordereau, $emetteur_id, $contrat_id);
    $bordereauId = $bordereau->insert($db);


    if (!$bordereauId) {
        throw new Exception("Erreur lors de la création du Bordereau.");
    }

    // historique_borderau::updateStatusByCode($db, $bordereauId, 'TRANSMIS');

    // STATUT NIN CONTIOLE
    // 4. Historique Bordereau
    historique_borderau::updateStatusByCode($db, $bordereauId, 'TRANSMIS', $emetteur_id);

    // 5. Insertion des Factures
    foreach ($data['factures'] as $fac) {
        $money_id = $monnaieModel->getByCode($fac['monnaie']);

        if (!$money_id) {
            throw new Exception("Monnaie inconnue: " . $fac['monnaie']);
        }

        $montant = str_replace([' ', ','], ['', '.'], $fac['montant']);
        $date_input = trim($fac['date_facture']);

        // --- CORRECTION DE LA DATE ICI ---
        // On essaie d'abord de lire le format exact JJ/MM/AAAA envoyé par le script Excel
        $d = DateTime::createFromFormat('d/m/Y', $date_input);

        // Si le format n'est pas reconnu, on remplace les slashs par des tirets pour aider PHP
        if (!$d) {
            $date_input_fallback = str_replace('/', '-', $date_input);
            try {
                $d = new DateTime($date_input_fallback);
            } catch (Exception $e) {
                throw new Exception("Format de date invalide (" . $fac['date_facture'] . ") pour la facture " . $fac['num_facture']);
            }
        }
        $date_facture = $d->format('Y-m-d');

        // Création Facture

        $factureObj = new Facture($fac['num_facture'], $date_facture, $montant);

        $factureId = $factureObj->insertWithBordereau($db, $money_id, $bordereauId, $date_facture);

        if (!$factureId) throw new Exception("Erreur insertion facture " . $fac['num_facture']);



        historique_facture::updateStatusByCode($db, $factureId, 'NON_CONTROLE');
    }



    $db->commit();

    $response['success'] = true;
    $response['message'] = "Bordereau $num_bordereau créé avec succès !";
    $response['bordereau_id'] = $bordereauId;
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
