<?php
/**
 * dashboard_api.php
 * API locale pour le Tableau de Bord (statistiques, filtres rapport, recap OV)
 */
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/Dashboard.php'; 

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db       = $database->getConnection();

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'get_stats':
            echo json_encode(['success' => true, 'data' => Dashboard::getGlobalStats($db)]);
            break;

        case 'get_fournisseurs':
            echo json_encode(['success' => true, 'data' => Dashboard::getFournisseurs($db)]);
            break;

        case 'get_contrats_by_fournisseur':
            $fid = (int)($_POST['fournisseur_id'] ?? 0);
            echo json_encode(['success' => true, 'data' => Dashboard::getContratsByFournisseur($db, $fid)]);
            break;

        case 'get_monnaies':
            echo json_encode(['success' => true, 'data' => Dashboard::getMonnaies($db)]);
            break;

        case 'get_structures':
            echo json_encode(['success' => true, 'data' => Dashboard::getStructures($db)]);
            break;

        case 'get_statuts':
            echo json_encode(['success' => true, 'data' => Dashboard::getStatutsFacture($db)]);
            break;

        case 'get_statuts_ov':
            echo json_encode(['success' => true, 'data' => Dashboard::getStatutsOV($db)]);
            break;

        case 'get_gestionnaires':
            echo json_encode(['success' => true, 'data' => Dashboard::getGestionnaires($db)]);
            break;

       case 'rapport_factures':
            // Accepte les données POST classiques (FormData) ou JSON (Fetch)
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $result = Dashboard::getRapportFactures($db, $data);
            
            // CORRECTION MAJEURE ICI : Ajout des flags JSON pour ne pas planter sur les accents !
            echo json_encode([
                'success' => true,
                'count'   => $result['count'],
                'totaux'  => $result['totaux'],
                'data'    => $result['data'],
                'debug'   => [
                    'sql' => $result['debug_sql'],
                    'params' => $result['debug_params']
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        case 'recap_ov':
            // On récupère les données soit du flux JSON, soit du $_POST classique
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $result = Dashboard::getRecapOV($db, $data);
            echo json_encode([
                'success' => true,
                'count'   => $result['count'],
                'totaux'  => $result['totaux'],
                'data'    => $result['data']
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        case 'graphique_performance':
            $result = Dashboard::getPerformanceGestionnaires($db, $_POST);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        case 'graphique_regional':
            $result = Dashboard::getRepartitionRegionale($db, $_POST);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        case 'graphique_leadtime':
            $result = Dashboard::getLeadTime($db, $_POST);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        default:
            echo json_encode(['error' => 'Action inconnue']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}