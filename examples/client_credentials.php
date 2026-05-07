<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PinVandaag\BuckarooAPI\BuckarooAPIClient;

$client = (new BuckarooAPIClient())
    ->configure(
        clientId: getenv('BUCKAROO_CLIENT_ID') ?: 'your-client-id',
        clientSecret: getenv('BUCKAROO_CLIENT_SECRET') ?: 'your-client-secret',
        // Fill in the API Gateway base URL Buckaroo gives you, for example:
        // baseUri: 'https://api.buckaroo.nl'
    );

$token = $client->getAccessToken();

var_dump([
    'authorization_header' => $token->authorizationHeader(),
    'expires_at' => date(DATE_ATOM, $token->expiresAt()),
]);
