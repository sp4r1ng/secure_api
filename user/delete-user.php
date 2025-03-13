<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$uuid = $data['uuid'] ?? '';
$token = $data['token'] ?? '';

if (!isset($data['token'])) {
    echo json_encode(['message' => 'Token non fourni']);
    exit;
}

$payload = verifyAndDecodeToken($token, $config['password']);
if ($payload && $payload["uuid"] === $uuid) {
    try {
        $userInfo = DB::fetch("SELECT username, mail FROM users WHERE uuid = :uuid", ['uuid' => $uuid]);
        if ($userInfo) {
            $username = $userInfo['username'];
            $mail = $userInfo['mail'];

            $result = DB::fetch("DELETE FROM users WHERE uuid = :uuid", ['uuid' => $uuid]);
            if ($result) {
                callLogApi('account', "Suppresion: " .$username . " vient de supprimer son compte : " . $uuid . ", " . $mail);
                echo json_encode(['status' => 'success', 'message' => 'Compte supprimé avec succès']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Échec de la suppression du compte']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Échec de la suppression du compte']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Token invalide ou ne correspond pas à l\'UUID']);
}
