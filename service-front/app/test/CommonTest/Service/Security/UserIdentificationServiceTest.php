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

        $service = new UserIdentificationService('unique-salt');

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals('a84da0c3de11c7c328e54575bc78de9947ead0a1e1b6154c1043bc24b715c5b6', $id);
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

        $service = new UserIdentificationService('unique-salt');

        $id = $service->id($requestProphecy->reveal());

        $this->assertEquals($expectedId, $id);
    }

    public function headerCombinations(): array
    {
        return [
            [ # the realistic bare minimum unique thing to track
                ['x-forwarded-for'],
                'd483dd46a77a4af929768603fbe558fe2551355accbc23fcad00ca9459ff1f2e'
            ],
            [ # the complete set
                [
                    'accept',
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'x-forwarded-for'
                ],
                '7602cb507e99785a96b6028c8426603d4052f19f5c19de8d081dad5495a9a206'
            ],
            [ # not a complete set
                [
                    'accept',
                    'user-agent',
                    'x-forwarded-for'
                ],
                'fa73c09e905dfdd94c5fbe955f60dec7f2ce156686d6b5ee9ebbb7d5c2ca45eb'
            ],
            [ # only use specified headers
                [
                    'accept',
                    'user-agent',
                    'not-a-real-header',
                    'x-forwarded-for'
                ],
                'fa73c09e905dfdd94c5fbe955f60dec7f2ce156686d6b5ee9ebbb7d5c2ca45eb'
            ],
        ];
    }

    /** @test */
    public function the_hashing_salt_is_used_in_the_hash()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $serviceOne = new UserIdentificationService('unique-salt');
        $idOne = $serviceOne->id($requestProphecy->reveal());

        $serviceTwo = new UserIdentificationService('second-unique-salt');
        $idTwo = $serviceTwo->id($requestProphecy->reveal());

        $this->assertEquals('a84da0c3de11c7c328e54575bc78de9947ead0a1e1b6154c1043bc24b715c5b6', $idOne);
        $this->assertNotEquals($idOne, $idTwo);
    }
}
