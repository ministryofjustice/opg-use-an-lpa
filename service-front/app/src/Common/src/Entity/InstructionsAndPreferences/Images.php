<?php

declare(strict_types=1);

namespace Common\Entity\InstructionsAndPreferences;

use JsonSerializable;

class Images implements JsonSerializable
{
    private const INSTRUCTIONS = '/iap-%s-(?:continuation_)?instructions/';
    private const PREFERENCES  = '/iap-%s-(?:continuation_)?preferences/';
    private const UNKNOWN      = '/iap-%s-continuation_unknown/';

    /**
     * @param int              $uId
     * @param ImagesStatus     $status
     * @param array<SignedUrl> $signedUrls
     */
    public function __construct(
        public readonly int $uId,
        public readonly ImagesStatus $status,
        private readonly array $signedUrls,
    ) {
    }

    /**
     * @return array<SignedUrl>
     */
    public function getInstructionsImageUrls(): array
    {
        return $this->getImageUrls($this->getImageRegex(self::INSTRUCTIONS));
    }

    /**
     * @return array<SignedUrl>
     */
    public function getPreferencesImageUrls(): array
    {
        return $this->getImageUrls($this->getImageRegex(self::PREFERENCES));
    }

    /**
     * @return array<SignedUrl>
     */
    public function getUnknownImageUrls(): array
    {
        return $this->getImageUrls($this->getImageRegex(self::UNKNOWN));
    }

    protected function getImageRegex(string $format): string
    {
        return sprintf($format, $this->uId);
    }

    protected function getImageUrls(string $pattern): array
    {
        return array_values(
            array_filter(
                $this->signedUrls, function ($signedUrl) use ($pattern) {
                    return preg_match($pattern, $signedUrl->imageName);
                }
            )
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'uId'        => $this->uId,
            'status'     => $this->status->value,
            'signedUrls' => [
                'instructions' => $this->getInstructionsImageUrls(),
                'preferences'  => $this->getPreferencesImageUrls(),
                'unknown'      => $this->getUnknownImageUrls(),
            ],
        ];
    }
}
