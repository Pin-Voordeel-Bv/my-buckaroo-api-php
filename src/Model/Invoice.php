<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\InvoicePaymentStatus;

final readonly class Invoice
{
    /**
     * @param array<int, InvoiceAttachment|array<string, mixed>>|null $attachments
     * @param array<int, InvoiceCreditNote|array<string, mixed>>|null $creditNotes
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $invoiceNumber = '',
        public ?string $invoiceDate = null,
        public ?string $dueDate = null,
        public string $currency = '',
        public string $grossAmount = '',
        public string $vatAmount = '',
        public string $netAmount = '',
        public string $dueAmount = '',
        public ?array $attachments = null,
        public ?array $creditNotes = null,
        public InvoicePaymentStatus|string|null $paymentStatus = null,
        public ?string $createdAt = null,
        public array $links = [],
    ) {
    }
}