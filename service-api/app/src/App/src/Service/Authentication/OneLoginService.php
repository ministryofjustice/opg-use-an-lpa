<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\AuthorisationServiceException;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use App\Exception\RandomException;
use App\Service\RandomByteGenerator;
use App\Service\User\ResolveOAuthUser;

use function Facile\OpenIDClient\base64url_encode;
use function openssl_digest;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class OneLoginService
{
    public const CORE_IDENTITY_JWT   = 'https://vocab.account.gov.uk/v1/coreIdentityJWT';
    public const LOGOUT_REDIRECT_URL = 'https://www.gov.uk/done/use-lasting-power-of-attorney';

    public function __construct(
        private AuthorisationServiceBuilder $authorisationServiceBuilder,
        private UserInfoService $userInfoService,
        private ResolveOAuthUser $resolveOAuthUser,
        private RandomByteGenerator $byteGenerator,
    ) {
    }

    /**
     * @throws AuthorisationServiceException An error was encountered in the authentication library
     * @throws RandomException               It was not possible to generate random bytes (php error)
     */
    public function createAuthenticationRequest(string $uiLocale, string $redirectURL): array
    {
        $state = base64url_encode(($this->byteGenerator)(12));
        $nonce = openssl_digest(($this->byteGenerator)(24), 'sha256');

        $authorisationRequestUrl = $this->authorisationServiceBuilder->build()->getAuthorisationUri(
            [
                'scope'        => 'openid email',
                'state'        => $state,
                'redirect_uri' => $redirectURL,
                'nonce'        => $nonce,
                'vtr'          => '["Cl.Cm"]',
                'ui_locales'   => $uiLocale,
            ]
        );

        return [
            'state' => $state,
            'nonce' => $nonce,
            'url'   => $authorisationRequestUrl,
        ];
    }

    /**
     * @param string $code        The code returned from the OIDC service
     * @param string $state       The state returned from the OIDC service
     * @param array{
     *     state: string,
     *     nonce: string,
     *     customs: array{
     *         redirect_uri: string
     *     }
     * }             $authSession A pair of values needed generated at the start of the process
     * @return array{
     *     user: array,
     *     token: string,
     * }                          The User retrieved from our records
     * @psalm-return array{
     *     user: ActorUser,
     *     token: string,
     * }                          The User retrieved from our records
     * @throws AuthorisationServiceException Throw whilst failing to talk to the OIDC service
     * @throws CreationException             It was not possible to create a new user
     * @throws ConflictException             The email exists as a "NewEmail" against an existing account
     * @throws NotFoundException             The newly created user was not found when attempting to migrate to OIDC
     */
    public function handleCallback(
        string $code,
        string $state,
        array $authSession,
    ): array {
        $authorisationService = $this->authorisationServiceBuilder->build();
        $tokens               = $authorisationService->callback($code, $state, $authSession);
        if ($tokens->getIdToken() === null) {
            throw new AuthorisationServiceException('No Id token from service despite passing checks');
        }

        $info = $this->userInfoService->getUserInfo($tokens);

        return [
            'user'  => ($this->resolveOAuthUser)($info['sub'], $info['email']),
            'token' => $tokens->getIdToken(),
        ];
    }

    /**
     * @param string $idToken The ID token retrieved as a part of the original authentication flow
     * @return string A URL with populated parameters
     * @throws AuthorisationServiceException
     */
    public function createLogoutUrl(string $idToken): string
    {
        $authorisationService = $this->authorisationServiceBuilder->build();
        $logoutUri            = $authorisationService->getLogoutUri();

        $params = [
            'id_token_hint'            => $idToken,
            'post_logout_redirect_uri' => self::LOGOUT_REDIRECT_URL,
        ];

        return $logoutUri . '?' . http_build_query($params);
    }
}
