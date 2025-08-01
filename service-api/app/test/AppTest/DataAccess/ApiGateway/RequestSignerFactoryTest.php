<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\GenerateJWT;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use App\Exception\RequestSigningException;
use App\Service\Secrets\LpaDataStoreSecretManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

#[BackupGlobals(true)]
class RequestSignerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        // Keys from the documentation
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
    }

    public function tearDown(): void
    {
        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }

    #[Test]
    public function it_creates_an_request_signer_without_config(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new RequestSignerFactory($containerProphecy->reveal());

        $this->expectNotToPerformAssertions();
        $factory();
    }

    #[Test]
    public function it_creates_an_data_store_lpas_configured_signer(): void
    {
        $jwtGenerator = $this->prophesize(GenerateJWT::class);
        $jwtGenerator
            ->__invoke(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->will(function (array $args) {
                Assert::assertStringContainsString(
                    'urn:opg:poas:use:users:my_user_identifier',
                    $args[1]->getPayload()
                );

                return 'signed_jwt_string';
            });

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);
        $containerProphecy
            ->get(LpaDataStoreSecretManager::class)
            ->willReturn($this->prophesize(LpaDataStoreSecretManager::class)->reveal());
        $containerProphecy
            ->get(GenerateJWT::class)
            ->willReturn($jwtGenerator->reveal());

        $factory = new RequestSignerFactory($containerProphecy->reveal());

        $factory(SignatureType::DataStoreLpas, 'my_user_identifier');
    }

    #[Test]
    public function it_handles_container_exceptions(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willThrow($this->prophesize(ContainerExceptionInterface::class)->reveal());

        $factory = new RequestSignerFactory($containerProphecy->reveal());

        $this->expectException(RequestSigningException::class);
        $factory();
    }
}
