<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Factory;

use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Entity\InstructionsAndPreferences\SignedUrl;
use Common\Service\Lpa\IAPImagesFactory;

final class IAPImages implements IAPImagesFactory
{
    /**
     * @inheritdoc
     */
    public function createFromData(array $data): Images
    {
        $signedUrls = [];
        foreach ($data['signedUrls'] as $filename => $url) {
            $signedUrls[] = new SignedUrl((string) $filename, (string) $url);
        }

        return new Images(
            (int) $data['uId'],
            ImagesStatus::from((string) $data['status']),
            $signedUrls
        );
    }
}
