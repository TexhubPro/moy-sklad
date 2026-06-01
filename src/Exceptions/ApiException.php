<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Exceptions;

/**
 * Thrown when the MoySklad API returns an error.
 *
 * Error payload: `{ "errors": [ { "error", "code", "moreInfo", "parameter" } ] }`.
 */
class ApiException extends MoySkladException
{
    /**
     * @param array<int, array<string, mixed>> $errors  The `errors` array.
     * @param array<string, mixed>             $payload Full decoded response body.
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly array $errors = [],
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $httpStatus);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromResponse(int $httpStatus, array $body): self
    {
        $errors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $message = $errors[0]['error'] ?? ('MoySklad API error (HTTP ' . $httpStatus . ')');

        return new self((string) $message, $httpStatus, $errors, $body);
    }

    /** The MoySklad error code of the first error, if any. */
    public function errorCode(): ?int
    {
        return isset($this->errors[0]['code']) ? (int) $this->errors[0]['code'] : null;
    }

    public function isUnauthorized(): bool
    {
        return $this->httpStatus === 401;
    }

    public function isRateLimit(): bool
    {
        return $this->httpStatus === 429;
    }
}
