<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

enum SignatureType
{
    case None;
    case DataStoreLpas;
}
