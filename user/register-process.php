<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'];
$mail = $data["email"];
$password = $data["password"];

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Vérification de l'email
$existingUser = DB::fetch("SELECT id FROM users WHERE mail = :mail", ['mail' => $mail]);
if ($existingUser) {
    echo json_encode(['status' => 'error', 'message' => 'Email déjà existant']);
    exit;
}

$uuid = guidv4(); // uuid generation
$admin = ($mail === 'test@proton.me' || $mail === 'test2@gmail.com') ? 1 : 0; // for access to the admin panel

$result = DB::fetch("INSERT INTO users (uuid, username, mail, password, admin) VALUES (:uuid, :username, :mail, :password, :admin)", [
    'uuid' => $uuid,
    'username' => $username,
    'mail' => $mail,
    'password' => $hashedPassword,
    'admin' => $admin
]);

if ($result) {
    $token = generateSignature(['uuid' => $uuid], $config['password']);
    callLogApi('account', "Register: " .$username . " s'est inscrit avec l'uuid et le mail suivant " . $uuid . ", " . $mail);
    echo json_encode(['status' => 'success', 'message' => 'Utilisateur enregistré', 'uuid' => $uuid, 'token' => $token]);
    } else {
    echo json_encode(['status' => 'error', 'message' => 'Une erreur inattendue est survenue']);
}