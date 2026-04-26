<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/utilisateur.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';




if ($action === 'toggle_status') 
{
    $id = $_POST['user_id'] ?? null;
    if ($id && Utilisateur::toggleStatus($db, $id)) {
        $_SESSION['flash_message'] = "Statut mis à jour !";
        $_SESSION['flash_type'] = "success";
    }
} 


 
header("Location: ../../Pages/Utilisateur/Liste_User.php");
exit();


