<?php
session_start();
require_once '../../classes/Database.php';
require_once '../../classes/utilisateur.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';



if ($action === 'update_user') {
    $id = (int) ($_POST['user_id'] ?? 0);
    
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $active = (int) (isset($_POST['active']) ? $_POST['active'] : 0);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    $user = $id ? Utilisateur::getById($db, $id) : null;
    if (!$user) {
         $_SESSION['alert'] =[
            'icon'  => 'error',
            'title' => 'Erreur',
            'text'  => 'Utilisateur introuvable dans la base de données.'
        ];
    } else {
        $update_password = ($new_password !== '');
        if ($update_password) {
            if ($current_password === '' || !password_verify($current_password, $user->getPasswordUser())) {
                $_SESSION['flash_message'] = "Mot de passe actuel incorrect. Le mot de passe n'a pas été modifié.";
                $_SESSION['flash_type'] = "danger";
            } else {
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                if (Utilisateur::update($db, $id, $role_id, $active, $hashed_new)) {
                    $_SESSION['flash_message'] = "Utilisateur et mot de passe mis à jour !";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Erreur lors de la mise à jour.";
                    $_SESSION['flash_type'] = "danger";
                }
            }
        } else {
            if (Utilisateur::update($db, $id, $role_id, $active, null)) {
              $usrname = $user->getById($db, $id)->getUserName();
            $_SESSION['alert'] =[
                    'icon'  => 'success',
                    'title' => 'Modification réussie',
                    'text'  => 'L\'utilisateur ' . $usrname . ' a été modifié avec succès !'
                ];
            }
        }
    }
}


header("Location: ../../Pages/Utilisateur/Liste_User.php");
exit();