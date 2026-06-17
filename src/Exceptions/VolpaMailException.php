<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class VolpaMailException extends RuntimeException
{
    /**
     * @var array<string, mixed>
     */
    public array $errors = [];

    public ?int $status = null;

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

    public static function missingField(string $field): self
    {
        return new self("Volpa Mail: o campo obrigatório \"{$field}\" não foi informado.");
    }

    public static function missingApiKey(): self
    {
        return new self('Volpa Mail: VOLPA_MAIL_API_KEY não configurada.');
    }
}
