<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

final readonly class Attachment
{
    /**
     * @param  string  $content  Conteúdo já codificado em base64.
     */
    public function __construct(
        public string $filename,
        public string $content,
        public string $contentType = 'application/octet-stream',
    ) {
    }

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
