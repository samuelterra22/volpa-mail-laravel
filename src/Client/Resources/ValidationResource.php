<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\BulkValidationResult;
use SamuelTerra\VolpaMail\Data\EmailValidationResult;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for email validation `/validate`.
 *
 * Provides single-address and bulk validation. All HTTP communication
 * goes through the injected {@see VolpaMailClient}.
 */
final class ValidationResource
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {}

    /**
     * Validate a single email address.
     *
     * @throws VolpaMailException
     */
    public function validate(string $email): EmailValidationResult
    {
        $response = $this->client->post('validate/email', ['email' => $email]);

        return EmailValidationResult::fromArray($response);
    }

    /**
     * Validate up to 100 email addresses in a single request.
     *
     * @param  array<int, string>  $emails
     *
     * @throws VolpaMailException
     */
    public function validateBulk(array $emails): BulkValidationResult
    {
        $response = $this->client->post('validate/bulk', ['emails' => $emails]);

        return BulkValidationResult::fromArray($response);
    }
}
