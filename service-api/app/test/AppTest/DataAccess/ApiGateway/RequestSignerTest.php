<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSigner;
use Aws\Signature\SignatureV4;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

#[BackupGlobals(true)]
class RequestSignerTest extends TestCase
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
    public function it_signs_request_with_a_supplied_static_token(): void
    {
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $requestProphecy
            ->withHeader('Authorization', 'test_token')
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $signatureV4Prophecy = $this->prophesize(SignatureV4::class);
        $signatureV4Prophecy
            ->signRequest(Argument::any(), Argument::any())
            ->willReturnArgument(0);

        $signer = new RequestSigner($signatureV4Prophecy->reveal(), ['Authorization' => 'test_token']);

        $request = $signer->sign($requestProphecy->reveal());
    }

    #[Test]
    public function it_signs_a_request_with_the_aws_signer(): void
    {
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $signatureV4Prophecy = $this->prophesize(SignatureV4::class);
        $signatureV4Prophecy
            ->signRequest($requestProphecy->reveal(), Argument::any())
            ->shouldBeCalled()
            ->willReturnArgument(0);

        $signer = new RequestSigner($signatureV4Prophecy->reveal());

        $request = $signer->sign($requestProphecy->reveal());
    }
}
