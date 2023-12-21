<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;

/**
 * Facade class for Facile AuthorizationServiceBuilder
 *
 * @link https://en.wikipedia.org/wiki/Facade_pattern
 *
 * @codeCoverageIgnore
 */
class AuthorisationServiceBuilder
{
    public function __construct(
        private AuthorizationServiceBuilder $authorizationServiceBuilder,
        private AuthorisationClientManager $authorisationClientManager,
    ) {
    }

    /**
     * Decorates the return of {@link FacileAuthorizationServiceBuilder::build()}
     */
    public function build(): AuthorisationService
    {
        return new AuthorisationService(
            $this->authorizationServiceBuilder->build(),
            $this->authorisationClientManager,
        );
    }
}
