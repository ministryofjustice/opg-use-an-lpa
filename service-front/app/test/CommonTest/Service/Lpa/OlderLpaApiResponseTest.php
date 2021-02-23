<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Service\Lpa\OlderLpaApiResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class OlderLpaApiResponseTest
 *
 * @package CommonTest\Service\Lpa
 * @coversDefaultClass \Common\Service\Lpa\OlderLpaApiResponse
 */
class OlderLpaApiResponseTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::validateResponseType
     */
    public function it_can_be_created_with_a_recognised_response_type(): void
    {
        $sut = new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, []);

        $this->assertInstanceOf(OlderLpaApiResponse::class, $sut);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::validateResponseType
     */
    public function it_throws_an_exception_with_an_unrecognised_response_type(): void
    {
        $this->expectException(RuntimeException::class);
        $sut = new OlderLpaApiResponse('BAD TYPE', []);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::validateResponseType
     * @covers ::getResponse
     * @covers ::getData
     */
    public function it_makes_available_the_type_and_passed_in_additional_data(): void
    {
        $data = [
            'test' => 'data'
        ];

        $sut = new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, $data);

        $this->assertEquals(OlderLpaApiResponse::SUCCESS, $sut->getResponse());
        $this->assertEquals($data, $sut->getData());
    }
}
