<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder as FacileAuthorizationServiceBuilder;

/**
 * Decorator class for Facile AuthorizationServiceBuilder
 *
 * @codeCoverageIgnore
 */
class AuthorisationServiceBuilder
{
    private FacileAuthorizationServiceBuilder $authorizationServiceBuilder;

    public function __construct()
    {
        $this->authorizationServiceBuilder = new FacileAuthorizationServiceBuilder();
    }

    /**
     * Decorates the return of FacileAuthorizationServiceBuilder::build()
     */
    public function build(): AuthorisationService
    {
        return new AuthorisationService($this->authorizationServiceBuilder->build());
    }
}
