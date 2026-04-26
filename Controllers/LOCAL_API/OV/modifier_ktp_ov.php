<?php
/**
 * API LOCAL — modifier_ktp_ov.php
 * Chemin : Controllers/LOCAL_API/OV/modifier_ktp_ov.php
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Session expirée.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Méthode non autorisée.']); exit(); }

$ov_id   = isset($_POST['ov_id'])   ? intval($_POST['ov_id'])          : 0;
$num_ktp = isset($_POST['num_ktp']) ? trim(htmlspecialchars($_POST['num_ktp'])) : '';

if ($ov_id <= 0 || empty($num_ktp)) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit();
}

require_once __DIR__ . '/../../../classes/Database.php';
$database = new Database();
$db       = $database->getConnection();

try {
    // Vérifier unicité du KTP
    $chk = $db->prepare("SELECT COUNT(*) FROM ordres_virement WHERE Num_KTP = :ktp AND id != :id");
    $chk->execute([':ktp' => $num_ktp, ':id' => $ov_id]);
    if ($chk->fetchColumn() > 0) {
        echo json_encode(['success'=>false,'message'=>'Ce numéro KTP est déjà utilisé par un autre OV.']); exit();
    }

    $upd = $db->prepare("UPDATE ordres_virement SET Num_KTP = :ktp WHERE id = :id");
    $upd->execute([':ktp' => $num_ktp, ':id' => $ov_id]);

    echo json_encode(['success'=>true,'num_ktp'=>$num_ktp,'message'=>'KTP mis à jour.']);

} catch (PDOException $e) {
    error_log("modifier_ktp_ov error: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur base de données.']);
}
