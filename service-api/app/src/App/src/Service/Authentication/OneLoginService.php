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
    public const CORE_IDENTITY_JWT = 'https://vocab.account.gov.uk/v1/coreIdentityJWT';

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
                'vtr'          => '["Cl.Cm.P2"]',
                'ui_locales'   => $uiLocale,
                'claims'       => '{"userinfo":{"' . self::CORE_IDENTITY_JWT . '":null}}',
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
     * @return array The User retrieved from our records
     * @psalm-return ActorUser
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
        if (! array_key_exists(self::CORE_IDENTITY_JWT, $info)) {
            throw new AuthorisationServiceException(
                'Identity information not returned from authorisation service'
            );
        }

        $this->userInfoService->processCoreIdentity($info[self::CORE_IDENTITY_JWT]);

        return ($this->resolveOAuthUser)($info['sub'], $info['email']);
    }
}
