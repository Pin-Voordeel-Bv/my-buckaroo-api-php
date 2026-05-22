<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PinVandaag\BuckarooAPI\Exception\BuckarooAPIException;
use PinVandaag\BuckarooAPI\Model\AccessToken;
use PinVandaag\BuckarooAPI\Model\AccountPayoutSettings;
use PinVandaag\BuckarooAPI\Model\AccountSearchResult;
use PinVandaag\BuckarooAPI\Model\Account;
use PinVandaag\BuckarooAPI\Model\ApiKey;
use PinVandaag\BuckarooAPI\Model\Application;
use PinVandaag\BuckarooAPI\Model\ApplicationInstallation;
use PinVandaag\BuckarooAPI\Model\ApplicationInstallationSearchResult;
use PinVandaag\BuckarooAPI\Model\ApplicationSearchResult;
use PinVandaag\BuckarooAPI\Model\Customer;
use PinVandaag\BuckarooAPI\Model\CustomerSearchResult;
use PinVandaag\BuckarooAPI\Model\GlobalSearchResult;
use PinVandaag\BuckarooAPI\Model\InternalTerminal;
use PinVandaag\BuckarooAPI\Model\InternalTerminalConnectionStatus;
use PinVandaag\BuckarooAPI\Model\Invoice;
use PinVandaag\BuckarooAPI\Model\InvoiceAttachment;
use PinVandaag\BuckarooAPI\Model\InvoiceCreditNote;
use PinVandaag\BuckarooAPI\Model\InvoiceSearchResult;
use PinVandaag\BuckarooAPI\Model\Merchant;
use PinVandaag\BuckarooAPI\Model\MerchantFeatures;
use PinVandaag\BuckarooAPI\Model\MerchantLegalEntity;
use PinVandaag\BuckarooAPI\Model\PaymentMethodSubscription;
use PinVandaag\BuckarooAPI\Model\PaymentMethodSubscriptionSearchResult;
use PinVandaag\BuckarooAPI\Model\PayoutSearchResult;
use PinVandaag\BuckarooAPI\Model\Payout;
use PinVandaag\BuckarooAPI\Model\Sale;
use PinVandaag\BuckarooAPI\Model\SaleSearchResult;
use PinVandaag\BuckarooAPI\Model\Store;
use PinVandaag\BuckarooAPI\Model\StoreSearchResult;
use PinVandaag\BuckarooAPI\Model\SmartTerminal;
use PinVandaag\BuckarooAPI\Model\SmartTerminalConnectionStatus;
use PinVandaag\BuckarooAPI\Model\SmartTerminalMdmSettings;
use PinVandaag\BuckarooAPI\Model\TerminalSearchResult;
use PinVandaag\BuckarooAPI\Model\Transaction;
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
     * Get all accounts.
     *
     * @throws BuckarooAPIException
     */
    public function getAccounts(
        string $accessToken,
    ): AccountSearchResult {
        /** @var AccountSearchResult $result */
        $result = $this->getHal(
            endpoint: '/v1/accounts',
            accessToken: $accessToken,
            responseClass: AccountSearchResult::class,
            actionDescription: 'get Buckaroo accounts',
        );

        return $result;
    }

    /**
     * Get an account by id.
     *
     * @throws BuckarooAPIException
     */
    public function getAccount(
        string $accessToken,
        string $id,
    ): Account {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo account request requires an id.');
        }

        /** @var Account $account */
        $account = $this->getHal(
            endpoint: sprintf('/v1/accounts/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Account::class,
            actionDescription: sprintf('get Buckaroo account "%s"', $id),
        );

        return $account;
    }

    /**
     * Update the payout settings of an account.
     *
     * @param array<string, mixed> $payload
     *
     * @throws BuckarooAPIException
     */
    public function updateAccountPayoutSettings(
        string $accessToken,
        string $id,
        array $payload,
    ): AccountPayoutSettings {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo account payout settings update requires an id.');
        }

        $payload = $this->filterPayload($payload);

        if (($payload['grouping'] ?? null) === null || $payload['grouping'] === '') {
            throw new BuckarooAPIException('Buckaroo account payout settings update requires grouping.');
        }

        if (($payload['payoutInterval'] ?? null) === null || $payload['payoutInterval'] === '') {
            throw new BuckarooAPIException('Buckaroo account payout settings update requires payoutInterval.');
        }

        /** @var AccountPayoutSettings $settings */
        $settings = $this->patchHal(
            endpoint: sprintf('/v1/accounts/%s', rawurlencode($id)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: AccountPayoutSettings::class,
            actionDescription: sprintf('update Buckaroo account "%s" payout settings', $id),
        );

        return $settings;
    }

    /**
     * Search applications.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchApplications(
        string $accessToken,
        array $filters = [],
    ): ApplicationSearchResult {
        /** @var ApplicationSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/applications/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: ApplicationSearchResult::class,
            actionDescription: 'search Buckaroo applications',
        );

        return $result;
    }

    /**
     * Get application by id.
     *
     * @throws BuckarooAPIException
     */
    public function getApplication(
        string $accessToken,
        string $id,
    ): Application {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo application request requires an id.');
        }

        /** @var Application $application */
        $application = $this->getHal(
            endpoint: sprintf('/v1/applications/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Application::class,
            actionDescription: sprintf('get Buckaroo application "%s"', $id),
        );

        return $application;
    }

    /**
     * Create application.
     *
     * @param array<string, mixed> $application
     *
     * @throws BuckarooAPIException
     */
    public function createApplication(
        string $accessToken,
        array $application,
    ): Application {
        $payload = $this->filterPayload($application);

        foreach (['name', 'applicationType', 'scopes'] as $requiredField) {
            if (($payload[$requiredField] ?? null) === null || $payload[$requiredField] === '') {
                throw new BuckarooAPIException(sprintf('Buckaroo application payload requires "%s".', $requiredField));
            }
        }

        /** @var Application $createdApplication */
        $createdApplication = $this->postHalSearch(
            endpoint: '/v1/applications',
            accessToken: $accessToken,
            filters: $payload,
            responseClass: Application::class,
            actionDescription: 'create Buckaroo application',
        );

        return $createdApplication;
    }

    /**
     * Get installations for an application.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchApplicationInstallations(
        string $accessToken,
        string $id,
        array $filters = [],
    ): ApplicationInstallationSearchResult {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo application installations search requires an application id.');
        }

        /** @var ApplicationInstallationSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: sprintf('/v1/applications/%s/installations/search', rawurlencode($id)),
            accessToken: $accessToken,
            filters: $filters,
            responseClass: ApplicationInstallationSearchResult::class,
            actionDescription: sprintf('search Buckaroo application "%s" installations', $id),
        );

        return $result;
    }

    /**
     * Get installation for an application.
     *
     * @throws BuckarooAPIException
     */
    public function getApplicationInstallation(
        string $accessToken,
        string $id,
        string $installationId,
    ): ApplicationInstallation {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo application installation request requires an application id.');
        }

        if ($installationId === '') {
            throw new BuckarooAPIException('Buckaroo application installation request requires an installation id.');
        }

        /** @var ApplicationInstallation $installation */
        $installation = $this->getHal(
            endpoint: sprintf('/v1/applications/%s/installations/%s', rawurlencode($id), rawurlencode($installationId)),
            accessToken: $accessToken,
            responseClass: ApplicationInstallation::class,
            actionDescription: sprintf('get Buckaroo application "%s" installation "%s"', $id, $installationId),
        );

        return $installation;
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
     * Get invoice.
     *
     * @throws BuckarooAPIException
     */
    public function getInvoice(
        string $accessToken,
        string $id,
    ): Invoice {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo invoice request requires an id.');
        }

        /** @var Invoice $invoice */
        $invoice = $this->getHal(
            endpoint: sprintf('/v1/invoices/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Invoice::class,
            actionDescription: sprintf('get Buckaroo invoice "%s"', $id),
        );

        return $invoice;
    }

    /**
     * Get invoice attachment.
     *
     * @throws BuckarooAPIException
     */
    public function getInvoiceAttachment(
        string $accessToken,
        string $id,
    ): InvoiceAttachment {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo invoice attachment request requires an id.');
        }

        /** @var InvoiceAttachment $attachment */
        $attachment = $this->getHal(
            endpoint: sprintf('/v1/invoices/attachment/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: InvoiceAttachment::class,
            actionDescription: sprintf('get Buckaroo invoice attachment "%s"', $id),
        );

        return $attachment;
    }

    /**
     * Get credit note.
     *
     * @throws BuckarooAPIException
     */
    public function getInvoiceCreditNote(
        string $accessToken,
        string $id,
    ): InvoiceCreditNote {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo invoice credit note request requires an id.');
        }

        /** @var InvoiceCreditNote $creditNote */
        $creditNote = $this->getHal(
            endpoint: sprintf('/v1/invoices/creditnote/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: InvoiceCreditNote::class,
            actionDescription: sprintf('get Buckaroo invoice credit note "%s"', $id),
        );

        return $creditNote;
    }

    /**
     * Search invoices.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchInvoices(
        string $accessToken,
        array $filters = [],
    ): InvoiceSearchResult {
        $filters['limit'] ??= 100;

        /** @var InvoiceSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/invoices/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: InvoiceSearchResult::class,
            actionDescription: 'search Buckaroo invoices',
        );

        return $result;
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
     * Search payment method subscriptions.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchPaymentMethodSubscriptions(
        string $accessToken,
        array $filters = [],
    ): PaymentMethodSubscriptionSearchResult {
        /** @var PaymentMethodSubscriptionSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/paymentmethods/subscriptions/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: PaymentMethodSubscriptionSearchResult::class,
            actionDescription: 'search Buckaroo payment method subscriptions',
        );

        return $result;
    }

    /**
     * Get a specific payment method subscription by id.
     *
     * @throws BuckarooAPIException
     */
    public function getPaymentMethodSubscription(
        string $accessToken,
        string $id,
    ): PaymentMethodSubscription {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo payment method subscription request requires an id.');
        }

        /** @var PaymentMethodSubscription $subscription */
        $subscription = $this->getHal(
            endpoint: sprintf('/v1/paymentmethods/subscriptions/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: PaymentMethodSubscription::class,
            actionDescription: sprintf('get Buckaroo payment method subscription "%s"', $id),
        );

        return $subscription;
    }

    /**
     * Patch payment method subscription.
     *
     * @param array<string, mixed> $payload
     *
     * @throws BuckarooAPIException
     */
    public function updatePaymentMethodSubscription(
        string $accessToken,
        string $id,
        array $payload,
    ): PaymentMethodSubscription {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo payment method subscription update requires an id.');
        }

        $payload = $this->filterPayload($payload);

        if (($payload['action'] ?? null) === null || $payload['action'] === '') {
            throw new BuckarooAPIException('Buckaroo payment method subscription update requires an action.');
        }

        /** @var PaymentMethodSubscription $subscription */
        $subscription = $this->patchHal(
            endpoint: sprintf('/v1/paymentmethods/subscriptions/%s', rawurlencode($id)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: PaymentMethodSubscription::class,
            actionDescription: sprintf('update Buckaroo payment method subscription "%s"', $id),
        );

        return $subscription;
    }

    /**
     * Reprioritise payment method subscriptions.
     *
     * @param array<int, string> $orderedSubscriptionIds
     *
     * @throws BuckarooAPIException
     */
    public function reprioritisePaymentMethodSubscriptions(
        string $accessToken,
        string $code,
        array $orderedSubscriptionIds,
    ): PaymentMethodSubscriptionSearchResult {
        if ($code === '') {
            throw new BuckarooAPIException('Buckaroo payment method subscription reprioritise requires a code.');
        }

        if ($orderedSubscriptionIds === []) {
            throw new BuckarooAPIException('Buckaroo payment method subscription reprioritise requires orderedSubscriptionIds.');
        }

        /** @var PaymentMethodSubscriptionSearchResult $result */
        $result = $this->patchHal(
            endpoint: '/v1/paymentmethods/subscriptions/reprioritise',
            accessToken: $accessToken,
            payload: [
                'code' => $code,
                'orderedSubscriptionIds' => array_values($orderedSubscriptionIds),
            ],
            responseClass: PaymentMethodSubscriptionSearchResult::class,
            actionDescription: sprintf('reprioritise Buckaroo payment method subscriptions for "%s"', $code),
        );

        return $result;
    }

    /**
     * Search payouts.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchPayouts(
        string $accessToken,
        array $filters = [],
    ): PayoutSearchResult {
        $filters['limit'] ??= 100;

        /** @var PayoutSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/payouts/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: PayoutSearchResult::class,
            actionDescription: 'search Buckaroo payouts',
        );

        return $result;
    }

    /**
     * Get an existing payout.
     *
     * @throws BuckarooAPIException
     */
    public function getPayout(
        string $accessToken,
        string $id,
    ): Payout {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo payout request requires an id.');
        }

        /** @var Payout $payout */
        $payout = $this->getHal(
            endpoint: sprintf('/v1/payouts/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Payout::class,
            actionDescription: sprintf('get Buckaroo payout "%s"', $id),
        );

        return $payout;
    }

    /**
     * Search filtered POS terminals.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchTerminals(
        string $accessToken,
        array $filters = [],
    ): TerminalSearchResult {
        $filters['limit'] ??= 100;

        /** @var TerminalSearchResult $terminals */
        $terminals = $this->postHalSearch(
            endpoint: '/v1/pos/terminals/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: TerminalSearchResult::class,
            actionDescription: 'search Buckaroo POS terminals',
        );

        return $terminals;
    }

    /**
     * Get an existing smart terminal.
     *
     * @throws BuckarooAPIException
     */
    public function getSmartTerminal(
        string $accessToken,
        string $terminalId,
    ): SmartTerminal {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal request requires a terminalId.');
        }

        /** @var SmartTerminal $terminal */
        $terminal = $this->getHal(
            endpoint: sprintf('/v1/pos/terminals/smart/%s', rawurlencode($terminalId)),
            accessToken: $accessToken,
            responseClass: SmartTerminal::class,
            actionDescription: sprintf('get Buckaroo smart terminal "%s"', $terminalId),
        );

        return $terminal;
    }

    /**
     * Update an existing smart terminal.
     *
     * @param array<string, mixed> $terminal
     *
     * @throws BuckarooAPIException
     */
    public function updateSmartTerminal(
        string $accessToken,
        string $terminalId,
        array $terminal,
    ): SmartTerminal {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal update requires a terminalId.');
        }

        $payload = $this->filterPayload($terminal);

        /** @var SmartTerminal $updatedTerminal */
        $updatedTerminal = $this->patchHal(
            endpoint: sprintf('/v1/pos/terminals/smart/%s', rawurlencode($terminalId)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: SmartTerminal::class,
            actionDescription: sprintf('update Buckaroo smart terminal "%s"', $terminalId),
        );

        return $updatedTerminal;
    }

    /**
     * Cancel the current action of a smart terminal.
     *
     * @throws BuckarooAPIException
     */
    public function cancelSmartTerminalAction(
        string $accessToken,
        string $terminalId,
    ): void {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal cancel requires a terminalId.');
        }

        $this->postHalNoContent(
            endpoint: sprintf('/v1/pos/terminals/smart/%s/cancel', rawurlencode($terminalId)),
            accessToken: $accessToken,
            actionDescription: sprintf('cancel current action for Buckaroo smart terminal "%s"', $terminalId),
        );
    }

    /**
     * Update an existing smart terminal MDM/app settings.
     *
     * @param array<string, mixed> $settings
     *
     * @throws BuckarooAPIException
     */
    public function updateSmartTerminalMdmSettings(
        string $accessToken,
        string $terminalId,
        array $settings,
    ): SmartTerminalMdmSettings {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal MDM update requires a terminalId.');
        }

        $payload = $this->filterPayload($settings);

        /** @var SmartTerminalMdmSettings $updatedSettings */
        $updatedSettings = $this->patchHal(
            endpoint: sprintf('/v1/pos/terminals/smart/%s/mdm', rawurlencode($terminalId)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: SmartTerminalMdmSettings::class,
            actionDescription: sprintf('update Buckaroo smart terminal "%s" MDM settings', $terminalId),
        );

        return $updatedSettings;
    }

    /**
     * Get the MDM and App settings for a smart terminal.
     *
     * @throws BuckarooAPIException
     */
    public function getSmartTerminalMdmSettings(
        string $accessToken,
        string $terminalId,
    ): SmartTerminalMdmSettings {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal MDM settings request requires a terminalId.');
        }

        /** @var SmartTerminalMdmSettings $settings */
        $settings = $this->getHal(
            endpoint: sprintf('/v1/pos/terminals/smart/%s/mdm', rawurlencode($terminalId)),
            accessToken: $accessToken,
            responseClass: SmartTerminalMdmSettings::class,
            actionDescription: sprintf('get Buckaroo smart terminal "%s" MDM settings', $terminalId),
        );

        return $settings;
    }

    /**
     * Reboot a smart terminal.
     *
     * @throws BuckarooAPIException
     */
    public function rebootSmartTerminal(
        string $accessToken,
        string $terminalId,
    ): void {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal reboot requires a terminalId.');
        }

        $this->postHalNoContent(
            endpoint: sprintf('/v1/pos/terminals/smart/%s/reboot', rawurlencode($terminalId)),
            accessToken: $accessToken,
            actionDescription: sprintf('reboot Buckaroo smart terminal "%s"', $terminalId),
        );
    }

    /**
     * Get the connection status of a smart terminal.
     *
     * @throws BuckarooAPIException
     */
    public function getSmartTerminalConnectionStatus(
        string $accessToken,
        string $terminalId,
    ): SmartTerminalConnectionStatus {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo smart terminal status request requires a terminalId.');
        }

        /** @var SmartTerminalConnectionStatus $status */
        $status = $this->getHal(
            endpoint: sprintf('/v1/pos/terminals/smart/%s/status', rawurlencode($terminalId)),
            accessToken: $accessToken,
            responseClass: SmartTerminalConnectionStatus::class,
            actionDescription: sprintf('get Buckaroo smart terminal "%s" connection status', $terminalId),
        );

        return $status;
    }

    /**
     * Get an existing internal terminal.
     *
     * @throws BuckarooAPIException
     */
    public function getInternalTerminal(
        string $accessToken,
        string $terminalId,
    ): InternalTerminal {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo internal terminal request requires a terminalId.');
        }

        /** @var InternalTerminal $terminal */
        $terminal = $this->getHal(
            endpoint: sprintf('/v1/pos/terminals/internal/%s', rawurlencode($terminalId)),
            accessToken: $accessToken,
            responseClass: InternalTerminal::class,
            actionDescription: sprintf('get Buckaroo internal terminal "%s"', $terminalId),
        );

        return $terminal;
    }

    /**
     * Update an existing internal terminal.
     *
     * @param array<string, mixed> $terminal
     *
     * @throws BuckarooAPIException
     */
    public function updateInternalTerminal(
        string $accessToken,
        string $terminalId,
        array $terminal,
    ): InternalTerminal {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo internal terminal update requires a terminalId.');
        }

        $payload = $this->filterPayload($terminal);

        /** @var InternalTerminal $updatedTerminal */
        $updatedTerminal = $this->patchHal(
            endpoint: sprintf('/v1/pos/terminals/internal/%s', rawurlencode($terminalId)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: InternalTerminal::class,
            actionDescription: sprintf('update Buckaroo internal terminal "%s"', $terminalId),
        );

        return $updatedTerminal;
    }

    /**
     * Get the connection status of an internal terminal.
     *
     * @throws BuckarooAPIException
     */
    public function getInternalTerminalConnectionStatus(
        string $accessToken,
        string $terminalId,
    ): InternalTerminalConnectionStatus {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo internal terminal status request requires a terminalId.');
        }

        /** @var InternalTerminalConnectionStatus $status */
        $status = $this->getHal(
            endpoint: sprintf('/v1/pos/terminals/internal/%s/status', rawurlencode($terminalId)),
            accessToken: $accessToken,
            responseClass: InternalTerminalConnectionStatus::class,
            actionDescription: sprintf('get Buckaroo internal terminal "%s" connection status', $terminalId),
        );

        return $status;
    }

    /**
     * Cancel the current WECR transaction of an internal terminal.
     *
     * @throws BuckarooAPIException
     */
    public function cancelInternalTerminalAction(
        string $accessToken,
        string $terminalId,
    ): void {
        if ($terminalId === '') {
            throw new BuckarooAPIException('Buckaroo internal terminal cancel requires a terminalId.');
        }

        $this->postHalNoContent(
            endpoint: sprintf('/v1/pos/terminals/internal/%s/cancel', rawurlencode($terminalId)),
            accessToken: $accessToken,
            actionDescription: sprintf('cancel current WECR transaction for Buckaroo internal terminal "%s"', $terminalId),
        );
    }

    /**
     * Create a sale.
     *
     * @param array<string, mixed> $sale
     *
     * @throws BuckarooAPIException
     */
    public function createSale(
        string $accessToken,
        array $sale,
    ): Sale {
        $payload = $this->filterPayload($sale);

        foreach (['reference', 'currency', 'totalAmount', 'sequenceType', 'intentType'] as $requiredField) {
            if (($payload[$requiredField] ?? null) === null || $payload[$requiredField] === '') {
                throw new BuckarooAPIException(sprintf('Buckaroo sale payload requires "%s".', $requiredField));
            }
        }

        /** @var Sale $createdSale */
        $createdSale = $this->postHalSearch(
            endpoint: '/v1/sales',
            accessToken: $accessToken,
            filters: $payload,
            responseClass: Sale::class,
            actionDescription: 'create Buckaroo sale',
        );

        return $createdSale;
    }

    /**
     * Search sales.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchSales(
        string $accessToken,
        array $filters = [],
    ): SaleSearchResult {
        $filters['limit'] ??= 100;

        /** @var SaleSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/sales/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: SaleSearchResult::class,
            actionDescription: 'search Buckaroo sales',
        );

        return $result;
    }

    /**
     * Search Buckaroo sales transactions.
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
     * Retrieve a transaction.
     *
     * @throws BuckarooAPIException
     */
    public function getTransaction(
        string $accessToken,
        string $id,
    ): Transaction {
        if ($id === '') {
            throw new BuckarooAPIException('Buckaroo transaction request requires an id.');
        }

        /** @var Transaction $transaction */
        $transaction = $this->getHal(
            endpoint: sprintf('/v1/sales/transactions/%s', rawurlencode($id)),
            accessToken: $accessToken,
            responseClass: Transaction::class,
            actionDescription: sprintf('get Buckaroo transaction "%s"', $id),
        );

        return $transaction;
    }

    /**
     * Retrieve a sale.
     *
     * @throws BuckarooAPIException
     */
    public function getSale(
        string $accessToken,
        string $saleId,
    ): Sale {
        if ($saleId === '') {
            throw new BuckarooAPIException('Buckaroo sale request requires a saleId.');
        }

        /** @var Sale $sale */
        $sale = $this->getHal(
            endpoint: sprintf('/v1/sales/%s', rawurlencode($saleId)),
            accessToken: $accessToken,
            responseClass: Sale::class,
            actionDescription: sprintf('get Buckaroo sale "%s"', $saleId),
        );

        return $sale;
    }

    /**
     * Cancel a sale.
     *
     * @throws BuckarooAPIException
     */
    public function cancelSale(
        string $accessToken,
        string $saleId,
    ): Sale {
        if ($saleId === '') {
            throw new BuckarooAPIException('Buckaroo sale cancel request requires a saleId.');
        }

        /** @var Sale $sale */
        $sale = $this->postHalSearch(
            endpoint: sprintf('/v1/sales/%s/cancel', rawurlencode($saleId)),
            accessToken: $accessToken,
            filters: [],
            responseClass: Sale::class,
            actionDescription: sprintf('cancel Buckaroo sale "%s"', $saleId),
        );

        return $sale;
    }

    /**
     * Retrieve a sale by reference.
     *
     * @throws BuckarooAPIException
     */
    public function getSaleByReference(
        string $accessToken,
        string $reference,
    ): Sale {
        if ($reference === '') {
            throw new BuckarooAPIException('Buckaroo sale reference request requires a reference.');
        }

        /** @var Sale $sale */
        $sale = $this->getHal(
            endpoint: sprintf('/v1/sales/reference/%s', rawurlencode($reference)),
            accessToken: $accessToken,
            responseClass: Sale::class,
            actionDescription: sprintf('get Buckaroo sale by reference "%s"', $reference),
        );

        return $sale;
    }

    /**
     * Global search across Buckaroo resources.
     *
     * @throws BuckarooAPIException
     */
    public function search(
        string $accessToken,
        string $needle,
        ?string $resourceType = null,
        int $limit = 100,
    ): GlobalSearchResult {
        if ($needle === '') {
            throw new BuckarooAPIException('Buckaroo global search requires a needle.');
        }

        /** @var GlobalSearchResult $result */
        $result = $this->postHalSearch(
            endpoint: '/v1/search',
            accessToken: $accessToken,
            filters: [
                'resourceType' => $resourceType,
                'needle' => $needle,
                'limit' => $limit,
            ],
            responseClass: GlobalSearchResult::class,
            actionDescription: 'perform Buckaroo global search',
        );

        return $result;
    }

    /**
     * Get stores.
     *
     * @throws BuckarooAPIException
     */
    public function getStores(
        string $accessToken,
        ?string $status = null,
        ?string $continuationToken = null,
    ): StoreSearchResult {
        /** @var StoreSearchResult $stores */
        $stores = $this->getHal(
            endpoint: '/v1/stores',
            accessToken: $accessToken,
            responseClass: StoreSearchResult::class,
            actionDescription: 'get Buckaroo stores',
            query: [
                'status' => $status,
                'continuationToken' => $continuationToken,
            ],
        );

        return $stores;
    }

    /**
     * Add a new store.
     *
     * @param array<string, mixed> $store
     *
     * @throws BuckarooAPIException
     */
    public function createStore(
        string $accessToken,
        array $store,
    ): Store {
        $payload = $this->filterPayload($store);

        if (($payload['type'] ?? null) === null || $payload['type'] === '') {
            throw new BuckarooAPIException('Buckaroo store payload requires a type.');
        }

        if (($payload['name'] ?? null) === null || $payload['name'] === '') {
            throw new BuckarooAPIException('Buckaroo store payload requires a name.');
        }

        /** @var Store $createdStore */
        $createdStore = $this->postHalSearch(
            endpoint: '/v1/stores',
            accessToken: $accessToken,
            filters: $payload,
            responseClass: Store::class,
            actionDescription: 'create Buckaroo store',
        );

        return $createdStore;
    }

    /**
     * Search stores.
     *
     * @param array<string, mixed> $filters
     *
     * @throws BuckarooAPIException
     */
    public function searchStores(
        string $accessToken,
        array $filters = [],
    ): StoreSearchResult {
        /** @var StoreSearchResult $stores */
        $stores = $this->postHalSearch(
            endpoint: '/v1/stores/search',
            accessToken: $accessToken,
            filters: $filters,
            responseClass: StoreSearchResult::class,
            actionDescription: 'search Buckaroo stores',
        );

        return $stores;
    }

    /**
     * Update a store.
     *
     * @param array<string, mixed> $store
     *
     * @throws BuckarooAPIException
     */
    public function updateStore(
        string $accessToken,
        string $storeId,
        array $store,
    ): Store {
        $payload = $this->filterPayload($store);

        if ($storeId === '') {
            throw new BuckarooAPIException('Buckaroo store update requires a storeId.');
        }

        /** @var Store $updatedStore */
        $updatedStore = $this->patchHal(
            endpoint: sprintf('/v1/stores/%s', rawurlencode($storeId)),
            accessToken: $accessToken,
            payload: $payload,
            responseClass: Store::class,
            actionDescription: sprintf('update Buckaroo store "%s"', $storeId),
        );

        return $updatedStore;
    }

    /**
     * POST HAL endpoint without response payload.
     *
     * @throws BuckarooAPIException
     */
    private function postHalNoContent(
        string $endpoint,
        string $accessToken,
        string $actionDescription,
    ): void {
        $response = $this->requestHal(
            method: 'POST',
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
