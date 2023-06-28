<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\InstructionsAndPreferences\Images;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Psr\Log\LoggerInterface;

class InstAndPrefImagesService
{
    public function __construct(
        private ApiClient $apiClient,
        private InstAndPrefImagesFactory $imagesFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function getImagesById(string $userToken, string $actorLpaToken): Images
    {
        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $imagesData = $this->apiClient->httpGet('/v1/lpas/' . $actorLpaToken . '/images');

            return $this->imagesFactory->createFromData($imagesData);
        } catch (ApiException $apiEx) {
            $this->logger->error(
                'An unrecoverable error was encountered when attempting to fetch images for lpa with token {token}',
                [
                    'token' => $actorLpaToken,
                ]
            );

            // It shouldn't error with anything and if it does do we want to continue to attempt to show the page?
            throw $apiEx;
        }
    }
}
