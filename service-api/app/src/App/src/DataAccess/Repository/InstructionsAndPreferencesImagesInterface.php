<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface InstructionsAndPreferencesImagesInterface
{
    /**
     * Returns the data for the instructions and preferences images.
     *
     * @return array|null   // TODO this will return the DAO instead now
     */
    public function getInstructionsAndPreferencesImages(): ?array;
}
