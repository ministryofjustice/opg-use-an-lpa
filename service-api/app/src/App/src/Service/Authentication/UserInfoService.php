<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Exception\AuthorisationServiceException;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder as FacileUserInfoServiceBuilder;
use Facile\OpenIDClient\Service\UserInfoService as FacileUserInfoService;
use Facile\OpenIDClient\Token\TokenSetInterface;
use Throwable;

/**
 * Facade class for OIDC user info fetching and validation
 *
 * @link https://en.wikipedia.org/wiki/Facade_pattern
 * @see FacileUserInfoService
 *
 * @codeCoverageIgnore
 */
class UserInfoService
{
    public function __construct(
        private FacileUserInfoServiceBuilder $userInfoServiceBuilder,
        private AuthorisationClientManager $authorisationClientManager,
    ) {
    }

    /**
     * @param TokenSetInterface $tokenSet
     * @return array
     * @throws AuthorisationServiceException
     */
    public function getUserInfo(TokenSetInterface $tokenSet): array
    {
        try {
            return $this->userInfoServiceBuilder->build()
                ->getUserInfo(
                    $this->authorisationClientManager->get(),
                    $tokenSet,
                );
        } catch (Throwable $e) {
            throw new AuthorisationServiceException(
                'Error encountered whilst fetching userinfo from OIDC service',
                500,
                $e
            );
        }
    }
}
