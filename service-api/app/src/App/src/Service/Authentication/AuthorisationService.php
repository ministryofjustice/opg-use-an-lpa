<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Exception\AuthorisationServiceException;
use Facile\OpenIDClient\Client\ClientInterface as OpenIDClient;
use Facile\OpenIDClient\Service\AuthorizationService as FacileAuthorisationService;
use JsonException;

/**
 * Decorator class for Facile AuthorizationService
 *
 * @codeCoverageIgnore
 */
class AuthorisationService
{
    public function __construct(private FacileAuthorisationService $authorisationService)
    {
    }

    /**
     * Decorates the return of FacileAuthorisationService::getAuthorisationUri()
     *
     * @throws AuthorisationServiceException
     */
    public function getAuthorisationUri(OpenIDClient $client, array $params = []): string
    {
        try {
            return $this->authorisationService->getAuthorizationUri($client, $params);
        } catch (JsonException $e) {
            throw new AuthorisationServiceException(
                'JSON error encountered when fetching authorisation uri',
                500,
                $e
            );
        }
    }
}
