<?php

declare(strict_types=1);

namespace Common\Entity\InstructionsAndPreferences;

enum ImagesStatus: string
{
    case COLLECTION_NOT_STARTED = 'COLLECTION_NOT_STARTED';
    case COLLECTION_IN_PROGRESS = 'COLLECTION_IN_PROGRESS';
    case COLLECTION_COMPLETE    = 'COLLECTION_COMPLETE';
    case COLLECTION_ERROR       = 'COLLECTION_ERROR';
}
