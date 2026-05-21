<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI;

use GuzzleHttp\Client;
use PinVandaag\BuckarooAPI\Client\APIClient;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use PinVandaag\BuckarooAPI\Model\ApiKey;
use PinVandaag\BuckarooAPI\Model\Customer;
use PinVandaag\BuckarooAPI\Model\CustomerSearchResult;
use PinVandaag\BuckarooAPI\Model\Merchant;
use PinVandaag\BuckarooAPI\Model\MerchantFeatures;
use PinVandaag\BuckarooAPI\Model\MerchantLegalEntity;
use PinVandaag\BuckarooAPI\Model\TransactionSearchResult;
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

    /**
     * Create a long-lived API key with the given OAuth access token.
     */
    public function createApiKey(
        AccessToken|string $accessToken,
        string $name,
        string|array $scopes = "sale:read sale:write transaction:read",
    ): ApiKey {
        return $this->apiClient->createApiKey($accessToken, $name, $scopes);
    }

    /**
     * Add a new customer or update an existing customer by reference.
     *
     * @param array<string, mixed> $customer
     */
    public function createOrUpdateCustomer(
        string $accessToken,
        array $customer,
    ): Customer {
        return $this->apiClient->createOrUpdateCustomer($accessToken, $customer);
    }

    /**
     * Search customers.
     *
     * @param array<string, mixed> $filters
     */
    public function searchCustomers(
        string $accessToken,
        array $filters = [],
    ): CustomerSearchResult {
        return $this->apiClient->searchCustomers($accessToken, $filters);
    }

    /**
     * Get merchant details.
     */
    public function getMerchant(
        string $accessToken,
    ): Merchant {
        return $this->apiClient->getMerchant($accessToken);
    }

    /**
     * Update merchant details.
     */
    public function updateMerchant(
        string $accessToken,
        string $defaultLanguage,
    ): Merchant {
        return $this->apiClient->updateMerchant($accessToken, $defaultLanguage);
    }

    /**
     * Get merchant settings/features.
     */
    public function getMerchantFeatures(
        string $accessToken,
        ?string $continuationToken = null,
    ): MerchantFeatures {
        return $this->apiClient->getMerchantFeatures($accessToken, $continuationToken);
    }

    /**
     * Get merchant legal entity.
     */
    public function getMerchantLegalEntity(
        string $accessToken,
    ): MerchantLegalEntity {
        return $this->apiClient->getMerchantLegalEntity($accessToken);
    }

    /**
     * Search sales transactions using a stored API key.
     *
     * @param array<string, mixed> $filters
     */
    public function searchTransactions(
        string $accessToken,
        array $filters = [],
    ): TransactionSearchResult {
        return $this->apiClient->searchTransactions($accessToken, $filters);
    }
}
