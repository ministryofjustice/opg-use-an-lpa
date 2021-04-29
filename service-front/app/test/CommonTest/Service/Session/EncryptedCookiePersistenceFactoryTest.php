<?php

declare(strict_types=1);

namespace CommonTest\Service\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\Session\EncryptedCookiePersistenceFactory;
use Common\Service\Session\Encryption\EncryptInterface;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class EncryptedCookiePersistenceFactoryTest
 *
 * @package CommonTest\Service\Session
 * @coversDefaultClass \Common\Service\Session\EncryptedCookiePersistenceFactory
 */
class EncryptedCookiePersistenceFactoryTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    public function it_will_create_an_instance_when_given_correct_configuration(): void
    {
        $config = [
            'session' => [
                'cookie_name' => 'session',
                'cookie_path' => '/',
                'cache_limiter' => 'nocache',
                'expires' => 1200,
                'last_modified' => null,
                'cookie_ttl' => 86400,
                'cookie_domain' => null,
                'cookie_secure' => true,
                'cookie_http_only' => true,
            ]
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);
        $containerProphecy
            ->get(EncryptInterface::class)
            ->willReturn(
                $this->prophesize(EncryptInterface::class)->reveal()
            );

        $sut = new EncryptedCookiePersistenceFactory();
        $ecp = $sut($containerProphecy->reveal());

        $this->assertInstanceOf(EncryptedCookiePersistence::class, $ecp);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_will_throw_runtime_exceptions_if_session_configuration_is_missing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn([]);
        $containerProphecy
            ->get(EncryptInterface::class)
            ->willReturn(
                $this->prophesize(EncryptInterface::class)->reveal()
            );

        $sut = new EncryptedCookiePersistenceFactory();

        $this->expectException(RuntimeException::class);
        $ecp = $sut($containerProphecy->reveal());
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_will_throw_runtime_exceptions_if_necessary_configuration_is_missing(): void
    {
        $config = [
            'session' => [
                'cookie_name' => 'session',
                'cookie_path' => '/',
                'cache_limiter' => 'nocache',
                'expires' => 1200,
                'last_modified' => null,
                'cookie_ttl' => 86400,
                'cookie_domain' => null,
                'cookie_secure' => true,
                'cookie_http_only' => true,
            ]
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        // this code removes one configuration value and attempts the operation, it should result
        // in an exception, which we count as a good thing. It then loops around and repeats for
        // all the configured values.
        for ($i = 0; $i < count($config['session']); $i++) {
            $value = array_keys($config['session'])[$i];
            $badConfig = $config;
            unset($badConfig['session'][$value]);

            $containerProphecy->get('config')->willReturn($badConfig);
            $containerProphecy
                ->get(EncryptInterface::class)
                ->willReturn(
                    $this->prophesize(EncryptInterface::class)->reveal()
                );

            $sut = new EncryptedCookiePersistenceFactory();

            try {
                $ecp = $sut($containerProphecy->reveal());
            } catch (\RuntimeException $re) {
                $this->addToAssertionCount(1);
                continue;
            }

            throw new ExpectationFailedException("Failed to throw exception when $value was removed");
        }
    }
}
