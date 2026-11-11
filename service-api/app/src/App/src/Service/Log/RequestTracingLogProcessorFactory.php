<?php

declare(strict_types=1);

namespace App\Service\Log;

use Blazon\PSR11MonoLog\FactoryInterface;

final class RequestTracingLogProcessorFactory implements FactoryInterface
{
    public function __invoke(array $options): RequestTracingLogProcessor
    {
        return new RequestTracingLogProcessor();
    }
}
