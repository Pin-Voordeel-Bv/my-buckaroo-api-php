<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI;

use GuzzleHttp\Client;
use PinVandaag\BuckarooAPI\Client\APIClient;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class BuckarooAPIClient
{
    private APIClient $apiClient;
    private ?string $clientId = null;
    private ?string $clientSecret = null;

    public function __construct(
        ?APIClient $apiClient = null,
        ?LoggerInterface $logger = null,
        ?string $baseUri = null,
    ) {
        $this->apiClient = $apiClient ?? new APIClient(new Client(), $baseUri ?? '');

        if ($logger !== null) {
            $this->apiClient->setLogger($logger);
        }
    }

    public function configure(
        string $clientId,
        #[SensitiveParameter] string $clientSecret,
        ?string $baseUri = null,
    ): self {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if ($baseUri !== null) {
            $this->apiClient->setBaseUri($baseUri);
        }

        return $this;
    }

    /**
     * @param list<string>|string|null $scope
     */
    public function getAccessToken(array|string|null $scope = null): AccessToken
    {
        if ($this->clientId === null || $this->clientSecret === null) {
            throw new \LogicException('Call configure() with a clientId and clientSecret before requesting a token.');
        }

        return $this->apiClient->retrieveAccessToken($this->clientId, $this->clientSecret, $scope);
    }
}
