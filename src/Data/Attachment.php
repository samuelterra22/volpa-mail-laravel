<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Email attachment. The content travels base64-encoded, as required by the API.
 */
final readonly class Attachment
{
    /**
     * @param  string  $content  Content already base64-encoded.
     */
    public function __construct(
        public string $filename,
        public string $content,
        public string $contentType = 'application/octet-stream',
    ) {}

    /**
     * Create an attachment by reading a file from disk and base64-encoding it.
     *
     * @param  string|null  $filename  Overrides the name; defaults to the file's basename.
     * @param  string|null  $contentType  Overrides the MIME type; defaults to the detected one.
     */
    public static function fromPath(string $path, ?string $filename = null, ?string $contentType = null): self
    {
        return new self(
            filename: $filename ?? basename($path),
            content: base64_encode((string) file_get_contents($path)),
            contentType: $contentType ?? (mime_content_type($path) ?: 'application/octet-stream'),
        );
    }

    /**
     * @return array{filename: string, content: string, content_type: string}
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'content' => $this->content,
            'content_type' => $this->contentType,
        ];
    }
}
