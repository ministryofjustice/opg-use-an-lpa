<?php

declare(strict_types=1);

namespace App\Service\JWT;

use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Psr\Container\ContainerInterface;

class JWSBuilderFactory
{
    private readonly AlgorithmManagerFactory $algorithmManagerFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->algorithmManagerFactory = $container->get(AlgorithmManagerFactory::class);

        // available algorithms
        $this->algorithmManagerFactory->add('HS256', $container->get(HS256::class));
    }

    /**
     * @param string[] $algorithms
     * @return JWSBuilder
     */
    public function create(array $algorithms): JWSBuilder
    {
        return new JWSBuilder($this->algorithmManagerFactory->create($algorithms));
    }
}
