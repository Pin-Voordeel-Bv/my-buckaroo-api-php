## About
A PHP Wrapper for the <a href="https://docs.buckaroo.io/v2/reference/">My Buckaroo API</a>

## Installation
`composer require pinvandaag/my-buckaroo-api-php`

## Usage example

```php
<?php

use PinVandaag\BuckarooAPI\BuckarooAPIClient;

final class BuckarooController
{
    private BuckarooAPIClient $apiClient;

    public function __construct()
    {
        $this->apiClient = (new BuckarooAPIClient())
            ->configure(
                clientId: 'your-client-id',
                clientSecret: 'your-client-secret',
                baseUri: 'https://api.buckaroo.io'
            );
    }

    public function getAccessToken()
    {
        $token = $this->apiClient->getAccessToken();

        $authHeader = $token->authorizationHeader();

        print_r($token);
        print_r($authHeader);
        print_r($token->accessToken);
    }
}
```
