<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\Token;

use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifier;
use AppTest\OidcUtilities;
use DateTimeImmutable;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;

class OutOfBandCoreIdentityVerifierTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $sut = new OutOfBandCoreIdentityVerifier(
            $this->prophesize(JWK::class)->reveal(),
            'issuer',
            'clientId',
            $this->prophesize(ClockInterface::class)->reveal(),
        );

        $this->assertInstanceOf(OutOfBandCoreIdentityVerifier::class, $sut);
    }

    /**
     * Tending towards an integration test this one but there's no easy way to do thi
     * without a major refactor of the verifier (and a lot of additional complexity)
     *
     * @test
     */
    public function it_can_verify_a_valid_jwt(): void
    {
        [$signedToken, $publicKey] = OidcUtilities::generateCoreIdentityToken('fakeSub', 'fakeBirthday');

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn(new DateTimeImmutable());

        $sut = new OutOfBandCoreIdentityVerifier(
            JWKFactory::createFromKey($publicKey),
            'http://identity.one-login-mock/',
            'clientId',
            $clock->reveal(),
        );

        $credentials = $sut->verify($signedToken);

        $this->assertSame(
            ['birthDate' => [['value' => 'fakeBirthday']]],
            $credentials,
        );
    }
}
