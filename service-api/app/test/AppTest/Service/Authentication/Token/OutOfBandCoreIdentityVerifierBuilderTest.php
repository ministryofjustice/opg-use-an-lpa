<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\Token;

use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifier;
use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadataInterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;

/**
 * @coversDefaultClass \App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilder
 */
class OutOfBandCoreIdentityVerifierBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers ::__construct()
     */
    public function it_can_be_instantiated(): void
    {
        $sut = new OutOfBandCoreIdentityVerifierBuilder(
            'https://fakeUrl',
            $this->prophesize(ClockInterface::class)->reveal(),
        );

        $this->assertInstanceOf(OutOfBandCoreIdentityVerifierBuilder::class, $sut);
    }

    /**
     * @test
     * @covers ::build()
     */
    public function it_can_build_a_verifier(): void
    {
        $metadataInterface = $this->prophesize(ClientMetadataInterface::class);
        $metadataInterface->toArray()->willReturn(['client_id' => 'fakeClientId']);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface
            ->getMetadata()
            ->willReturn($metadataInterface->reveal());

        $sut = new OutOfBandCoreIdentityVerifierBuilder(
            'https://fakeUrl',
            $this->prophesize(ClockInterface::class)->reveal(),
        );

        $result = $sut->build(
            $clientInterface->reveal(),
            $this->prophesize(JWK::class)->reveal(),
        );

        $this->assertInstanceOf(OutOfBandCoreIdentityVerifier::class, $result);
    }
}
