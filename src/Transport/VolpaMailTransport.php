<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Transport;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\VolpaMailServiceProvider;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Symfony Mailer transport that delivers emails through the Volpa Mail API.
 *
 * Registered in Laravel as the `volpa-mail` mailer by the
 * {@see VolpaMailServiceProvider}, enabling the idiomatic
 * `Mail::mailer('volpa-mail')->send(...)`. Converts the Symfony message into a
 * {@see SendEmailData} and delegates the actual send to {@see VolpaMailClient}.
 */
final class VolpaMailTransport extends AbstractTransport
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {
        parent::__construct();
    }

    /**
     * Convert the Symfony message and send it through the API, then propagate
     * the returned ID back onto the Laravel message.
     *
     * @throws VolpaMailException If the API returns an error.
     */
    protected function doSend(SentMessage $message): void
    {
        /** @var Message $original */
        $original = $message->getOriginalMessage();
        $email = MessageConverter::toEmail($original);

        $sent = $this->client->emails()->send(
            SendEmailData::fromSymfonyEmail($email)
        );

        // Propagate the Volpa Mail ID back onto the Laravel message so it can be
        // correlated with later status webhooks.
        $message->setMessageId($sent->id);
    }

    /**
     * Transport name (DSN scheme), used by Symfony Mailer.
     */
    public function __toString(): string
    {
        return 'volpa-mail';
    }
}
