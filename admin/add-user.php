<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'];
$mail = $data['email'];
$password = $data['password'];
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$solde = $data['solde'];

// Vérification de l'email
$existingUser = DB::fetch("SELECT id FROM users WHERE mail = :mail", ['mail' => $mail]);
if ($existingUser) {
    echo json_encode(['status' => 'error', 'message' => 'Email déjà existant']);
    exit;
}

$token = $data['token'];
$uuid = guidv4(); // uuid generation 
$admin = ($mail === 'test@proton.me' || $mail === 'test2@gmail.com') ? 1 : 0; // for access to the admin panel
$payload = verifyAndDecodeToken($token, $config['password']);

if (isset($payload['uuid'])) {
    $adminUuid = $payload['uuid'];
    $email = DB::fetch("SELECT mail FROM users WHERE uuid = :uuid AND mail IN (:admin1, :admin2)", ['uuid' => $adminUuid, 'admin1' => 'test@proton.me', 'admin2' => 'test2@gmail.com']);
    if ($email) {
        $result = DB::fetch("INSERT INTO users (uuid, username, mail, password, admin) VALUES (:uuid, :username, :mail, :password, :admin)", [
            'uuid' => $uuid,
            'username' => $username,
            'mail' => $mail,
            'password' => $hashedPassword,
            'admin' => $admin
        ]);
        if ($result) {
            callLogApi('account', "Register: " . $username . " s'est inscrit avec l'uuid et le mail suivant " . $uuid . ", " . $mail);
            echo json_encode(['status' => 'success', 'message' => 'Utilisateur enregistré']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Une erreur inattendue est survenue']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tu n\'as pas accès à cette api fils']);
    }
}