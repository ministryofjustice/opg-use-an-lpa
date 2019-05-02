<?php

declare(strict_types=1);

namespace Viewer\Service\Form;

use RuntimeException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class TokenManager implements CsrfTokenManagerInterface
{
    private $baseToken;

    public function setBaseToken($token)
    {
        $this->baseToken = $token;
    }

    /**
     * @inheritDoc
     */
    public function getToken($tokenId) : CsrfToken
    {
        return new CsrfToken($tokenId, $this->baseToken);
    }

    /**
     * @inheritDoc
     */
    public function isTokenValid(CsrfToken $token) : bool
    {
        return $token->getValue() === $this->baseToken;
    }

    /**
     * @inheritDoc
     */
    public function refreshToken($tokenId) : CsrfToken
    {
        throw new RuntimeException(__METHOD__ . ' not implemented');
    }

    /**
     * @inheritDoc
     */
    public function removeToken($tokenId) : ?string
    {
        throw new RuntimeException(__METHOD__ . ' not implemented');
    }
}