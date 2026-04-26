<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_name'])) {
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

require_once '../../../classes/Database.php';
require_once '../../../classes/utilisateur.php';

$user_name = trim($_POST['user_name']);

if ($user_name === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$exists = Utilisateur::userNameExists($db, $user_name);

echo json_encode(['exists' => $exists]);
exit;
?>
