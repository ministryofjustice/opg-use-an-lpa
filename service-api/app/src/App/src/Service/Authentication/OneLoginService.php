<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\AuthorisationServiceException;
use DateTime;
use DateTimeInterface;
use Exception;

use function Facile\OpenIDClient\base64url_encode;
use function openssl_digest;
use function random_bytes;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class OneLoginService
{
    public const CORE_IDENTITY_JWT = 'https://vocab.account.gov.uk/v1/coreIdentityJWT';

    public function __construct(
        private AuthorisationServiceBuilder $authorisationServiceBuilder,
        private UserInfoService $userInfoService,
    ) {
    }

    /**
     * @throws AuthorisationServiceException An error was encountered in the authentication library
     * @throws Exception                     It was not possible to generate random bytes (php error)
     */
    public function createAuthenticationRequest(string $uiLocale, string $redirectURL): array
    {
        $state = base64url_encode(random_bytes(12));
        $nonce = openssl_digest(random_bytes(24), 'sha256');

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
     * @param string $code The code returned from the OIDC service
     * @param string $state The state returned from the OIDC service
     * @param array{
     *     state: string,
     *     nonce: string,
     *     customs: array{
     *         redirect_uri: string
     *     }
     * } $authSession A pair of values needed generated at the start of the process
     * @return array The User retrieved from our records
     * @psalm-return ActorUser
     * @throws AuthorisationServiceException
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

        $identity = $this->userInfoService->processCoreIdentity($info[self::CORE_IDENTITY_JWT]);

        return [
            'Id'        => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
            'Identity'  => $info['sub'],
            'Email'     => $info['email'],
            'LastLogin' => (new DateTime('-1 day'))->format(DateTimeInterface::ATOM),
            'Birthday'  => $identity['birthDate'][0]['value'],
        ];
    }
}
