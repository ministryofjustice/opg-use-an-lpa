<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;

interface InstructionsAndPreferencesImagesInterface
{
    public function getInstructionsAndPreferencesImages(int $lpaId): InstructionsAndPreferencesImages;
}
