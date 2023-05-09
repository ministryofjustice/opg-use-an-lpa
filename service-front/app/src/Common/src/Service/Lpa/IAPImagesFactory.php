<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\InstructionsAndPreferences\Images;

interface IAPImagesFactory
{
    /**
     * Takes an inbound array of data and returns a DTO representing a
     * collection of instructions and preferences images attached to an LPA
     *
     * @param array{uId: int, status: string, signedUrls: array<string, string>} $data
     * @return Images
     */
    public function createFromData(array $data): Images;
}
