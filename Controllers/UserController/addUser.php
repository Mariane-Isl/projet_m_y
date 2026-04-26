<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/utilisateur.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

if ($action === 'add_user') 
{
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $username = $_POST['user_name'] ?? '';
    $password = $_POST['password_user'] ?? '';
    $role = $_POST['role_id'] ?? null;
    $region = $_POST['region_dp_id'] ?? null;


    if (Utilisateur::insert($db, $nom, $prenom, $username, $password, $role, $region)) {
      $_SESSION['alert'] =[
            'icon'  => 'success',
            'title' => 'Succès',
            'text'  => 'L\'utilisateur a été créé avec succès !'
        ];
    } else {
        // En cas d'ÉCHEC
        $_SESSION['alert'] =[
            'icon'  => 'error',
            'title' => 'Erreur',
            'text'  => 'Un problème est survenu lors de la création de l\'utilisateur.'
        ];
    }
}

header("Location: ../../Pages/Utilisateur/Liste_User.php");
exit();




