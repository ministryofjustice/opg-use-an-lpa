<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\UserIdentificationService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

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

        $service = new UserIdentificationService();

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals('da97c8ccc40114128dcaeff8be27d9481c116eb01cbf9007c0e1a02d2590a197', $id);
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

        $service = new UserIdentificationService();

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals($expectedId, $id);
    }

    public function headerCombinations(): array
    {
        return [
            [ # the realistic bare minimum unique thing to track
                ['x-forwarded-for'],
                'f9bcf7fa2cc63932adedba2696f4c8b6c86404c420ea201310dcd13b73710bde'
            ],
            [ # the complete set
                [
                    'accept',
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'x-forwarded-for'
                ],
                'a3b74c076c52c08495a7c37135db24becdeff0bcd88a3b40e0b3279d7349ef66'
            ],
            [ # not a complete set
                [
                    'accept',
                    'user-agent',
                    'x-forwarded-for'
                ],
                'be4e45e3a7274376b3e1f9fdc9e96c7af8eb9cbcfd397a30266ac0ab0ec9fa54'
            ],
            [ # only use specified headers
                [
                    'accept',
                    'user-agent',
                    'not-a-real-header',
                    'x-forwarded-for'
                ],
                'be4e45e3a7274376b3e1f9fdc9e96c7af8eb9cbcfd397a30266ac0ab0ec9fa54'
            ],
        ];
    }
}
