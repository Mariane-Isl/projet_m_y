<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On définit $page et on s'assure qu'elle n'est jamais "Undefined"
$page = $_GET['page'] ?? '';
$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? $_SESSION['details_rejet_id'] ?? 0);

// ... vos require_once ...
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Rejet.php';
require_once __DIR__ . '/../../classes/historique_rejet.php';
require_once __DIR__ . '/../../classes/historique_facture.php';
require_once __DIR__ . '/../../classes/Fournisseur.php';
require_once __DIR__ . '/../../classes/Contrat.php';
require_once __DIR__ . '/../../classes/region.php';
require_once __DIR__ . '/../../classes/utilisateur.php';

$database = new Database();
$db       = $database->getConnection();



// ════════════════════════════════════════════════════════════════════════
// POST ACTIONS
// ════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {

    // ── CREATION : Step 1 — draft en session → page confirmation ─────────
    if ($action === 'go_to_confirmation') {
        $contrat_id  = intval($_POST['contrat_id'] ?? 0);
        $region_id   = intval($_POST['region_id']  ?? 0);
        $facture_ids = array_map('intval', $_POST['facture_ids'] ?? []);

        if ($contrat_id <= 0 || empty($facture_ids)) {
            $_SESSION['flash_message'] = "Veuillez sélectionner un contrat et au moins une facture.";
            $_SESSION['flash_type']    = "warning";
            header("Location: ../../Pages/Rejet/creation_rejet.php");
            exit();
        }

        // Préparation du brouillon
        $placeholders = implode(',', array_fill(0, count($facture_ids), '?'));
        $stmt = $db->prepare("SELECT f.*, m.code AS monnaie_code FROM facture f JOIN money m ON f.money_id = m.id WHERE f.id IN ($placeholders)");
        $stmt->execute($facture_ids);
        $facturesDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtC = $db->prepare("SELECT c.num_Contrat, f.Nom_Fournisseur FROM contrat c JOIN fournisseur f ON c.Fournisseur_id = f.id WHERE c.id = ?");
        $stmtC->execute([$contrat_id]);
        $contratInfo = $stmtC->fetch(PDO::FETCH_ASSOC);

        $stmtR = $db->prepare("SELECT label FROM region_dp WHERE id = ?");
        $stmtR->execute([$region_id]);

        $_SESSION['rejet_draft'] = [
            'contrat_id'      => $contrat_id,
            'region_id'       => $region_id,
            'facture_ids'     => $facture_ids,
            'factures_detail' => $facturesDetails,
            'num_Contrat'     => $contratInfo['num_Contrat'] ?? '—',
            'Nom_Fournisseur' => $contratInfo['Nom_Fournisseur'] ?? '—',
            'structure_label' => $stmtR->fetchColumn() ?: '',
        ];

        header("Location: ../../Pages/Rejet/confirmation_rejet.php");
        exit();
    }

    // ── CREATION : Step 2 — création finale ───────────────────────────────
    if ($action === 'create_rejet') {
        $draft = $_SESSION['rejet_draft'] ?? null;
        $facture_ids_final = $_POST['facture_ids'] ?? [];

        if (!$draft || empty($facture_ids_final)) {
            $_SESSION['flash_message'] = "Session expirée ou aucune facture sélectionnée.";
            $_SESSION['flash_type'] = "danger";
            header("Location: ../../Pages/Rejet/creation_rejet.php");
            exit();
        }

        try {
            $db->beginTransaction();

            // 1. GÉNÉRATION DU NUMÉRO COMPLET
            $sequenceNum = Rejet::getNextNumRejet($db); // Récupère le prochain chiffre (ex: 3)
            $numeroPadded = str_pad($sequenceNum, 3, '0', STR_PAD_LEFT); // Transforme en 003

            // Récupérer le code région (ex: ALG)
            $stmtReg = $db->prepare("SELECT code FROM region_dp WHERE id = ?");
            $stmtReg->execute([$draft['region_id']]);
            $region_code = strtoupper($stmtReg->fetchColumn() ?: 'XX');

            // ON FABRIQUE LA CHAÎNE FINALE
            $fullNumRejet = "SH/DP/REJ/{$region_code}/{$numeroPadded}";

            // 2. INSERTION EN BDD (On envoie la chaîne complète)
            $new_id = Rejet::create($db, $fullNumRejet, trim($_POST['cause']), $draft['region_id'], $draft['contrat_id']);

            if (!$new_id) {
                throw new Exception("L'ID du rejet n'a pas pu être généré.");
            }

            // 2. MISE À JOUR DE L'HISTORIQUE DU REJET
            historique_rejet::updateStatusByCode($db, $new_id, 'CREE', $_SESSION['user_id']);

            // 3. Liaison des factures
            foreach ($facture_ids_final as $fid) {
                $fid = intval($fid);
                Rejet::addFacture($db, $new_id, $fid);

                // Mise à jour du statut de la facture (on ne supprime plus, on ajoute)
                historique_facture::updateStatusByCode($db, $fid, 'REJETER');
            }

            $db->commit();

            // 4. Finalisation
            unset($_SESSION['rejet_draft']);
            $_SESSION['flash_message'] = "Le rejet a été créé avec succès !";
            $_SESSION['flash_type']    = "success";

            // On stocke l'ID pour la page de détails (URL Propre)
            $_SESSION['details_rejet_id'] = $new_id;

            header("Location: ../../Pages/Rejet/details_rejet.php");
            exit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['flash_message'] = "Erreur : " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            header("Location: ../../Pages/Rejet/confirmation_rejet.php");
            exit();
        }
    }

    // ── DETAILS : Sauvegarder le motif ───────────────────────────────────
    if ($action === 'save_cause') {
        Rejet::updateCause($db, $id, trim($_POST['cause']));
        $_SESSION['details_rejet_id'] = $id; // On garde l'ID en session
        header("Location: ../../Pages/Rejet/details_rejet.php"); // URL PROPRE
        exit();
    }

    // ── DETAILS : Récupérer (statut → RECUP) ─────────────────────────────
    if ($action === 'recuperer') {
        try {
            $db->beginTransaction();
            historique_rejet::updateStatusByCode($db, $id, 'RECUP', $_SESSION['user_id']);
            $db->commit();
            $_SESSION['flash_message'] = "Marqué comme récupéré.";
            $_SESSION['flash_type'] = "success";
        } catch (Throwable $e) {
            $db->rollBack();
        }
        $_SESSION['details_rejet_id'] = $id;
        header("Location: ../../Pages/Rejet/details_rejet.php");
        exit();
    }

    // ── DETAILS : Supprimer le rejet ──────────────────────────────────────
    if ($action === 'delete_rejet') {
        if (Rejet::deleteById($db, $id)) {
            // Si supprimé, on vide la session de l'ID car il n'existe plus
            unset($_SESSION['details_rejet_id']);
            $_SESSION['flash_message'] = "Le rejet a été supprimé avec succès.";
            $_SESSION['flash_type'] = "success";
            header("Location: ../../Pages/Rejet/Liste_rejet.php");
        } else {
            $_SESSION['flash_message'] = "Erreur lors de la suppression.";
            $_SESSION['flash_type'] = "danger";
            header("Location: ../../Pages/Rejet/details_rejet.php");
        }
        exit();
    }

    // ── DETAILS : Retirer une facture ─────────────────────────────────────
    if ($action === 'remove_facture') {
        $fid = intval($_POST['facture_id']);
        if (Rejet::removeFacture($db, $id, $fid)) {
            historique_facture::updateStatusByCode($db, $fid, 'RECU');
        }
        $_SESSION['details_rejet_id'] = $id;
        header("Location: ../../Pages/Rejet/details_rejet.php");
        exit();
    }

    // ── DETAILS : Ajouter des factures ────────────────────────────────────
    if ($action === 'add_factures') {
        foreach (($_POST['facture_ids'] ?? []) as $fid) {
            if (Rejet::addFacture($db, $id, intval($fid))) {
                historique_facture::updateStatusByCode($db, intval($fid), 'REJETER');
            }
        }
        $_SESSION['details_rejet_id'] = $id;
        header("Location: ../../Pages/Rejet/details_rejet.php");
        exit();
    }

    if ($action === 'set_details_session') {
        $_SESSION['details_rejet_id'] = $id;
        header("Location: ../../Pages/Rejet/details_rejet.php");
        exit();
    }
}

// ════════════════════════════════════════════════════════════════════════
// LOGIQUE DE PRÉPARATION DES DONNÉES (Pour les vues dans /Pages/)
// ════════════════════════════════════════════════════════════════════════

// -- Données pour Liste_rejet.php --
$rejets = Rejet::getAllWithDetails($db, $_SESSION['liste_filters'] ?? []);
$listeFournisseurs = Fournisseur::getAll($db);
$listeStructures = Rejet::getAllRegions($db);
$listeStatuts = Rejet::getAllStatuts($db);


// ════════════════════════════════════════════════════════════════════════
// GET : DETAILS
// ════════════════════════════════════════════════════════════════════════
if ($page === 'details') {
    if ($id <= 0) {
    }

    $data = Rejet::getDetailsById($db, $id);
    if (!$data['infos']) {
        $_SESSION['flash_message'] = "Ce rejet n'existe pas.";
        $_SESSION['flash_type']    = 'danger';
    }

    $rejet    = $data['infos'];
    $factures = $data['factures'];

    // Créateur stocké en session (pas de colonne utilisateur_id dans rejet)
    $rejet['createur_nom'] = $_SESSION['rejet_createurs'][$id] ?? '—';

    // MODIFICATION ICI : On prend directement la valeur de la BDD pour le titre
    $codification = $rejet['num_rejet'] ?? 'Inconnu';
    $page_title   = "Détails du Rejet #" . $codification;

    $availableFactures = Rejet::getAvailableFactures($db, $id);

    include __DIR__ . '/../../Pages/Rejet/details_rejet.php';
    exit();
}

// ════════════════════════════════════════════════════════════════════════
// GET : IMPRESSION / PDF
// ════════════════════════════════════════════════════════════════════════
if ($page === 'print') {
    if ($id <= 0) {
    }

    $data = Rejet::getDetailsById($db, $id);
    if (!$data['infos']) {
    }

    $rejet    = $data['infos'];
    $factures = $data['factures'];

    // MODIFICATION ICI : On prend directement la valeur de la BDD
    $codification = $rejet['num_rejet'] ?? 'Inconnu';

    $dateRejet    = !empty($rejet['date_rejet'])
        ? (new DateTime($rejet['date_rejet']))->format('d/m/Y')
        : date('d/m/Y');

    $projectRoot = realpath(__DIR__ . '/../../');
    $docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');
    $baseUrl     = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    $logoUrl     = $baseUrl . '/dist/images/sonatrach.jpg';

    include __DIR__ . '/../../Pages/Rejet/print_rejet.php';
    exit();
}
