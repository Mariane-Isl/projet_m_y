<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Requête invalide']);
    exit;
}

$password = $_POST['password_user'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($password === '') {
    echo json_encode(['valid' => false, 'message' => 'Le mot de passe est requis.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['valid' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['valid' => false, 'message' => 'Les deux mots de passe ne correspondent pas.']);
    exit;
}

echo json_encode(['valid' => true]);
