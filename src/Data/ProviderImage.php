<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Data;

use ChrisReedIO\MDStaff\Exceptions\InvalidProviderImageException;
use finfo;

final readonly class ProviderImage
{
    public const int MAX_FILE_SIZE = 3 * 1024 * 1024;

    public function __construct(
        public string $providerId,
        public string $uid,
        public string $contents,
        public string $mimeType,
    ) {}

    /**
     * Decode a provider file download, which MDStaff returns either as a
     * JSON-encoded base64 string or as the raw file contents.
     */
    public static function fromDownload(string $providerId, string $uid, string $body): self
    {
        $contents = self::decodeContents($body);
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new InvalidProviderImageException('MDStaff provider images must be JPEG or PNG files.');
        }

        if (@getimagesizefromstring($contents) === false) {
            throw new InvalidProviderImageException('MDStaff returned an unreadable provider image.');
        }

        return new self($providerId, $uid, $contents, $mimeType);
    }

    public function extension(): string
    {
        return $this->mimeType === 'image/png' ? 'png' : 'jpg';
    }

    /**
     * The UID reduced to characters that are safe in a file name.
     */
    public function safeUid(): string
    {
        return (string) preg_replace('/[^A-Za-z0-9_-]/', '-', $this->uid);
    }

    public function size(): int
    {
        return strlen($this->contents);
    }

    private static function decodeContents(string $body): string
    {
        $json = json_decode($body, true);

        if (is_string($json)) {
            $contents = base64_decode($json, true);

            if (! is_string($contents)) {
                throw new InvalidProviderImageException('MDStaff returned invalid base64 provider image data.');
            }
        } else {
            $contents = $body;
        }

        if ($contents === '') {
            throw new InvalidProviderImageException('MDStaff returned an empty provider image.');
        }

        if (strlen($contents) > self::MAX_FILE_SIZE) {
            throw new InvalidProviderImageException('MDStaff provider images must be 3 MB or smaller.');
        }

        return $contents;
    }
}
