<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI;

use GuzzleHttp\Client;
use PinVandaag\BuckarooAPI\Client\APIClient;
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
use PinVandaag\BuckarooAPI\Model\Report;
use PinVandaag\BuckarooAPI\Model\ReportDefinitionList;
use PinVandaag\BuckarooAPI\Model\ReportSchedule;
use PinVandaag\BuckarooAPI\Model\ReportScheduleSearchResult;
use PinVandaag\BuckarooAPI\Model\ReportSearchResult;
use PinVandaag\BuckarooAPI\Model\Sale;
use PinVandaag\BuckarooAPI\Model\SaleSearchResult;
use PinVandaag\BuckarooAPI\Model\ServiceSubscription;
use PinVandaag\BuckarooAPI\Model\ServiceSubscriptionSearchResult;
use PinVandaag\BuckarooAPI\Model\Store;
use PinVandaag\BuckarooAPI\Model\StoreSearchResult;
use PinVandaag\BuckarooAPI\Model\SmartTerminal;
use PinVandaag\BuckarooAPI\Model\SmartTerminalConnectionStatus;
use PinVandaag\BuckarooAPI\Model\SmartTerminalMdmSettings;
use PinVandaag\BuckarooAPI\Model\TerminalSearchResult;
use PinVandaag\BuckarooAPI\Model\Transaction;
use PinVandaag\BuckarooAPI\Model\TransactionSearchResult;
use PinVandaag\BuckarooAPI\Model\Webhook;
use PinVandaag\BuckarooAPI\Model\WebhookEventTypeList;
use PinVandaag\BuckarooAPI\Model\WebhookSearchResult;
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
     * Get all accounts.
     */
    public function getAccounts(string $accessToken): AccountSearchResult
    {
        return $this->apiClient->getAccounts($accessToken);
    }

    /**
     * Get an account by id.
     */
    public function getAccount(
        string $accessToken,
        string $id,
    ): Account {
        return $this->apiClient->getAccount($accessToken, $id);
    }

    /**
     * Update the payout settings of an account.
     *
     * @param array<string, mixed> $payload
     */
    public function updateAccountPayoutSettings(
        string $accessToken,
        string $id,
        array $payload,
    ): AccountPayoutSettings {
        return $this->apiClient->updateAccountPayoutSettings($accessToken, $id, $payload);
    }

    /**
     * Search applications.
     *
     * @param array<string, mixed> $filters
     */
    public function searchApplications(
        string $accessToken,
        array $filters = [],
    ): ApplicationSearchResult {
        return $this->apiClient->searchApplications($accessToken, $filters);
    }

    /**
     * Get application by id.
     */
    public function getApplication(
        string $accessToken,
        string $id,
    ): Application {
        return $this->apiClient->getApplication($accessToken, $id);
    }

    /**
     * Create application.
     *
     * @param array<string, mixed> $application
     */
    public function createApplication(
        string $accessToken,
        array $application,
    ): Application {
        return $this->apiClient->createApplication($accessToken, $application);
    }

    /**
     * Get installations for an application.
     *
     * @param array<string, mixed> $filters
     */
    public function searchApplicationInstallations(
        string $accessToken,
        string $id,
        array $filters = [],
    ): ApplicationInstallationSearchResult {
        return $this->apiClient->searchApplicationInstallations($accessToken, $id, $filters);
    }

    /**
     * Get installation for an application.
     */
    public function getApplicationInstallation(
        string $accessToken,
        string $id,
        string $installationId,
    ): ApplicationInstallation {
        return $this->apiClient->getApplicationInstallation($accessToken, $id, $installationId);
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
     * Get an existing customer.
     */
    public function getCustomer(
        string $accessToken,
        string $id,
    ): Customer {
        return $this->apiClient->getCustomer($accessToken, $id);
    }

    /**
     * Delete an existing customer.
     */
    public function deleteCustomer(
        string $accessToken,
        string $id,
    ): void {
        $this->apiClient->deleteCustomer($accessToken, $id);
    }

    /**
     * Get application installations.
     *
     * @param array<string, mixed> $filters
     */
    public function searchInstallations(
        string $accessToken,
        array $filters = [],
    ): ApplicationInstallationSearchResult {
        return $this->apiClient->searchInstallations($accessToken, $filters);
    }

    /**
     * Get installation.
     */
    public function getInstallation(
        string $accessToken,
        string $id,
    ): ApplicationInstallation {
        return $this->apiClient->getInstallation($accessToken, $id);
    }

    /**
     * Update installation.
     *
     * @param array<string, mixed> $payload
     */
    public function updateInstallation(
        string $accessToken,
        string $id,
        array $payload,
    ): ApplicationInstallation {
        return $this->apiClient->updateInstallation($accessToken, $id, $payload);
    }

    /**
     * Get invoice.
     */
    public function getInvoice(
        string $accessToken,
        string $id,
    ): Invoice {
        return $this->apiClient->getInvoice($accessToken, $id);
    }

    /**
     * Get invoice attachment.
     */
    public function getInvoiceAttachment(
        string $accessToken,
        string $id,
    ): InvoiceAttachment {
        return $this->apiClient->getInvoiceAttachment($accessToken, $id);
    }

    /**
     * Get credit note.
     */
    public function getInvoiceCreditNote(
        string $accessToken,
        string $id,
    ): InvoiceCreditNote {
        return $this->apiClient->getInvoiceCreditNote($accessToken, $id);
    }

    /**
     * Search invoices.
     *
     * @param array<string, mixed> $filters
     */
    public function searchInvoices(
        string $accessToken,
        array $filters = [],
    ): InvoiceSearchResult {
        return $this->apiClient->searchInvoices($accessToken, $filters);
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
     * Search payment method subscriptions.
     *
     * @param array<string, mixed> $filters
     */
    public function searchPaymentMethodSubscriptions(
        string $accessToken,
        array $filters = [],
    ): PaymentMethodSubscriptionSearchResult {
        return $this->apiClient->searchPaymentMethodSubscriptions($accessToken, $filters);
    }

    /**
     * Get a specific payment method subscription by id.
     */
    public function getPaymentMethodSubscription(
        string $accessToken,
        string $id,
    ): PaymentMethodSubscription {
        return $this->apiClient->getPaymentMethodSubscription($accessToken, $id);
    }

    /**
     * Patch payment method subscription.
     *
     * @param array<string, mixed> $payload
     */
    public function updatePaymentMethodSubscription(
        string $accessToken,
        string $id,
        array $payload,
    ): PaymentMethodSubscription {
        return $this->apiClient->updatePaymentMethodSubscription($accessToken, $id, $payload);
    }

    /**
     * Reprioritise payment method subscriptions.
     *
     * @param array<int, string> $orderedSubscriptionIds
     */
    public function reprioritisePaymentMethodSubscriptions(
        string $accessToken,
        string $code,
        array $orderedSubscriptionIds,
    ): PaymentMethodSubscriptionSearchResult {
        return $this->apiClient->reprioritisePaymentMethodSubscriptions($accessToken, $code, $orderedSubscriptionIds);
    }

    /**
     * Search payouts.
     *
     * @param array<string, mixed> $filters
     */
    public function searchPayouts(
        string $accessToken,
        array $filters = [],
    ): PayoutSearchResult {
        return $this->apiClient->searchPayouts($accessToken, $filters);
    }

    /**
     * Get an existing payout.
     */
    public function getPayout(
        string $accessToken,
        string $id,
    ): Payout {
        return $this->apiClient->getPayout($accessToken, $id);
    }

    /**
     * Search filtered POS terminals.
     *
     * @param array<string, mixed> $filters
     */
    public function searchTerminals(
        string $accessToken,
        array $filters = [],
    ): TerminalSearchResult {
        return $this->apiClient->searchTerminals($accessToken, $filters);
    }

    /**
     * Get an existing smart terminal.
     */
    public function getSmartTerminal(
        string $accessToken,
        string $terminalId,
    ): SmartTerminal {
        return $this->apiClient->getSmartTerminal($accessToken, $terminalId);
    }

    /**
     * Update an existing smart terminal.
     *
     * @param array<string, mixed> $terminal
     */
    public function updateSmartTerminal(
        string $accessToken,
        string $terminalId,
        array $terminal,
    ): SmartTerminal {
        return $this->apiClient->updateSmartTerminal($accessToken, $terminalId, $terminal);
    }

    /**
     * Cancel the current action of a smart terminal.
     */
    public function cancelSmartTerminalAction(
        string $accessToken,
        string $terminalId,
    ): void {
        $this->apiClient->cancelSmartTerminalAction($accessToken, $terminalId);
    }

    /**
     * Update an existing smart terminal MDM/app settings.
     *
     * @param array<string, mixed> $settings
     */
    public function updateSmartTerminalMdmSettings(
        string $accessToken,
        string $terminalId,
        array $settings,
    ): SmartTerminalMdmSettings {
        return $this->apiClient->updateSmartTerminalMdmSettings($accessToken, $terminalId, $settings);
    }

    /**
     * Get the MDM and App settings for a smart terminal.
     */
    public function getSmartTerminalMdmSettings(
        string $accessToken,
        string $terminalId,
    ): SmartTerminalMdmSettings {
        return $this->apiClient->getSmartTerminalMdmSettings($accessToken, $terminalId);
    }

    /**
     * Reboot a smart terminal.
     */
    public function rebootSmartTerminal(
        string $accessToken,
        string $terminalId,
    ): void {
        $this->apiClient->rebootSmartTerminal($accessToken, $terminalId);
    }

    /**
     * Get the connection status of a smart terminal.
     */
    public function getSmartTerminalConnectionStatus(
        string $accessToken,
        string $terminalId,
    ): SmartTerminalConnectionStatus {
        return $this->apiClient->getSmartTerminalConnectionStatus($accessToken, $terminalId);
    }

    /**
     * Get an existing internal terminal.
     */
    public function getInternalTerminal(
        string $accessToken,
        string $terminalId,
    ): InternalTerminal {
        return $this->apiClient->getInternalTerminal($accessToken, $terminalId);
    }

    /**
     * Update an existing internal terminal.
     *
     * @param array<string, mixed> $terminal
     */
    public function updateInternalTerminal(
        string $accessToken,
        string $terminalId,
        array $terminal,
    ): InternalTerminal {
        return $this->apiClient->updateInternalTerminal($accessToken, $terminalId, $terminal);
    }

    /**
     * Get the connection status of an internal terminal.
     */
    public function getInternalTerminalConnectionStatus(
        string $accessToken,
        string $terminalId,
    ): InternalTerminalConnectionStatus {
        return $this->apiClient->getInternalTerminalConnectionStatus($accessToken, $terminalId);
    }

    /**
     * Cancel the current WECR transaction of an internal terminal.
     */
    public function cancelInternalTerminalAction(
        string $accessToken,
        string $terminalId,
    ): void {
        $this->apiClient->cancelInternalTerminalAction($accessToken, $terminalId);
    }

    /**
     * Create a report.
     *
     * @param array<string, mixed> $report
     */
    public function createReport(
        string $accessToken,
        array $report,
    ): Report {
        return $this->apiClient->createReport($accessToken, $report);
    }

    /**
     * Get a report.
     */
    public function getReport(
        string $accessToken,
        string $id,
    ): Report {
        return $this->apiClient->getReport($accessToken, $id);
    }

    /**
     * Search filtered reports.
     *
     * @param array<string, mixed> $filters
     */
    public function searchReports(
        string $accessToken,
        array $filters = [],
    ): ReportSearchResult {
        return $this->apiClient->searchReports($accessToken, $filters);
    }

    /**
     * Get report definitions.
     */
    public function getReportDefinitions(
        string $accessToken,
    ): ReportDefinitionList {
        return $this->apiClient->getReportDefinitions($accessToken);
    }

    /**
     * Search report definitions.
     */
    public function searchReportDefinitions(
        string $accessToken,
    ): ReportDefinitionList {
        return $this->apiClient->searchReportDefinitions($accessToken);
    }

    /**
     * Create a report schedule.
     *
     * @param array<string, mixed> $schedule
     */
    public function createReportSchedule(
        string $accessToken,
        array $schedule,
    ): ReportSchedule {
        return $this->apiClient->createReportSchedule($accessToken, $schedule);
    }

    /**
     * Update a report schedule.
     *
     * @param array<string, mixed> $schedule
     */
    public function updateReportSchedule(
        string $accessToken,
        string $id,
        array $schedule,
    ): ReportSchedule {
        return $this->apiClient->updateReportSchedule($accessToken, $id, $schedule);
    }

    /**
     * Get a report schedule.
     */
    public function getReportSchedule(
        string $accessToken,
        string $id,
    ): ReportSchedule {
        return $this->apiClient->getReportSchedule($accessToken, $id);
    }

    /**
     * Delete a report schedule.
     */
    public function deleteReportSchedule(
        string $accessToken,
        string $id,
    ): void {
        $this->apiClient->deleteReportSchedule($accessToken, $id);
    }

    /**
     * Search report schedules.
     *
     * @param array<string, mixed> $filters
     */
    public function searchReportSchedules(
        string $accessToken,
        array $filters = [],
    ): ReportScheduleSearchResult {
        return $this->apiClient->searchReportSchedules($accessToken, $filters);
    }

    /**
     * Create a sale.
     *
     * @param array<string, mixed> $sale
     */
    public function createSale(
        string $accessToken,
        array $sale,
    ): Sale {
        return $this->apiClient->createSale($accessToken, $sale);
    }

    /**
     * Search sales.
     *
     * @param array<string, mixed> $filters
     */
    public function searchSales(
        string $accessToken,
        array $filters = [],
    ): SaleSearchResult {
        return $this->apiClient->searchSales($accessToken, $filters);
    }

    /**
     * Search sales transactions.
     *
     * @param array<string, mixed> $filters
     */
    public function searchTransactions(
        string $accessToken,
        array $filters = [],
    ): TransactionSearchResult {
        return $this->apiClient->searchTransactions($accessToken, $filters);
    }

    /**
     * Retrieve a transaction.
     */
    public function getTransaction(
        string $accessToken,
        string $id,
    ): Transaction {
        return $this->apiClient->getTransaction($accessToken, $id);
    }

    /**
     * Retrieve a sale.
     */
    public function getSale(
        string $accessToken,
        string $saleId,
    ): Sale {
        return $this->apiClient->getSale($accessToken, $saleId);
    }

    /**
     * Cancel a sale.
     */
    public function cancelSale(
        string $accessToken,
        string $saleId,
    ): Sale {
        return $this->apiClient->cancelSale($accessToken, $saleId);
    }

    /**
     * Retrieve a sale by reference.
     */
    public function getSaleByReference(
        string $accessToken,
        string $reference,
    ): Sale {
        return $this->apiClient->getSaleByReference($accessToken, $reference);
    }

    /**
     * Global search across Buckaroo resources.
     */
    public function search(
        string $accessToken,
        string $needle,
        ?string $resourceType = null,
        int $limit = 100,
    ): GlobalSearchResult {
        return $this->apiClient->search($accessToken, $needle, $resourceType, $limit);
    }

    /**
     * Search service subscriptions.
     *
     * @param array<string, mixed> $filters
     */
    public function searchServiceSubscriptions(
        string $accessToken,
        array $filters = [],
    ): ServiceSubscriptionSearchResult {
        return $this->apiClient->searchServiceSubscriptions($accessToken, $filters);
    }

    /**
     * Get a specific service subscription by id.
     */
    public function getServiceSubscription(
        string $accessToken,
        string $id,
    ): ServiceSubscription {
        return $this->apiClient->getServiceSubscription($accessToken, $id);
    }

    /**
     * Activate or deactivate service subscription.
     */
    public function updateServiceSubscription(
        string $accessToken,
        string $id,
        string $action,
    ): ServiceSubscription {
        return $this->apiClient->updateServiceSubscription($accessToken, $id, $action);
    }

    /**
     * Reprioritise service subscriptions.
     *
     * @param array<int, string> $orderedSubscriptionIds
     */
    public function reprioritiseServiceSubscriptions(
        string $accessToken,
        string $code,
        array $orderedSubscriptionIds,
    ): ServiceSubscriptionSearchResult {
        return $this->apiClient->reprioritiseServiceSubscriptions($accessToken, $code, $orderedSubscriptionIds);
    }

    /**
     * Get stores.
     */
    public function getStores(
        string $accessToken,
        ?string $status = null,
        ?string $continuationToken = null,
    ): StoreSearchResult {
        return $this->apiClient->getStores($accessToken, $status, $continuationToken);
    }
 
    /**
     * Search stores.
     *
     * @param array<string, mixed> $filters
     */
    public function searchStores(
        string $accessToken,
        array $filters = [],
    ): StoreSearchResult {
        return $this->apiClient->searchStores($accessToken, $filters);
    }

    /**
     * Add a new store.
     *
     * @param array<string, mixed> $store
     */
    public function createStore(
        string $accessToken,
        array $store,
    ): Store {
        return $this->apiClient->createStore($accessToken, $store);
    }
 
    /**
     * Update a store.
     *
     * @param array<string, mixed> $store
     */
    public function updateStore(
        string $accessToken,
        string $storeId,
        array $store,
    ): Store {
        return $this->apiClient->updateStore($accessToken, $storeId, $store);
    }

    /**
     * List webhook event types.
     */
    public function getWebhookEventTypes(
        string $accessToken,
    ): WebhookEventTypeList {
        return $this->apiClient->getWebhookEventTypes($accessToken);
    }

    /**
     * Search filtered webhook configurations.
     *
     * @param array<string, mixed> $filters
     */
    public function searchWebhooks(
        string $accessToken,
        array $filters = [],
    ): WebhookSearchResult {
        return $this->apiClient->searchWebhooks($accessToken, $filters);
    }

    /**
     * Add a new webhook configuration.
     *
     * @param array<string, mixed> $webhook
     */
    public function createWebhook(
        string $accessToken,
        array $webhook,
    ): Webhook {
        return $this->apiClient->createWebhook($accessToken, $webhook);
    }

    /**
     * Get an existing webhook configuration.
     */
    public function getWebhook(
        string $accessToken,
        string $id,
    ): Webhook {
        return $this->apiClient->getWebhook($accessToken, $id);
    }

    /**
     * Update an existing webhook configuration.
     *
     * @param array<string, mixed> $payload
     */
    public function updateWebhook(
        string $accessToken,
        string $id,
        array $payload,
    ): Webhook {
        return $this->apiClient->updateWebhook($accessToken, $id, $payload);
    }

    /**
     * Delete an existing webhook configuration.
     */
    public function deleteWebhook(
        string $accessToken,
        string $id,
    ): void {
        $this->apiClient->deleteWebhook($accessToken, $id);
    }
}
