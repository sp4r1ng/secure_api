<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);

$uuid = $data['uuid'] ?? '';
$token = $data['token'] ?? '';

$payload = verifyAndDecodeToken($token, $config['password']);

if (isset($payload["uuid"])) {
    if ($payload["uuid"] === $uuid) {
        $user = DB::fetch("SELECT uuid, username, mail FROM users WHERE uuid = :uuid", ['uuid' => $uuid]);

        if ($user) {
            echo json_encode(['status' => 'success', 'message' => 'Utilisateur valide', 'username' => $user['username'], 'mail' => $user['mail']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Utilisateur invalide']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'L\'uuid et le token sont invalides']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Le token est invalide']);
}