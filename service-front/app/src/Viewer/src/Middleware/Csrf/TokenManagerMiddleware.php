<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionMiddleware;


/**
 * Class TokenManagerMiddleware
 *
 * Provides a Symfony compatible CSRF implementation to the application backed by Zend Session
 *
 * @package Viewer\Middleware\Csrf
 */
class TokenManagerMiddleware implements MiddlewareInterface
{
    const TOKEN_ATTRIBUTE = 'crsf_token_manager';

    /**
     * @var TokenGeneratorInterface
     */
    private $generator;

    /**
     * @var SessionTokenStorageFactory
     */
    private $storageFactory;

    /**
     * @var TokenManagerFactoryInterface
     */
    private $tokenManagerFactory;

    /**
     * @var string
     */
    private $attributeKey;
    public function __construct(
        TokenGeneratorInterface $generator,
        SessionTokenStorageFactory $storageFactory,
        TokenManagerFactoryInterface $tokenManagerFactory,
        string $attributeKey = self::TOKEN_ATTRIBUTE)
    {

        $this->generator = $generator;
        $this->storageFactory = $storageFactory;
        $this->tokenManagerFactory = $tokenManagerFactory;
        $this->attributeKey = $attributeKey;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false);
        if (! $session instanceof SessionInterface) {
            throw new \Exception("Missing session attribute in the request.");
        }

        $generator = $this->generator;
        $tokenStorage = $this->storageFactory->createSessionTokenStorage($session);
        $tokenManager = $this->tokenManagerFactory->createTokenManager($generator, $tokenStorage);

        return $handler->handle($request->withAttribute($this->attributeKey, $tokenManager));
    }
}