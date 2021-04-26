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

        $this->assertEquals('d3b534a6cf6e6e4f5bb7ba3442ef239f3d632a11d0f1a50a42bf0f271ffce331', $id);
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
            'the realistic bare minimum unique thing to track' => [
                ['x-forwarded-for'],
                '597967406e7009e87bfa34db426f795fc9248dd12af70290385fded6e46443a9'
            ],
            'the complete set' => [
                [
                    'accept',
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'x-forwarded-for'
                ],
                '92e7dd7306dbdd412c8d6b626b7c808f0c3fc692c9297aedf047ae918b11be58'
            ],
            'not a complete set' => [
                [
                    'accept',
                    'user-agent',
                    'x-forwarded-for'
                ],
                '833dccbeb6f8d0fea36924bafa0e3eaa8c4d565a36ed8742321e39bc5981ab61'
            ],
            'only use specified headers' => [
                [
                    'accept',
                    'user-agent',
                    'not-a-real-header',
                    'x-forwarded-for'
                ],
                '833dccbeb6f8d0fea36924bafa0e3eaa8c4d565a36ed8742321e39bc5981ab61'
            ],
        ];
    }
}
