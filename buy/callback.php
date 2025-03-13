<?php
require_once '../functions.php';
require_once '../db.php';
$whitelisted_ips = [
    'xx.xxx.xxx.xx',
    'xx.xxx.xxx.xx',
    'xx.xxx.xxx.xx'
];

$client_ip = $_SERVER['REMOTE_ADDR'];
if (!in_array($client_ip, $whitelisted_ips)) {
    callLogApi("forbidden", "callback.php :" . $client_ip . "Tentative d'accès à /api/callback.php !");
    http_response_code(403); // forbidden
    die("Accès interdit");
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit("Méthode non autorisée");
}
$data = $_GET;

if (isset($data['pending']) && $data['pending'] == 1) {
    handlePendingPayment($data);
} elseif (isset($data['pending']) && $data['pending'] == 0 && isset($data['confirmations']) && $data['confirmations'] > 0) {
    handleConfirmedPayment($data);
} else {
    http_response_code(400);
    exit("Statut de paiement invalide ou manquant");
}

function handlePendingPayment($data)
{
    callLogApi("transaction","Paiement en attente reçu. UUID: " . ($data['uuid'] ?? 'Non fourni'));

}

function handleConfirmedPayment($data)
{
    callLogApi("transaction", "Paiement confirmé reçu. UUID: " . ($data['uuid'] ?? 'Non fourni') . ", Confirmations: " . ($data['confirmations'] ?? 'Non fourni'));

    $config = require 'connection.php';
    $value_forwarded_coin_convert = round($data['value_forwarded_coin_convert']['EUR']);

    $result = DB::fetch("UPDATE users SET coins = coins + :coins WHERE uuid = :uuid", [
        'coins' => $value_forwarded_coin_convert,
        'uuid' => $data['uuid']
    ]);
    if ($result) {
        callLogApi("transaction", "{user} a désormais ". $value_forwarded_coin_convert . " de solde, uuid: " . $data['uuid']);
    } else {
        echo "not good uuid";
    }
  

}

http_response_code(200);
echo "ok";