<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Exception\AuthorisationServiceException;
use Facile\OpenIDClient\Client\ClientInterface as OpenIDClient;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Session\AuthSession;
use Facile\OpenIDClient\Token\TokenSetInterface;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Facade class for Facile AuthorizationService and components
 *
 * @link https://en.wikipedia.org/wiki/Facade_pattern
 *
 * @codeCoverageIgnore
 */
class AuthorisationService
{
    private ?OpenIDClient $authorisationClient;

    public function __construct(
        private AuthorizationService $authorizationService,
        private AuthorisationClientManager $authorisationClientManager,
    ) {
        $this->authorisationClient = null;
    }

    /**
     * Decorates {@link AuthorizationService::getAuthorizationUri()}
     *
     * @throws AuthorisationServiceException
     */
    public function getAuthorisationUri(array $params = []): string
    {
        try {
            return $this->authorizationService->getAuthorizationUri($this->getClient(), $params);
        } catch (Throwable $e) {
            throw new AuthorisationServiceException(
                'JSON error encountered when fetching authorisation uri',
                500,
                $e
            );
        }
    }

    /**
     * Decorates {@link AuthorizationService::callback()}
     *
     * @param string $code The code returned from the OIDC service
     * @param string $state The state returned from the OIDC service
     * @param array{
     *     state: string,
     *     nonce: string,
     *     customs: array{
     *         redirect_uri: string
     *     }
     * } $session A pair of values needed generated at the start of the process
     * @throws AuthorisationServiceException
     */
    public function callback(string $code, string $state, array $session): TokenSetInterface
    {
        try {
            $session     = AuthSession::fromArray($session);
            $redirectUri = $session->getCustoms()['redirect_uri'];

            return $this->authorizationService->callback(
                $this->getClient(),
                [
                    'code'  => $code,
                    'state' => $state,
                ],
                $redirectUri,
                $session,
            );
        } catch (Throwable $e) {
            throw new AuthorisationServiceException(
                'Error encountered whilst validating OIDC callback response',
                500,
                $e
            );
        }
    }

    /**
     * Interrogates the client metadata to find the OIDC end_session_endpoint URI
     *
     * @throws AuthorisationServiceException
     */
    public function getLogoutUri(): string
    {
        try {
            $endpoint = $this->getClient()->getIssuer()->getMetadata()->get('end_session_endpoint');

            if ($endpoint === null) {
                throw new AuthorisationServiceException(
                    '"end_session_endpoint" not defined in Issuers OIDC configuration'
                );
            }

            return $endpoint;
        } catch (Throwable $e) {
            throw new AuthorisationServiceException(
                'JSON error encountered when fetching logout uri',
                500,
                $e
            );
        }
    }

    /**
     * Ensures each instance of this class only builds a single client instance. In practice this should amount to
     * once per request.
     *
     * @throws SimpleCacheException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    private function getClient(): OpenIDClient
    {
        if ($this->authorisationClient === null) {
            $this->authorisationClient = $this->authorisationClientManager->get();
        }

        return $this->authorisationClient;
    }
}
