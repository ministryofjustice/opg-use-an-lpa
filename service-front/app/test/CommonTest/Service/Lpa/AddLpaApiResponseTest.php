<?php

namespace CommonTest\Service\Lpa;

use Common\Service\Lpa\AddLpaApiResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ArrayObject;

class AddLpaApiResponseTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_a_recognised_response_type(): void
    {
        $sut = new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_NOT_FOUND, new ArrayObject());

        $this->assertInstanceOf(AddLpaApiResponse::class, $sut);
    }

    /** @test */
    public function it_throws_an_exception_with_an_unrecognised_response_type(): void
    {
        $this->expectException(RuntimeException::class);
        new AddLpaApiResponse('BAD TYPE', new ArrayObject());
    }

    /** @test */
    public function it_makes_available_the_type_and_passed_in_additional_data(): void
    {
        $data = [
            'test' => 'data'
        ];

        $sut = new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_FOUND, new ArrayObject($data));

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_FOUND, $sut->getResponse());
        $this->assertEquals(new ArrayObject($data), $sut->getData());
    }
}
