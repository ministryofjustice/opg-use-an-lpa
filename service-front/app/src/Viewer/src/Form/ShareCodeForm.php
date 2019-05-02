<?php

declare(strict_types=1);

namespace Viewer\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ShareCodeForm extends Form implements CsrfTokenManagerInterface
{
    public static function getType() : string
    {
        return ShareCode::class;
    }

    public function getToken($tokenId)
    {
        die(__METHOD__);
    }

    public function refreshToken($tokenId)
    {
        die(__METHOD__);
    }

    public function removeToken($tokenId)
    {
        die(__METHOD__);
    }

    public function isTokenValid(CsrfToken $token)
    {
        die(__METHOD__);
    }

}
