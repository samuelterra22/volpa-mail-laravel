<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Base exception for the Volpa Mail package.
 *
 * Thrown when the configuration is invalid (missing API key, missing required
 * field) or when the API responds with an error (4xx/5xx). Carries the HTTP
 * status and the validation errors returned by the API.
 */
class VolpaMailException extends RuntimeException
{
    /**
     * Validation errors returned by the API, shaped as `field => [messages]`.
     *
     * @var array<string, mixed>
     */
    public array $errors = [];

    /** HTTP status associated with the error, when it originated from a response. */
    public ?int $status = null;

    /** Machine-readable error code from the API (e.g. `sender_not_found`), when present. */
    public ?string $errorCode = null;

    /** Seconds to wait before retrying, parsed from the `Retry-After` header on 429 responses. */
    public ?int $retryAfter = null;

    /**
     * Build the exception from a failed HTTP response.
     *
     * Handles both API error envelopes:
     *  - business errors: `{"error": {"code": "...", "message": "..."}}`
     *  - Laravel validation/throttle: `{"message": "...", "errors": {...}}`
     *
     * Populates {@see self::$status}, {@see self::$errors}, {@see self::$errorCode}
     * and, for 429 responses, {@see self::$retryAfter}.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $error = is_array($body) && isset($body['error']) && is_array($body['error'])
            ? $body['error']
            : null;

        $message = match (true) {
            is_array($body) && isset($body['message']) => (string) $body['message'],
            $error !== null && isset($error['message']) => (string) $error['message'],
            $error !== null && isset($error['code']) => (string) $error['code'],
            default => "Volpa Mail request failed with status {$response->status()}.",
        };

        $exception = new self($message, $response->status());
        $exception->status = $response->status();
        $exception->errors = is_array($body) && isset($body['errors']) && is_array($body['errors'])
            ? $body['errors']
            : [];
        $exception->errorCode = $error !== null && isset($error['code']) ? (string) $error['code'] : null;

        $retryAfter = $response->header('Retry-After');
        if ($retryAfter !== '' && ctype_digit($retryAfter)) {
            $exception->retryAfter = (int) $retryAfter;
        }

        return $exception;
    }

    /** Required field missing from the send payload (e.g. `from`, `to`). */
    public static function missingField(string $field): self
    {
        return new self("Volpa Mail: required field \"{$field}\" was not provided.");
    }

    /** API key (`VOLPA_MAIL_API_KEY`) is not configured. */
    public static function missingApiKey(): self
    {
        return new self('Volpa Mail: VOLPA_MAIL_API_KEY is not configured.');
    }
}
