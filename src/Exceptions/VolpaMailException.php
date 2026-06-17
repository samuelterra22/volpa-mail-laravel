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

    /**
     * Build the exception from a failed HTTP response.
     *
     * Uses the body's `message` field when present; otherwise a generic message
     * with the status. Populates {@see self::$status} and {@see self::$errors}.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $message = is_array($body) && isset($body['message'])
            ? (string) $body['message']
            : "Volpa Mail request failed with status {$response->status()}.";

        $exception = new self($message, $response->status());
        $exception->status = $response->status();
        $exception->errors = is_array($body) ? ($body['errors'] ?? []) : [];

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
