<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

enum InstructionsAndPreferencesImagesResult: string
{
    case COLLECTION_NOT_STARTED = 'COLLECTION_NOT_STARTED';
    case COLLECTION_IN_PROGRESS = 'COLLECTION_IN_PROGRESS';
    case COLLECTION_COMPLETE    = 'COLLECTION_COMPLETE';
    case COLLECTION_ERROR       = 'COLLECTION_ERROR';
}
