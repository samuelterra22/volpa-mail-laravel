<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Immutable email-send DTO — mirrors the `POST /emails` request body.
 *
 * It can be built three ways: through the typed constructor, through
 * {@see self::fromArray()} (direct SDK usage), or through
 * {@see self::fromSymfonyEmail()} (used by the Transport). {@see self::toArray()}
 * produces the final payload, omitting empty fields.
 */
final readonly class SendEmailData
{
    /**
     * @param  array<int, Address>  $to
     * @param  array<int, Address>  $cc
     * @param  array<int, Address>  $bcc
     * @param  array<int, Address>  $replyTo
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $tags
     * @param  array<string, string>  $headers
     * @param  array<int, Attachment>  $attachments
     */
    public function __construct(
        public Address $from,
        public array $to,
        public ?string $subject = null,
        public ?string $html = null,
        public ?string $text = null,
        public array $cc = [],
        public array $bcc = [],
        public array $replyTo = [],
        public ?string $templateId = null,
        public array $variables = [],
        public array $tags = [],
        public array $headers = [],
        public array $attachments = [],
    ) {}

    /**
     * Build the DTO from a "friendly" array (direct SDK usage).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws VolpaMailException If `from` or `to` is missing.
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['from'])) {
            throw VolpaMailException::missingField('from');
        }

        if (empty($data['to'])) {
            throw VolpaMailException::missingField('to');
        }

        return new self(
            from: self::address($data['from']),
            to: self::addresses($data['to']),
            subject: $data['subject'] ?? null,
            html: $data['html'] ?? null,
            text: $data['text'] ?? null,
            cc: self::addresses($data['cc'] ?? []),
            bcc: self::addresses($data['bcc'] ?? []),
            replyTo: self::addresses($data['reply_to'] ?? []),
            templateId: $data['template_id'] ?? null,
            variables: $data['variables'] ?? [],
            tags: $data['tags'] ?? [],
            headers: $data['headers'] ?? [],
            attachments: array_map(
                static fn (array $a) => new Attachment($a['filename'], $a['content'], $a['content_type'] ?? 'application/octet-stream'),
                $data['attachments'] ?? [],
            ),
        );
    }

    /**
     * Build the DTO from a Symfony message (used by the Transport).
     *
     * @throws VolpaMailException If the message has no sender.
     */
    public static function fromSymfonyEmail(SymfonyEmail $email): self
    {
        $from = $email->getFrom();

        if ($from === []) {
            throw VolpaMailException::missingField('from');
        }

        $attachments = [];
        foreach ($email->getAttachments() as $part) {
            $attachments[] = new Attachment(
                filename: $part->getFilename() ?? 'attachment',
                content: base64_encode($part->getBody()),
                contentType: $part->getContentType(),
            );
        }

        return new self(
            from: Address::fromSymfony($from[0]),
            to: array_map(Address::fromSymfony(...), $email->getTo()),
            subject: $email->getSubject(),
            html: self::bodyToString($email->getHtmlBody()),
            text: self::bodyToString($email->getTextBody()),
            cc: array_map(Address::fromSymfony(...), $email->getCc()),
            bcc: array_map(Address::fromSymfony(...), $email->getBcc()),
            replyTo: array_map(Address::fromSymfony(...), $email->getReplyTo()),
            attachments: $attachments,
        );
    }

    /**
     * Serialize to the API payload, dropping null and empty values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'from' => $this->from->toArray(),
            'to' => array_map(static fn (Address $a) => $a->toArray(), $this->to),
            'cc' => array_map(static fn (Address $a) => $a->toArray(), $this->cc),
            'bcc' => array_map(static fn (Address $a) => $a->toArray(), $this->bcc),
            'reply_to' => array_map(static fn (Address $a) => $a->toArray(), $this->replyTo),
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
            'template_id' => $this->templateId,
            'variables' => $this->variables,
            'tags' => $this->tags,
            'headers' => $this->headers,
            'attachments' => array_map(static fn (Attachment $a) => $a->toArray(), $this->attachments),
        ], static fn ($value) => $value !== null && $value !== []);
    }

    /**
     * Normalize a single address (a string "email" or an array) into {@see Address}.
     *
     * @param  array<string, mixed>|string  $value
     */
    private static function address(array|string $value): Address
    {
        return is_string($value)
            ? new Address($value)
            : Address::fromArray($value);
    }

    /**
     * Normalize a list of addresses (string, single assoc array, or list) into Address objects.
     *
     * @param  array<int, array<string, mixed>|string>|array<string, mixed>|string  $value
     * @return array<int, Address>
     */
    private static function addresses(array|string $value): array
    {
        if (is_string($value)) {
            return [new Address($value)];
        }

        // Allow passing a single address as an associative array.
        if (isset($value['email'])) {
            return [Address::fromArray($value)];
        }

        return array_values(array_map(self::address(...), $value));
    }

    /**
     * Reduce a Symfony body (string|object|null) to a string or null.
     */
    private static function bodyToString(mixed $body): ?string
    {
        if ($body === null) {
            return null;
        }

        return is_string($body) ? $body : (string) $body;
    }
}
