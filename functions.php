<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Fonction pour générer un jwt-token avec un secret
 * @param array $payload inscrivez-y ici le payload du jwt-token
 * @param string $secret inscrivez-y ici le secret qui génèrera le token
 * @return string
 */
function generateSignature($payload, $secret) {
    $jsonPayload = json_encode($payload);
    $b64Payload = rtrim(base64_encode($jsonPayload), '=');
    $hash = hash('sha256', $b64Payload . $secret);
    return $b64Payload . '.' . $hash;
}

/**
 * Fonction pour vérifiez l'authenticiter du jwt-token
 * @param string $token inscrivez-y ici le jwt-token
 * @param string $secret inscrivez-y ici le secret qui génère le token
 * @return {Promise<any | null>} The original JSON payload if the token is valid, null otherwise.
 */
function verifyAndDecodeToken($token, $secret) {
    $parts = explode(".", $token);
    if (count($parts) != 2) {
        return null;
    }
    $calculatedHash = hash('sha256', $parts[0] . $secret);
    if ($calculatedHash === $parts[1]) {
        $stringPayload = base64_decode($parts[0]);
        return json_decode($stringPayload, true);
    }
    return null;
}

/**
 * Fonction de génération d'un uuid v4
 * @return string
*/
function guidv4($data = null) {
    $data = $data ?? random_bytes(16);     // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    assert(strlen($data) == 16);     // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);     // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);     // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Fonction pour log les transactions, les créations de comptes et les actions malveillantes
 * @param string $type account, transaction, forbidden
 * @param string $message inscrivez-y ici le message qui s'affichera dans le fichier log
 * @return null
 */
function callLogApi($type, $message): void
{
    $client = new Client([
        'base_uri' => 'http://localhost.rf.gd',
        'timeout' => 2.5,
    ]);

    $apiKey = '10gs_4p7_k3y_S3cUr3!!!';

    try {
        $response = $client->request('POST', '/api/create-logs.php', [
            'json' => [
                'apikey' => $apiKey,
                'type' => $type,
                'message' => $message
            ]
        ]);

        $body = $response->getBody();
        $result = json_decode($body, true);

        if (isset($result['success']) && $result['success']) {
        } else {
            error_log("Erreur d'envoi de log: " . ($result['error'] ?? 'Erreur inconnue'));
        }

    } catch (GuzzleException $e) {
        error_log("Erreur de requête lors de l'envoi de log: " . $e->getMessage());
    }
}