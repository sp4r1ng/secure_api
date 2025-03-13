<?php
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$data = json_decode(file_get_contents('php://input'), true);
$coin = $data['coin'] ?? '';

if (!in_array($coin, ['btc', 'eth', 'ltc'])) {
    echo json_encode([
        'success' => false,
        'message' => "Type de monnaie non supporté"
    ]);
    exit;
}

$addresses = [
    'btc' => 'btc_adress',
    'eth' => 'eth_adress',
    'ltc' => 'ltc_adress'
];

$address = $addresses[$coin];

$callback_url = 'https://localhost/api/callback.php';
$params = array_filter(['token' => $token, 'uuid' => $uuid]);
$callback = $callback_url . (!empty($params) ? '?' . http_build_query($params) : '');

$client = new Client([
    'base_uri' => 'https://api.cryptapi.io/',
    'http_errors' => false
]);

try {
    $response = $client->request('GET', "$coin/create/", [
        'query' => [
            'callback' => $callback,
            'address' => $address,
            'convert' => 1,
        ]
    ]);

    $status_code = $response->getStatusCode();
    $result = json_decode($response->getBody(), true);

    if (isset($result['address_in'])) {
        $payment_address = $result['address_in'];

        $conversion_response = $client->request('GET', "$coin/convert/", [
            'query' => [
                'from' => 'EUR',
                'to' => $coin,
                'value' => 1
            ]
        ]);

        $conversion_result = json_decode($conversion_response->getBody(), true);
        $conversion_rate = isset($conversion_result['value_coin']) ? round(1 / $conversion_result['value_coin'], 2) : null;

        $qr_response = $client->request('GET', "$coin/qrcode/", [
            'query' => [
                'address' => $payment_address,
                'value' => $data['amount'] ?? 0,
                'size' => 256
            ]
        ]);

        $qr_result = json_decode($qr_response->getBody(), true);

        echo json_encode([
            'success' => true,
            'conversion' => $conversion_rate,
            'qrcode' => $qr_result['qr_code'] ?? '',
            'address' => $payment_address,
        ]);
    } else {
        throw new Exception("Erreur lors de la création de l'adresse de paiement");
    }
} catch (RequestException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
