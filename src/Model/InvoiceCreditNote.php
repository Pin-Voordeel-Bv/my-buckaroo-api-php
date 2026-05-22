<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\InvoiceProcessingStatus;

final readonly class InvoiceCreditNote
{
    /**
     * @param array<int, InvoiceAttachment|array<string, mixed>> $attachments
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $invoiceNumber = '',
        public string $parentInvoiceNumber = '',
        public ?string $invoiceKey = null,
        public string $currency = '',
        public string $grossAmount = '',
        public string $vatAmount = '',
        public string $netAmount = '',
        public InvoiceProcessingStatus|string|null $processingStatus = null,
        public string $createdAt = '',
        public array $attachments = [],
        public array $links = [],
    ) {
    }
}