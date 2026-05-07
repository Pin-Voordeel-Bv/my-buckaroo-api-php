<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PinVandaag\BuckarooAPI\Exception\BuckarooAPIException;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use Psr\Log\LoggerAwareTrait;
use SensitiveParameter;

final class APIClient
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
    ) {
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

        /** @var mixed $decoded */
        $decoded = json_decode($body, true);

        if (! is_array($decoded) || ! isset($decoded['access_token']) || ! is_string($decoded['access_token'])) {
            throw new BuckarooAPIException('Buckaroo OAuth token response did not contain an access_token.');
        }

        return AccessToken::fromArray($decoded);
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
