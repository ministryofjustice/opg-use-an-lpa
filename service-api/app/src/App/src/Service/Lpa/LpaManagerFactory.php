<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Features\FeatureEnabled;
use Psr\Container\ContainerInterface;
use RuntimeException;

class LpaManagerFactory
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function __invoke(): LpaManagerInterface
    {
//        if (($this->container->get(FeatureEnabled::class))('support_datastore_lpas')) {
//            throw new RuntimeException('Datastore LPA support is enabled but not implemented yet.');
//        }

        return $this->container->get(SiriusLpaManager::class);
    }
}
