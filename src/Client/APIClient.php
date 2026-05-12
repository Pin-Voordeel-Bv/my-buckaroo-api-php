<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PinVandaag\BuckarooAPI\Exception\BuckarooAPIException;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use PinVandaag\BuckarooAPI\Model\TransactionSearchResult;
use Psr\Log\LoggerAwareTrait;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

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
     * Search Buckaroo sales transactions using an API key.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchTransactions(
        string $apiKey,
        array $filters = [],
    ): TransactionSearchResult {
        $payload = array_filter(
            $filters,
            static fn (mixed $value): bool => $value !== null && $value !== []
        );

        if (! isset($payload['limit'])) {
            $payload['limit'] = 100;
        }

        try {
            $response = $this->client->request(
                'POST',
                $this->uri('/v1/sales/transactions/search'),
                [
                    'headers' => [
                        'X-API-KEY' => $apiKey,
                        'Accept' => 'application/hal+json',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $this->serializer->serialize($payload, 'json'),
                ],
            );
        } catch (Throwable $exception) {
            throw new BuckarooAPIException('Could not search Buckaroo transactions.', 0, $exception);
        }

        $body = (string) $response->getBody();

        try {
            /** @var TransactionSearchResult $result */
            $result = $this->serializer->deserialize($body, TransactionSearchResult::class, 'json');
        } catch (SerializerException $exception) {
            throw new BuckarooAPIException('Could not deserialize Buckaroo transaction search response.', 0, $exception);
        }

        return $result;
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
