<?php
require_once '../../classes/Database.php';
require_once '../../classes/Monnaie.php';

$db = (new Database())->getConnection();
$model = new Monnaie($db);

$code = $_GET['code'] ?? '';
$id = $_GET['id'] ?? null; // Récupère l'ID envoyé par le JS

if ($id) {
    // Si on a un ID, on vérifie les doublons chez les AUTRES
    echo json_encode(['exists' => $model->codeExistsForUpdate($code, $id)]);
} else {
    // Sinon (Ajout), vérification normale
    echo json_encode(['exists' => $model->codeExists($code)]);
}