<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class AccessToken
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public int $createdAt,
    ) {
    }

    /**
     * @param array{access_token:string, token_type?:string, expires_in?:int|string} $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            accessToken: $payload['access_token'],
            tokenType: (string) ($payload['token_type'] ?? 'Bearer'),
            expiresIn: (int) ($payload['expires_in'] ?? 0),
            createdAt: time(),
        );
    }

    public function authorizationHeader(): string
    {
        return sprintf('%s %s', $this->tokenType, $this->accessToken);
    }

    public function expiresAt(): int
    {
        return $this->createdAt + $this->expiresIn;
    }

    public function isExpired(int $leewaySeconds = 60): bool
    {
        return time() >= ($this->expiresAt() - $leewaySeconds);
    }
}
