<?php

declare(strict_types=1);

namespace App\Middleware\Session;

use App\Service\Session\EncryptedCookie as CookieSessionPersistence;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Session\LazySession;
use Zend\Expressive\Session\SessionPersistenceInterface;

/**
 * TODO: This could extent Zend\Expressive\Session\SessionMiddleware if/when self::SESSION_ATTRIBUTE is changed to static
 *  https://github.com/zendframework/zend-expressive-session/pull/35
 *
 * Class General
 * @package App\Middleware\Session
 */
class General implements MiddlewareInterface
{
    public const SESSION_ATTRIBUTE = 'general';

    /**
     * @var SessionPersistenceInterface
     */
    private $persistence;

    /**
     * General constructor.
     * @param CookieSessionPersistence $persistence
     */
    public function __construct(CookieSessionPersistence $persistence)
    {
        $this->persistence = $persistence;

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $session = new LazySession($this->persistence, $request);
        $response = $handler->handle($request->withAttribute(static::SESSION_ATTRIBUTE, $session));
        return $this->persistence->persistSession($session, $response);
    }
}
