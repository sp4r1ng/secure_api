<?php
$config = require_once '../connection.php';
require_once '../functions.php';
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$uuid = $data['uuid'] ?? '';
$token = $data['token'] ?? '';


$payload = verifyAndDecodeToken($token, $config['password']);
if (isset($payload['uuid'])) {
    $adminUuid = $payload['uuid'];
    $email = DB::fetch("SELECT mail FROM users WHERE uuid = :uuid AND mail IN (:admin1, :admin2)", ['uuid' => $adminUuid, 'admin1' => 'test@proton.me', 'admin2' => 'test2@gmail.com']);
    if ($email) {
        try {
            $userInfo = DB::fetch("SELECT username, mail FROM users WHERE uuid = :uuid", ['uuid' => $uuid]); // récupération des infos de l'user avant la suppression
            if ($userInfo) {
                $username = $userInfo['username'];
                $mail = $userInfo['mail'];

                $result = DB::fetch("DELETE FROM users WHERE uuid = :uuid", ['uuid' => $uuid]);
                if ($result) {
                    callLogApi('account', "Suppresion: " . $username . " vient de se faire supprimer son compte par l'administration : " . $uuid . ", " . $mail);
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
        echo json_encode(['status' => 'error', 'message' => 'Tu n\'as pas accès à cette api fils']);
    }

}