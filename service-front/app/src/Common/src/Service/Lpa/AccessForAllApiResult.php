<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Lpa\Response\Response;
use Common\Service\Lpa\Response\AccessForAllResult;

class AccessForAllApiResult
{
    public function __construct(
        private AccessForAllResult $response,
        private array|Response $data,
    ) {
    }

    public function getData(): array|Response
    {
        return $this->data;
    }

    public function getResponse(): AccessForAllResult
    {
        return $this->response;
    }
}
