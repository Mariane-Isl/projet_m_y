<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/utilisateur.php';
require_once '../../classes/Role.php';

// Already logged in → redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ../../Pages/Utilisateur/Liste_User.php');
    
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Pages/login/login.php');
    
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Veuillez remplir tous les champs.';
     header('Location: ../../Pages/login/login.php');
    exit;
}

$db   = (new Database())->getConnection();
$user = Utilisateur::getByUsername($db, $username);

if (!$user || !$user->getActive()) {
    $_SESSION['login_error'] = 'Compte inexistant ou désactivé.';
    header('Location: ../../Pages/login/login.php');
    
}
// DEBUG - remove after fixing

if (!password_verify($password, $user->getPasswordUser())) {
    $_SESSION['login_error'] = 'Mot de passe incorrect.';
    header('Location: ../../Pages/login/login.php');
    exit;
}

// $role = Role::getById($db, $user->getRoleId());

session_regenerate_id(true);

$_SESSION['user_id']    = $user->getId();
$_SESSION['user_name']  = $user->getUserName();
$_SESSION['nom']        = $user->getNom() . ' ' . $user->getPrenom();
$_SESSION['role_id']    = $user->getRoleId();
$_SESSION['role_label'] = $user->getRole()? $user->getRole()->getLabel() : '';
$_SESSION['role_code'] = $user->getRole()? $user->getRole()->getCode() : '';


// $_SESSION['region_id']  = $user->getRegionDpId();
// $_SESSION['role_code']       = $role ? $role->getcode() : '';
// $_SESSION['role']       = $role ? $role->getLabel() : '';

// All roles → dashboard
header('Location: ../../Pages/Utilisateur/Liste_User.php');