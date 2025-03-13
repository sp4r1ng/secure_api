<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$mail = $data["email"];
$password = $data["password"];

$user = DB::fetch("SELECT username, uuid, password FROM users WHERE mail = :mail", ['mail' => $mail]);
if ($user && password_verify($password, $user['password'])) {
    $token = generateSignature(['uuid' => $user['uuid']], $config['password']);

    echo json_encode(['status' => 'success','message' => 'Vous êtes connecté','uuid' => $user['uuid'],'token' => $token]);
} else {
    echo json_encode(['status' => 'error','message' => 'Adresse mail ou mot de passe incorrect']);
}
