<?php
/**
 * The majority of this class is taken from zend-expressive-session-cache.
 * It's been modified to used the cookie for the session data, rather than the cache id.
 *
 * Original header:
 * @see       https://github.com/zendframework/zend-expressive-session-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-session-cache/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Viewer\Service\Session;

use DateInterval;
use DateTimeImmutable;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\Session;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionPersistenceInterface;

use function filemtime;
use function file_exists;
use function getcwd;
use function gmdate;
use function in_array;
use function sprintf;
use function time;

/**
 * Session persistence using a client side cookie.
 *
 * Session identifiers are generated using random_bytes (and casting to hex).
 * During persistence, if the session regeneration flag is true, a new session
 * identifier is created, and the session re-started.
 */
class Cookie implements SessionPersistenceInterface
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

    /** @var array */
    private const SUPPORTED_CACHE_LIMITERS = [
        'nocache',
        'public',
        'private',
        'private_no_expire',
    ];

    /** @var int */
    private $sessionExpire;

    /** @var string */
    private $cacheLimiter;

    /** @var string */
    private $cookieName;

    /** @var string|null */
    private $cookieDomain;

    /** @var string */
    private $cookiePath;

    /** @var bool */
    private $cookieSecure;

    /** @var bool */
    private $cookieHttpOnly;

    /** @var false|string */
    private $lastModified;

    /** @var bool */
    private $persistent;

    /**
     * CacheSessionPersistence constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {

        $this->cookieName = $config->getCookieName();

        $this->cookieDomain = $config->getCookieDomain();

        $this->cookiePath = $config->getCookiePath();

        $this->cookieSecure = $config->getCookieSecure();

        $this->cookieHttpOnly = $config->getCookieHttpOnly();

        $this->cacheLimiter = in_array($config->getCacheLimiter(), self::SUPPORTED_CACHE_LIMITERS, true)
            ? $config->getCacheLimiter()
            : 'nocache';

        $this->sessionExpire = $config->getSessionExpired();

        $this->lastModified = $config->getLastModified()
            ? gmdate(self::HTTP_DATE_FORMAT, $config->getLastModified())
            : $this->determineLastModifiedValue();

        $this->persistent = $config->getPersistent();
    }

    public function initializeSessionFromRequest(ServerRequestInterface $request) : SessionInterface
    {
        $sessionData = $this->getCookieFromRequest($request);

        $data = $this->decodeCookieValue($sessionData);

        if (isset($data[self::SESSION_TIME_KEY])) {
            $expiresAt = $data[self::SESSION_TIME_KEY] + $this->sessionExpire;
            if ($expiresAt > time()) {
                return new Session($data);
            }
        }

        // Fail to an empty session
        return new Session([]);
    }

    /**
     * Decodes the session from query string encoding to an array
     *
     * @param string $sessionData
     * @return array
     */
    protected function decodeCookieValue(string $sessionData) : array
    {
        $result = [];
        parse_str($sessionData, $result);
        return $result;
    }

    /**
     * Encodes the array.
     *
     * @param array $data
     * @return string
     */
    protected function encodeCookieValue(array $data) : string
    {
        // To keep cookies small, limit payloads to scalar values.
        foreach ($data as $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException(__CLASS__ . ' only supports scalar values in the session');
            }
        }

        return http_build_query($data);
    }

    public function persistSession(SessionInterface $session, ResponseInterface $response) : ResponseInterface
    {
        // No data? Nothing to do.
        if ([] === $session->toArray()) {
            return $response;
        }

        // Record the current time.
        $session->set(self::SESSION_TIME_KEY, time());

        // Encode to string
        $sessionData = $this->encodeCookieValue($session->toArray());

        $sessionCookie = SetCookie::create($this->cookieName)
            ->withValue($sessionData)
            ->withDomain($this->cookieDomain)
            ->withPath($this->cookiePath)
            ->withSecure($this->cookieSecure)
            ->withHttpOnly($this->cookieHttpOnly);

        $persistenceDuration = $this->getPersistenceDuration();
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
    private function generateCacheHeaders() : array
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
        if ('public' === $this->cacheLimiter) {
            return [
                'Expires'       => gmdate(self::HTTP_DATE_FORMAT, time() + $this->sessionExpire),
                'Cache-Control' => sprintf('public, max-age=%d', $this->sessionExpire),
                'Last-Modified' => $this->lastModified,
            ];
        }

        // cache_limiter: 'private'
        if ('private' === $this->cacheLimiter) {
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
     * Return the Last-Modified header line based on the request's script file
     * modified time. If no script file could be derived from the request we use
     * the file modification time of the current working directory as a fallback.
     *
     * @return string
     */
    private function determineLastModifiedValue() : string
    {
        $cwd = getcwd();
        foreach (['public/index.php', 'index.php'] as $filename) {
            $path = sprintf('%s/%s', $cwd, $filename);
            if (! file_exists($path)) {
                continue;
            }

            return gmdate(self::HTTP_DATE_FORMAT, filemtime($path));
        }

        return gmdate(self::HTTP_DATE_FORMAT, filemtime($cwd));
    }

    /**
     * Retrieve the session cookie value.
     *
     * Cookie headers may or may not be present, based on SAPI.  For instance,
     * under Swoole, they are omitted, but the cookie parameters are present.
     * As such, this method uses FigRequestCookies to retrieve the cookie value
     * only if the Cookie header is present. Otherwise, it falls back to the
     * request cookie parameters.
     *
     * In each case, if the value is not found, it falls back to generating a
     * new session identifier.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getCookieFromRequest(ServerRequestInterface $request) : string
    {
        if ('' !== $request->getHeaderLine('Cookie')) {
            return FigRequestCookies::get($request, $this->cookieName)->getValue() ?? '';
        }

        return $request->getCookieParams()[$this->cookieName] ?? '';
    }

    /**
     * Check if the response already carries cache headers
     */
    private function responseAlreadyHasCacheHeaders(ResponseInterface $response) : bool
    {
        return (
            $response->hasHeader('Expires')
            || $response->hasHeader('Last-Modified')
            || $response->hasHeader('Cache-Control')
            || $response->hasHeader('Pragma')
        );
    }

    private function getPersistenceDuration() : int
    {
        return $this->sessionExpire;
    }
}
