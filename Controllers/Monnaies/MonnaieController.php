<?php
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Monnaie.php';

$database = new Database();
$db = $database->getConnection();
$monnaieModel = new Monnaie($db);

// Action: AJOUTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $code = strtoupper(trim($_POST['code']));
    $label = trim($_POST['label']);

    if (empty($code) || empty($label)) {
        $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Erreur', 'text' => 'Champs vides.'];
    } elseif ($monnaieModel->codeExists($code)) {
        $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Erreur', 'text' => 'Ce code existe déjà.'];
    } elseif ($monnaieModel->create($code, $label)) {
        $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Parfait !', 'text' => 'Monnaie ajoutée.'];
    }
    header("Location: listeMonnaies.php");
    exit();
}

// Action: MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_money'])) {
    if ($monnaieModel->update($_POST['id'], $_POST['code'], $_POST['label'])) {
        $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Modifié !', 'text' => 'Mise à jour effectuée.'];
    }
    header("Location: listeMonnaies.php");
    exit();
}

// Action: SUPPRIMER
if (isset($_GET['delete_id'])) {
    $monnaieModel->delete($_GET['delete_id']);
    $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Supprimé !', 'text' => 'La monnaie a été retirée.'];
    header("Location: listeMonnaies.php");
    exit();
}

// Récupération de données (Ne mets JAMAIS ça au-dessus des 'header(...)')
$allMonnaies = $monnaieModel->getAll();
