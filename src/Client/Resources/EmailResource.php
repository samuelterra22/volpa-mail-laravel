<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Data\SentEmail;

final readonly class EmailResource
{
    public function __construct(
        private VolpaMailClient $client,
    ) {}

    /**
     * Envia um e-mail transacional.
     *
     * @param  SendEmailData|array<string, mixed>  $email
     */
    public function send(SendEmailData|array $email): SentEmail
    {
        $data = $email instanceof SendEmailData
            ? $email
            : SendEmailData::fromArray($email);

        $response = $this->client->post('emails', $data->toArray());

        return SentEmail::fromArray($response);
    }

    /**
     * Consulta o status de um e-mail pelo ID.
     */
    public function get(string $id): SentEmail
    {
        $response = $this->client->get("emails/{$id}");

        return SentEmail::fromArray($response);
    }
}
