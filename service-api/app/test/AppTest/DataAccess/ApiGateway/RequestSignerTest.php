<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSigner;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

class RequestSignerTest extends TestCase
{
    /** @test */
    public function it_signs_request_with_a_supplied_static_token(): void
    {
        $signatureV4Prophecy = $this->prophesize(SignatureV4::class);

        $requestProphecy = $this->prophesize(RequestInterface::class);
        $requestProphecy
            ->withAddedHeader('Authorization', 'test_token')
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $signer = new RequestSigner($signatureV4Prophecy->reveal(), 'test_token');

        $request = $signer->sign($requestProphecy->reveal());
    }

    /** @test */
    public function it_signs_a_request_with_the_aws_signer(): void
    {
        $signatureV4Prophecy = $this->prophesize(SignatureV4::class);

        $requestProphecy = $this->prophesize(RequestInterface::class);

        $signatureV4Prophecy->signRequest($requestProphecy->reveal(), Argument::type(CredentialsInterface::class))
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $signer = new RequestSigner($signatureV4Prophecy->reveal());

        $request = $signer->sign($requestProphecy->reveal());
    }
}
