<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\DataAccess\ApiGateway\InstructionsAndPreferencesImagesFactory;
use App\DataAccess\ApiGateway\RequestSigner;
use App\Service\Log\RequestTracing;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Exception;

class InstructionsAndPreferencesImagesFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_an_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'iap_images_api' => [
                        'endpoint' => 'test',
                    ],
                ]
            );

        $containerProphecy
            ->get(HttpClient::class)
            ->willReturn($this->prophesize(HttpClient::class)->reveal());

        $containerProphecy
            ->get(RequestSigner::class)
            ->willReturn($this->prophesize(RequestSigner::class)->reveal());

        $containerProphecy
            ->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('test-trace-id');

        $factory = new InstructionsAndPreferencesImagesFactory();

        $instructionsAndPreferencesImages = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(InstructionsAndPreferencesImages::class, $instructionsAndPreferencesImages);
    }

    /** @test */
    public function it_fails_with_exception_when_config_missing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'iap_images_api' => [],
                ]
            );

        $factory = new InstructionsAndPreferencesImagesFactory();

        $this->expectException(Exception::class);
        $instructionsAndPreferencesImages = $factory($containerProphecy->reveal());
    }
}
