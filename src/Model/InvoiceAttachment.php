<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\InvoiceAttachmentType;

final readonly class InvoiceAttachment
{
    /**
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public InvoiceAttachmentType|string|null $type = null,
        public string $filename = '',
        public ?string $url = null,
        public ?string $createdAt = null,
        public array $links = [],
    ) {
    }
}