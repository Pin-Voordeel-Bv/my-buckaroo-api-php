<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PinVandaag\BuckarooAPI\Exception\BuckarooAPIException;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use PinVandaag\BuckarooAPI\Model\ApiKey;
use PinVandaag\BuckarooAPI\Model\Customer;
use PinVandaag\BuckarooAPI\Model\CustomerSearchResult;
use PinVandaag\BuckarooAPI\Model\Merchant;
use PinVandaag\BuckarooAPI\Model\MerchantFeatures;
use PinVandaag\BuckarooAPI\Model\MerchantLegalEntity;
use PinVandaag\BuckarooAPI\Model\TransactionSearchResult;
use Psr\Log\LoggerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final class APIClient
{
    use LoggerAwareTrait;

    private readonly SerializerInterface $serializer;

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
        ?SerializerInterface $serializer = null,
    ) {
        $this->serializer = $serializer ?? new Serializer(
            [new ObjectNormalizer(nameConverter: new CamelCaseToSnakeCaseNameConverter())],
            [new JsonEncoder()],
        );
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    /**
     * Retrieve a JWT access token using the OAuth2 client_credentials flow.
     *
     * @param list<string>|string|null $scope Optional scope(s), separated by spaces in the request.
     *
     * @throws BuckarooAPIException
     */
    public function retrieveAccessToken(
        string $clientId,
        #[SensitiveParameter] string $clientSecret,
        array|string|null $scope = null,
    ): AccessToken {
        $formParams = [
            'grant_type' => 'client_credentials',
        ];

        if ($scope !== null && $scope !== []) {
            $formParams['scope'] = is_array($scope) ? implode(' ', $scope) : $scope;
        }

        $startedAt = microtime(true);

        try {
            $response = $this->client->request('POST', $this->uri('/oauth/token'), [
                'auth' => [$clientId, $clientSecret],
                'form_params' => $formParams,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'connect_timeout' => 8.0,
                'http_errors' => false,
                'timeout' => 25.0,
                'verify' => true,
            ]);
        } catch (ConnectException $exception) {
            $this->log('[Buckaroo] OAuth connect failed: ' . $exception->getMessage());
            throw new BuckarooAPIException('Could not connect to Buckaroo OAuth endpoint.', 504, $exception);
        } catch (RequestException $exception) {
            $this->log('[Buckaroo] OAuth request failed: ' . $exception->getMessage());
            throw new BuckarooAPIException('Buckaroo OAuth request failed.', (int) $exception->getCode(), $exception);
        } catch (GuzzleException $exception) {
            $this->log('[Buckaroo] OAuth HTTP client failed: ' . $exception->getMessage());
            throw new BuckarooAPIException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $this->log(sprintf('[Buckaroo] POST /oauth/token -> HTTP %d in %dms', $statusCode, $durationMs));

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new BuckarooAPIException(
                sprintf('Buckaroo OAuth token request failed with HTTP %d: %s', $statusCode, $body),
                $statusCode,
            );
        }

        try {
            /** @var AccessToken $accessToken */
            $accessToken = $this->serializer->deserialize($body, AccessToken::class, 'json');
        } catch (SerializerException $exception) {
            throw new BuckarooAPIException('Could not deserialize Buckaroo OAuth token response.', 0, $exception);
        }

        if ($accessToken->accessToken === '') {
            throw new BuckarooAPIException('Buckaroo OAuth token response did not contain an access_token.');
        }

        return $accessToken;
    }

    /**
     * Create a long-lived Buckaroo API key using an OAuth access token.
     *
     * @throws BuckarooAPIException
     */
    public function createApiKey(
        AccessToken|string $accessToken,
        string $name,
        string|array $scopes,
    ): ApiKey {
        $scopeString = is_array($scopes) ? implode(' ', $scopes) : $scopes;

        $payload = [
            'name' => $name,
            'scopes' => $scopeString,
        ];

        try {
            $response = $this->client->request(
                'POST',
                $this->uri('/v1/apikeys'),
                [
                    'headers' => [
                        'Authorization' => is_string($accessToken)
                            ? 'Bearer ' . $accessToken
                            : $accessToken->authorizationHeader(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $this->serializer->serialize($payload, 'json'),
                ],
            );
        } catch (Throwable $exception) {
            throw new BuckarooAPIException('Could not create Buckaroo API key.', 0, $exception);
        }

        $body = (string) $response->getBody();

        try {
            /** @var ApiKey $apiKey */
            $apiKey = $this->serializer->deserialize($body, ApiKey::class, 'json');
        } catch (SerializerException $exception) {
            throw new BuckarooAPIException('Could not deserialize Buckaroo API key response.', 0, $exception);
        }

        if ($apiKey->key === '') {
            throw new BuckarooAPIException('Buckaroo API key response did not contain a key.');
        }

        return $apiKey;
    }

    /**
     * Add a new Buckaroo customer or update an existing customer by reference.
     *
     * @param array<string, mixed> $customer
     *
     * @throws BuckarooAPIException
     */
    public function createOrUpdateCustomer(
        string $accessToken,
        array $customer,
    ): Customer {
        $payload = $this->filterPayload($customer);

        if (($payload['reference'] ?? null) === null || $payload['reference'] === '') {
            throw new BuckarooAPIException('Buckaroo customer payload requires a reference.');
        }

        /** @var Customer $createdCustomer */
        $createdCustomer = $this->postHalSearch(
            endpoint: '/v1/customers',
            accessToken: $accessToken,
            filters: $payload,
            responseClass: Customer::class,
            actionDescription: 'create or update Buckaroo customer',
        );

        return $createdCustomer;
    }

    /**
     * Search Buckaroo customers.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchCustomers(
        string $accessToken,
        array $filters = [],
    ): CustomerSearchResult {
        /** @var CustomerSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/customers/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: CustomerSearchResult::class,
            actionDescription: 'search Buckaroo customers',
        );

        return $result;
    }

    /**
     * Get an existing customer.
     *
     * @throws BuckarooAPIException
     */
    public function getCustomer(
        string $accessToken,
        string $id,
    ): Customer {
        /** @var Customer $customer */
        $customer = $this->getHal(
            endpoint: sprintf('/v1/customers/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Customer::class,
            actionDescription: sprintf('get Buckaroo customer "%s"', $id),
        );

        return $customer;
    }

    /**
     * Delete an existing customer.
     *
     * @throws BuckarooAPIException
     */
    public function deleteCustomer(
        string $accessToken,
        string $id,
    ): void {
        $this->deleteHal(
            endpoint: sprintf('/v1/customers/%s', rawurlencode($id)),
            accessToken: $accessToken,
            actionDescription: sprintf('delete Buckaroo customer "%s"', $id),
        );
    }

    /**
     * Get merchant details.
     *
     * @throws BuckarooAPIException
     */
    public function getMerchant(
        string $accessToken,
    ): Merchant {
        /** @var Merchant $merchant */
        $merchant = $this->getHal(
            endpoint: '/v1/merchant',
            accessToken: $accessToken,
            responseClass: Merchant::class,
            actionDescription: 'get Buckaroo merchant details',
        );

        return $merchant;
    }

    /**
     * Update merchant details.
     *
     * @throws BuckarooAPIException
     */
    public function updateMerchant(
        string $accessToken,
        string $defaultLanguage,
    ): Merchant {
        /** @var Merchant $merchant */
        $merchant = $this->patchHal(
            endpoint: '/v1/merchant',
            accessToken: $accessToken,
            payload: [
                'defaultLanguage' => $defaultLanguage,
            ],
            responseClass: Merchant::class,
            actionDescription: 'update Buckaroo merchant details',
        );

        return $merchant;
    }

    /**
     * Get merchant settings/features.
     *
     * @throws BuckarooAPIException
     */
    public function getMerchantFeatures(
        string $accessToken,
        ?string $continuationToken = null,
    ): MerchantFeatures {
        /** @var MerchantFeatures $features */
        $features = $this->getHal(
            endpoint: '/v1/merchant/features',
            accessToken: $accessToken,
            responseClass: MerchantFeatures::class,
            actionDescription: 'get Buckaroo merchant features',
            query: [
                'continuationToken' => $continuationToken,
            ],
        );

        return $features;
    }

    /**
     * Get merchant legal entity.
     *
     * @throws BuckarooAPIException
     */
    public function getMerchantLegalEntity(
        string $accessToken,
    ): MerchantLegalEntity {
        /** @var MerchantLegalEntity $legalEntity */
        $legalEntity = $this->getHal(
            endpoint: '/v1/merchant/legalentity',
            accessToken: $accessToken,
            responseClass: MerchantLegalEntity::class,
            actionDescription: 'get Buckaroo merchant legal entity',
        );

        return $legalEntity;
    }

    /**
     * Search Buckaroo sales transactions using an API key.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchTransactions(
        string $accessToken,
        array $filters = [],
    ): TransactionSearchResult {
        $filters['limit'] ??= 100;

        /** @var TransactionSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/sales/transactions/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: TransactionSearchResult::class,
            actionDescription: 'search Buckaroo transactions',
        );

        return $result;
    }

    /**
     * @template T of object
     *
     * @param array<string, mixed> $filters
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function postHalSearch(
        string $endpoint,
        string $accessToken,
        array $filters,
        string $responseClass,
        string $actionDescription,
    ): object {
        $payload = $this->filterPayload($filters);

        $response = $this->requestHal(
            method: 'POST',
            endpoint: $endpoint,
            options: [
                'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/hal+json',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $this->serializer->serialize($payload, 'json'),
                ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function getHal(
        string $endpoint,
        string $accessToken,
        string $responseClass,
        string $actionDescription,
        array $query = [],
    ): object {
        $query = $this->filterPayload($query);

        $response = $this->requestHal(
            method: 'GET',
            endpoint: $endpoint,
            options: [
                'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/hal+json',
                    ],
                    'query' => $query,
                ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @template T of object
     *
     * @param array<string, mixed> $payload
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function patchHal(
        string $endpoint,
        string $accessToken,
        array $payload,
        string $responseClass,
        string $actionDescription,
    ): object {
        $payload = $this->filterPayload($payload);

        $response = $this->requestHal(
            method: 'PATCH',
            endpoint: $endpoint,
            options: [
                'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/hal+json',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $this->serializer->serialize($payload, 'json'),
                ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @throws BuckarooAPIException
     */
    private function deleteHal(
        string $endpoint,
        string $accessToken,
        string $actionDescription,
    ): void {
        $response = $this->requestHal(
            method: 'DELETE',
            endpoint: $endpoint,
            options: [
                'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/hal+json',
                    ],
                ],
            actionDescription: $actionDescription,
        );

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 204 && ($statusCode < 200 || $statusCode >= 300)) {
            throw new BuckarooAPIException(
                sprintf('Buckaroo request to %s failed with HTTP %d.', $endpoint, $statusCode),
                $statusCode
            );
        }
    }
 
    /**
     * @param array<string, mixed> $options
     *
     * @throws BuckarooAPIException
     */
    private function requestHal(
        string $method,
        string $endpoint,
        array $options,
        string $actionDescription,
    ): ResponseInterface {
        try {
            return $this->client->request(
                $method,
                $this->uri($endpoint),
                $options,
            );
        } catch (Throwable $exception) {
            throw new BuckarooAPIException(sprintf('Could not %s.', $actionDescription), 0, $exception);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function deserializeResponse(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): object {
        $body = (string) $response->getBody();

        try {
            /** @var T $result */
            $result = $this->serializer->deserialize($body, $responseClass, 'json');
        } catch (SerializerException $exception) {
            throw new BuckarooAPIException(
                sprintf('Could not deserialize Buckaroo response for %s.', $actionDescription),
                0,
                $exception
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== null && $value !== []
        );
    }

    private function uri(string $path): string
    {
        if ($this->baseUri === '') {
            return $path;
        }

        return $this->baseUri . '/' . ltrim($path, '/');
    }

    private function log(string $message): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error($message);
    }
}
