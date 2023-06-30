<?php

declare(strict_types=1);

namespace Common\Entity\InstructionsAndPreferences;

class Images
{
    private const INSTRUCTIONS = '/iap-%s-(?:continuation_)?instructions/';
    private const PREFERENCES  = '/iap-%s-(?:continuation_)?preferences/';

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
    public function getImagesStatus(): ImagesStatus
    {
        return $this->status;
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

    protected function getImageRegex(string $format): string
    {
        return sprintf($format, $this->uId);
    }

    protected function getImageUrls(string $pattern): array
    {
        return array_values(
            array_filter($this->signedUrls, function ($signedUrl) use ($pattern) {
                return preg_match($pattern, $signedUrl->imageName);
            })
        );
    }
}
