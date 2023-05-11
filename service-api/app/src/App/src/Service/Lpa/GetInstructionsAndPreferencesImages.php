<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;

class GetInstructionsAndPreferencesImages
{
    public function __construct(private InstructionsAndPreferencesImagesInterface $apiGateway)
    {
    }


    public function __invoke(int $lpaId): InstructionsAndPreferencesImages
    {
        return $this->apiGateway->getInstructionsAndPreferencesImages($lpaId);
    }
}