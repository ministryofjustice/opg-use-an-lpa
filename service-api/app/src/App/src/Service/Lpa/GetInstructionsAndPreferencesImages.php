<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;
use App\Enum\InstructionsAndPreferencesImagesResult;
use App\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

class GetInstructionsAndPreferencesImages
{
    public function __construct(
        private InstructionsAndPreferencesImagesInterface $apiGateway,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(int $lpaId): InstructionsAndPreferencesImages
    {
        $images = $this->apiGateway->getInstructionsAndPreferencesImages($lpaId);

        if (
            $images->status === InstructionsAndPreferencesImagesResult::COLLECTION_ERROR
                || $images->status === InstructionsAndPreferencesImagesResult::COLLECTION_NOT_STARTED
        ) {
            $this->logger->notice(
                'I&P images for Lpa with id {lpa_id} have status {status}',
                [
                    'lpa_id'     => $images->uId,
                    'status'     => $images->status->value,
                    'event_code' => match ($images->status) {
                        InstructionsAndPreferencesImagesResult::COLLECTION_NOT_STARTED
                            => EventCodes::INSTRUCTIONS_PREFERENCES_IMAGES_NEW_EXTRACT,
                        InstructionsAndPreferencesImagesResult::COLLECTION_ERROR
                            => EventCodes::INSTRUCTIONS_PREFERENCES_IMAGES_ERROR,
                        default => ''
                    },
                ]
            );
        }

        return $images;
    }
}
