<?php

declare(strict_types=1);

namespace App\Service\Session;

use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\Session;
use Zend\Expressive\Session\SessionCookiePersistenceInterface;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionPersistenceInterface;

class Cookie implements SessionPersistenceInterface
{

    public function initializeSessionFromRequest(ServerRequestInterface $request) : SessionInterface
    {
        return null;
    }

    public function persistSession(SessionInterface $session, ResponseInterface $response) : ResponseInterface
    {
        return $response;
    }

}
