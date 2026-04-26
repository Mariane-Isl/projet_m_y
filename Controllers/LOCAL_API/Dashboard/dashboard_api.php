<?php
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
$action   = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'rapport_factures':
    // Récupération universelle des filtres
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data) {
        $data = $_POST; // Fallback si ce n'est pas du JSON (URLSearchParams)
    }
    
    $result = Dashboard::getRapportFactures($db, $data);
    
    echo json_encode([
        'success' => true,
        'count'   => $result['count'],
        'totaux'  => $result['totaux'],
        'data'    => $result['data'],
        'debug'   => [
            'sql'    => $result['debug_sql'] ?? '',
            'params' => $result['debug_params'] ?? []
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    break;

        case 'get_fournisseurs':
            echo json_encode(['success' => true, 'data' => Dashboard::getFournisseurs($db)]);
            break;
        case 'get_stats':
            echo json_encode(['success' => true, 'data' => Dashboard::getGlobalStats($db)]);
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
        case 'get_gestionnaires':
            echo json_encode(['success' => true, 'data' => Dashboard::getGestionnaires($db)]);
            break;
        // ... (autres cases)
        default:
            echo json_encode(['error' => 'Action inconnue']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}