<?php

declare(strict_types=1);

namespace AppTest\Exception\Mocks;

use App\Exception\AbstractApiException;

class BadApiException extends AbstractApiException
{
    /**
     * Normally this needs to be an integer to ensure the abstract can complete it's constructor
     *
     * @var string
     */
    protected $code = "bad code";
}