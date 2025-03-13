<?php
header('Content-Type: application/json');

$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);

$token = $data['token'] ?? '';
$payload = verifyAndDecodeToken($token, $config['password']);

if (isset($payload['uuid'])) {
    $adminUuid = $payload['uuid'];
    $good = DB::fetch("SELECT mail FROM users WHERE uuid = :uuid AND mail IN (:admin1, :admin2)", ['uuid' => $adminUuid, 'admin1' => 'test@proton.me', 'admin2' => 'test2@gmail.com']);
    if ($good) {
        $uuid = $data['uuid'] ?? '';
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $solde = $data['solde'] ?? '';

        $existingUser = DB::fetch("SELECT id FROM users WHERE mail = :mail", ['mail' => $email]);
        if ($existingUser) {
            echo json_encode(['status' => 'error', 'message' => 'Email déjà existant']);
            exit;
        }

        $result = DB::fetch("UPDATE users SET username = :username, mail = :mail WHERE uuid = :uuid", ['username' => $username, 'mail' => $email, 'uuid' => $uuid]);
        //$result = DB::fetch("UPDATE users SET username = :username, mail = :mail, solde = :solde WHERE uuid = :uuid", ['username' => $username, 'mail' => $email, 'solde' => $solde, 'uuid' => $uuid]);
        if ($result) {
            callLogApi('account', 'Modification : ' . $uuid . ', username: ' . $username . ', mail: ' . $email . ', solde: ' . $solde);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Échec de la mise à jour de l\'utilisateur']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tu n\'es pas autorisé à utilisé l\'api fils']);
    }
}