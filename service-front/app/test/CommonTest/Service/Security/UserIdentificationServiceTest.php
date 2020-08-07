<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\UserIdentificationService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class UserIdentificationServiceTest extends TestCase
{
    /**
     * Because this request has no headers we're not actually testing that a unique ID is generated per request,
     * this test is therefore just a validation of the code not throwing errors.
     *
     * @test
     */
    public function it_can_uniquely_identify_a_request_with_no_headers()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new UserIdentificationService($loggerProphecy->reveal());

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals('224fb6e8b478de4a0d10bbeb92a07ffe095df3f9e1b1b50197d88f4c48192025', $id);
    }

    /**
     * @test
     * @dataProvider headerCombinations
     */
    public function it_can_uniquely_identify_a_request_with_headers(array $headers, string $expectedId)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->hasHeader(Argument::type('string'))->willReturn(false);

        foreach ($headers as $header) {
            $requestProphecy->hasHeader($header)->willReturn(true);
            $requestProphecy->getHeader($header)->willReturn('header-value');
        }

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new UserIdentificationService($loggerProphecy->reveal());

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals($expectedId, $id);
    }

    public function headerCombinations(): array
    {
        return [
            [ # the realistic bare minimum unique thing to track
                ['x-forwarded-for'],
                'c6f41b7d23a875f6b1ba03cea0207c8340563e2df9fc43cb1b331717b999d099'
            ],
            [ # the complete set
                [
                    'accept',
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'x-forwarded-for'
                ],
                '3afdc96b35e60a6c3d98fc06ca8647ad5a106c862503cb64f982d260928c7285'
            ],
            [ # not a complete set
                [
                    'accept',
                    'user-agent',
                    'x-forwarded-for'
                ],
                'f2978681b9f61976090c88df4dfce164513606996cf4d5c4203121a14eec13f9'
            ],
            [ # only use specified headers
                [
                    'accept',
                    'user-agent',
                    'not-a-real-header',
                    'x-forwarded-for'
                ],
                'f2978681b9f61976090c88df4dfce164513606996cf4d5c4203121a14eec13f9'
            ],
        ];
    }
}
