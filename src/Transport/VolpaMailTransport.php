<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Transport;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

final class VolpaMailTransport extends AbstractTransport
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        /** @var Message $original */
        $original = $message->getOriginalMessage();
        $email = MessageConverter::toEmail($original);

        $sent = $this->client->emails()->send(
            SendEmailData::fromSymfonyEmail($email)
        );

        // Propaga o ID do Volpa Mail de volta para a mensagem do Laravel,
        // permitindo correlacionar com webhooks de status posteriormente.
        $message->setMessageId($sent->id);
    }

    public function __toString(): string
    {
        return 'volpa-mail';
    }
}
