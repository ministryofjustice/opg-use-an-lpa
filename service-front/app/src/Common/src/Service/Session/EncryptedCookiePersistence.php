<?php

declare(strict_types=1);

namespace Common\Service\Session;

use Common\Exception\SessionEncryptionFailureException;
use Common\Service\Session\Encryption\EncryptInterface;
use Mezzio\Session\Persistence\CacheHeadersGeneratorTrait;
use Mezzio\Session\Persistence\Http;
use Mezzio\Session\Persistence\SessionCookieAwareTrait;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Utilises Amazon KMS to encrypt the session cookie sent to users
 */
class EncryptedCookiePersistence implements SessionPersistenceInterface
{
    use CacheHeadersGeneratorTrait;
    use SessionCookieAwareTrait;

    /**
     * Key used within the session for the current time
     */
    public const string SESSION_TIME_KEY = '__TIME__';

    /**
     * Key used within the session to flag that the session has been expired
     */
    public const string SESSION_EXPIRED_KEY = '__EXPIRED__';

    public function __construct(
        private EncryptInterface $encrypter,
        string $cookieName,
        string $cookiePath,
        string $cacheLimiter,
        int $cacheExpire,
        ?int $lastModified,
        int $cookieLifetime,
        ?string $cookieDomain,
        bool $cookieSecure,
        bool $cookieHttpOnly,
    ) {
        $this->cookieName     = $cookieName;
        $this->cookiePath     = $cookiePath;
        $this->cacheExpire    = $cacheExpire;
        $this->cookieLifetime = $cookieLifetime;
        $this->cookieDomain   = $cookieDomain;
        $this->cookieSecure   = $cookieSecure;
        $this->cookieHttpOnly = $cookieHttpOnly;

        $this->cacheLimiter = isset(self::$supportedCacheLimiters[$cacheLimiter])
            ? $cacheLimiter
            : 'nocache';

        $this->lastModified = $lastModified !== null
            ? gmdate(Http::DATE_FORMAT, $lastModified)
            : $this->getLastModified();

        $this->cookieSameSite             = 'None';
        $this->deleteCookieOnEmptySession = true;
    }

    //------------------------------------------------------------------------------------------------------------
    // Public methods for the actual starting and writing of the session

    public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
    {
        $sessionData = $this->getSessionCookieValueFromRequest($request);
        $data        = $this->encrypter->decodeCookieValue($sessionData);

        return new Session($data);
    }

    public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        // No data, nothing to process
        if ($session->toArray() === [] || ! $session->hasChanged()) {
            return $response;
        }

        $encryptedCookieValue = '';
        try {
            $encryptedCookieValue = $this->encrypter->encodeCookieValue($session->toArray());
        } catch (SessionEncryptionFailureException) {
            // Causes existing session cookie to be cleared via $this->deleteCookieOnEmptySession.
            // Because this will happen at essentially the last part of a response process this will
            // *not* visibly log a user out (for instance) on that particular request i.e. they will see the
            // page they requested. *But* they will have been logged out from that point onwards i.e. their
            // next request will result in the login page.
            $session->clear();
        }

        $response = $this->addSessionCookieHeaderToResponse($response, $encryptedCookieValue, $session);
        return $this->addCacheHeadersToResponse($response);
    }
}
