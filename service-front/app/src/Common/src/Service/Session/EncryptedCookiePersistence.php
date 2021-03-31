<?php

declare(strict_types=1);

/**
 * The majority of this class is taken from mezzio-session-cache.
 * It's been modified to used the cookie for the encrypted session data, rather than the cache id.
 *
 * Original header:
 * @see       https://github.com/mezzio/mezzio-session-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/mezzio/mezzio-session-cache/blob/master/LICENSE.md New BSD License
 */

namespace Common\Service\Session;

use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use DateInterval;
use DateTimeImmutable;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Laminas\Crypt\BlockCipher;
use Mezzio\Session\Session;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionPersistenceInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class EncryptedCookiePersistence
 *
 * Utilises Amazon KMS to encrypt the session cookie sent to users
 *
 * @package Common\Service\Session
 */
class EncryptedCookiePersistence implements SessionPersistenceInterface
{
    /**
     * This unusual past date value is taken from the php-engine source code and
     * used "as is" for consistency.
     */
    public const CACHE_PAST_DATE  = 'Thu, 19 Nov 1981 08:52:00 GMT';

    public const HTTP_DATE_FORMAT = 'D, d M Y H:i:s T';

    /**
     * Key used within the session for the current time
     */
    public const SESSION_TIME_KEY = '__TIME__';

    /**
     * Key used within the session to flag that the session has been expired
     */
    public const SESSION_EXPIRED_KEY = '__EXPIRED__';

    /** @var array */
    private const SUPPORTED_CACHE_LIMITERS = [
        'nocache',
        'public',
        'private',
        'private_no_expire',
    ];

    private KeyManagerInterface $keyManager;

    private string $cookieName;

    private string $cookiePath;

    private string $cacheLimiter;

    private int $sessionExpire;

    private ?string $lastModified;

    private ?int $cookieTtl;

    private ?string $cookieDomain;

    private bool $cookieSecure;

    private bool $cookieHttpOnly;

    private ?int $originalSessionTime;

    private ?string $requestPath;

    /**
     * EncryptedCookiePersistence constructor.
     * @param KeyManagerInterface $keyManager
     * @param string $cookieName
     * @param string $cookiePath
     * @param string $cacheLimiter
     * @param int $sessionExpire
     * @param int|null $lastModified
     * @param int|null $cookieTtl
     * @param string|null $cookieDomain
     * @param bool $cookieSecure
     * @param bool $cookieHttpOnly
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        string $cookieName,
        string $cookiePath,
        string $cacheLimiter,
        int $sessionExpire,
        ?int $lastModified,
        ?int $cookieTtl,
        ?string $cookieDomain,
        bool $cookieSecure,
        bool $cookieHttpOnly
    ) {
        $this->keyManager = $keyManager;
        $this->cookieName = $cookieName;
        $this->cookiePath = $cookiePath;
        $this->cacheLimiter = in_array($cacheLimiter, self::SUPPORTED_CACHE_LIMITERS, true)
            ? $cacheLimiter
            : 'nocache';
        $this->sessionExpire = $sessionExpire;
        $this->lastModified = $lastModified
            ? gmdate(self::HTTP_DATE_FORMAT, $lastModified)
            : $this->getLastModified();
        $this->cookieTtl = $cookieTtl;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure;
        $this->cookieHttpOnly = $cookieHttpOnly;

        $this->originalSessionTime = null;
        $this->requestPath = null;
    }


    //------------------------------------------------------------------------------------------------------------
    // Methods around securing the cookie

    /**
     * Returns the configured Block Cipher to be used within this class.
     *
     * @return BlockCipher
     */
    private function getBlockCipher(): BlockCipher
    {
        return BlockCipher::factory('openssl', [
            'algo' => 'aes',
            'mode' => 'gcm'
        ])->setBinaryOutput(true);
    }

    //---

    /**
     * Encrypts the session payload with the current (latest) key.
     *
     *  The result is <keyId>.<ciphertextr>
     *
     * @param array $data
     * @return string
     */
    protected function encodeCookieValue(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $plaintext = json_encode($data);

        $key = $this->keyManager->getEncryptionKey();

        $ciphertext = $this->getBlockCipher()
            ->setKey($key->getKeyMaterial())
            ->encrypt($plaintext);

        return $key->getId() . '.' . Base64UrlSafe::encode($ciphertext);
    }

    /**
     * Decrypt the session value.
     *
     * @param string $data
     * @return array
     */
    protected function decodeCookieValue(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Separate out the key ID and the data
        [$keyId, $payload] = explode('.', $data, 2);

        try {
            $key = $this->keyManager->getDecryptionKey($keyId);

            $ciphertext = Base64UrlSafe::decode($payload);

            $plaintext = $this->getBlockCipher()
                ->setKey($key->getKeyMaterial())
                ->decrypt($ciphertext);

            return json_decode($plaintext, true);
        } catch (KeyNotFoundException $e) {
            # TODO: add logging
        }

        // Something went wrong. Restart the session.
        return [];
    }


    //------------------------------------------------------------------------------------------------------------
    // Public methods for the actual starting and writing of the session

    public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
    {
        $sessionData = $this->getCookieFromRequest($request);
        $data = $this->decodeCookieValue($sessionData);

        $this->originalSessionTime = $data[self::SESSION_TIME_KEY] ?: null;
        $this->requestPath = $request->getUri()->getPath();

        // responsible the for expiry of a users session
        if (isset($data[self::SESSION_TIME_KEY])) {
            $expiresAt = $data[self::SESSION_TIME_KEY] + $this->sessionExpire;
            if ($expiresAt > time()) {
                return new Session($data);
            }
        }

        // if we have values in the session but are here then we have fallen into the gap where the session has expired
        // but we're still within the cookieTtl amount of time. by setting an expired flag we'll be able to prompt with
        // messages such as "You've been logged out due to inactivity" or allow access to PDF downloads
        if ($sessionData != '') {
            $data[self::SESSION_EXPIRED_KEY] = true;
        }

        return new Session($data);
    }

    public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        // Record the set time
        $session->set(self::SESSION_TIME_KEY, $this->sessionTime());

        // Encode to string
        $sessionData = $this->encodeCookieValue($session->toArray());

        $sessionCookie = SetCookie::create($this->cookieName)
            ->withValue($sessionData)
            ->withDomain($this->cookieDomain)
            ->withPath($this->cookiePath)
            ->withSecure($this->cookieSecure)
            ->withHttpOnly($this->cookieHttpOnly);

        $persistenceDuration = $this->getCookieLifetime($session);
        if ($persistenceDuration) {
            $sessionCookie = $sessionCookie->withExpires(
                (new DateTimeImmutable())->add(new DateInterval(sprintf('PT%dS', $persistenceDuration)))
            );
        }

        $response = FigResponseCookies::set($response, $sessionCookie);

        if ($this->responseAlreadyHasCacheHeaders($response)) {
            return $response;
        }

        foreach ($this->generateCacheHeaders() as $name => $value) {
            if (false !== $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Generate cache http headers for this instance's session cache_limiter and
     * cache_expire values
     */
    private function generateCacheHeaders(): array
    {
        // cache_limiter: 'nocache'
        if ('nocache' === $this->cacheLimiter) {
            return [
                'Expires'       => self::CACHE_PAST_DATE,
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma'        => 'no-cache',
            ];
        }

        // cache_limiter: 'public'
        if ($this->cacheLimiter === 'public') {
            return [
                'Expires'       => gmdate(self::HTTP_DATE_FORMAT, time() + $this->sessionExpire),
                'Cache-Control' => sprintf('public, max-age=%d', $this->sessionExpire),
                'Last-Modified' => $this->lastModified,
            ];
        }

        // cache_limiter: 'private'
        if ($this->cacheLimiter === 'private') {
            return [
                'Expires'       => self::CACHE_PAST_DATE,
                'Cache-Control' => sprintf('private, max-age=%d', $this->sessionExpire),
                'Last-Modified' => $this->lastModified,
            ];
        }

        // last possible case, cache_limiter = 'private_no_expire'
        return [
            'Cache-Control' => sprintf('private, max-age=%d', $this->sessionExpire),
            'Last-Modified' => $this->lastModified,
        ];
    }

    /**
     * Return the Last-Modified header line based on main script of execution
     * modified time. If unable to get a valid timestamp we use this class file
     * modification time as fallback.
     *
     * @return string|false
     */
    private function getLastModified()
    {
        $lastmod = getlastmod() ?: filemtime(__FILE__);
        return $lastmod ? gmdate(self::HTTP_DATE_FORMAT, $lastmod) : false;
    }

    /**
     * Retrieve the session cookie value.
     *
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getCookieFromRequest(ServerRequestInterface $request): string
    {
        return FigRequestCookies::get($request, $this->cookieName)->getValue() ?? '';
    }

    /**
     * Check if the response already carries cache headers
     *
     * @param ResponseInterface $response
     * @return bool
     */
    private function responseAlreadyHasCacheHeaders(ResponseInterface $response): bool
    {
        return (
            $response->hasHeader('Expires')
            || $response->hasHeader('Last-Modified')
            || $response->hasHeader('Cache-Control')
            || $response->hasHeader('Pragma')
        );
    }

    private function getCookieLifetime(SessionInterface $session): int
    {
        $lifetime = $this->cookieTtl;
        if (
            $session instanceof SessionCookiePersistenceInterface
            && $session->has(SessionCookiePersistenceInterface::SESSION_LIFETIME_KEY)
        ) {
            $lifetime = $session->getSessionLifetime();
        }

        return $lifetime > 0 ? $lifetime : 0;
    }

    private function sessionTime(): int
    {
        // Checking session or setting current time
        return !is_null($this->originalSessionTime)
        && !is_null($this->requestPath)
        && preg_match("/^\/session-check(\/|)$/i", $this->requestPath)
            ? $this->originalSessionTime
            : time();
    }
}
