<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Requête invalide']);
    exit;
}

require_once '../../../classes/Database.php';
require_once '../../../classes/utilisateur.php';

$user_id          = (int) ($_POST['user_id'] ?? 0);
$current_password = $_POST['current_password'] ?? '';

if ($user_id === 0 || $current_password === '') {
    echo json_encode(['valid' => false, 'message' => 'Données manquantes.']);
    exit;
}

$db   = (new Database())->getConnection();
$user = Utilisateur::getById($db, $user_id);

if (!$user) {
    echo json_encode(['valid' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}

if (!password_verify($current_password, $user->getPasswordUser())) {
    echo json_encode(['valid' => false, 'message' => 'Mot de passe actuel incorrect !']);
    exit;
}

echo json_encode(['valid' => true]);
exit;
?>
