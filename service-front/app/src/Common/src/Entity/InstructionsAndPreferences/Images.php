<?php

declare(strict_types=1);

namespace Common\Entity\InstructionsAndPreferences;

class Images
{
    private const INSTRUCTIONS = 'iap-%s-instructions';
    private const PREFERENCES  = 'iap-%s-preferences';

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
        return $this->getImageUrls($this->getImageName(self::INSTRUCTIONS));
    }

    /**
     * @return array<SignedUrl>
     */
    public function getPreferencesImageUrls(): array
    {
        return $this->getImageUrls($this->getImageName(self::PREFERENCES));
    }

    protected function getImageName(string $format): string
    {
        return sprintf($format, $this->uId);
    }

    protected function getImageUrls(string $imageName): array
    {
        return array_values(
            array_filter($this->signedUrls, function ($signedUrl) use ($imageName) {
                return $signedUrl->imageName === $imageName;
            })
        );
    }
}
