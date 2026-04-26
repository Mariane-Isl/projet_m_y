<?php
// Controllers/LOCAL_API/OV/prevalidate.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$header_info = isset($_POST['header_info']) ? $_POST['header_info'] : null;
$factures_list = isset($_POST['factures_list']) ? $_POST['factures_list'] : null;

// --- VOS VÉRIFICATIONS ICI (Statut des factures, montants, etc) ---

// Si tout est OK :
echo json_encode([
    'success' => true,
    'message' => 'Validation réussie'
    // 'draft_id' => 12345 (optionnel, si vous générez un ID en base)
]);
exit;
