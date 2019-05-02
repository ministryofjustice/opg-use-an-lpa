<?php

declare(strict_types=1);

namespace Viewer\Form;

use Viewer\Service\Form\TokenManager;
use Symfony\Component\Form\Form;

class ShareCodeForm extends Form
{
    /**
     * @var TokenManager
     */
    private $csrfManager;

    public function setCsrfToken($token)
    {
        $this->csrfManager->setBaseToken($token);
    }

    public function setCsrfTokenManager(TokenManager $manager)
    {
        $this->csrfManager = $manager;
    }

    public static function getType() : string
    {
        return ShareCode::class;
    }


}
