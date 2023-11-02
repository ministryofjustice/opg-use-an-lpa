<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Log\EventCodes;
use Common\Service\Security\UserIdentificationService;
use Common\Service\Security\UserIdentity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass UserIdentificationService
 */
class UserIdentificationServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Because this request has no headers we're not actually testing that a unique ID is generated per request,
     * this test is therefore just a validation of the code not throwing errors.
     *
     * @test
     * @covers ::__construct
     * @covers ::id
     */
    public function it_can_uniquely_identify_a_request_with_no_headers(): void
    {
        /**
 * @var ObjectProphecy|LoggerInterface $loggerProphecy
*/
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new UserIdentificationService($loggerProphecy->reveal());

        $id = $service->id([], null);

        $this->assertInstanceOf(UserIdentity::class, $id);
        $this->assertEquals('da11b962a28412cd40253f6047801b5fc0dd01503b475e88eaa58f332c37c9d5', $id->hash());
        $this->assertEquals('da11b962a28412cd40253f6047801b5fc0dd01503b475e88eaa58f332c37c9d5', (string) $id);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::id
     */
    public function it_logs_a_identity_mismatch(): void
    {
        /**
 * @var ObjectProphecy|LoggerInterface $loggerProphecy
*/
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->debug(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalled();
        $loggerProphecy
            ->notice(
                Argument::type('string'),
                Argument::that(
                    function ($parameter): bool {
                        $this->assertIsArray($parameter);
                        $this->assertArrayHasKey('event_code', $parameter);
                        $this->assertEquals(EventCodes::IDENTITY_HASH_CHANGE, $parameter['event_code']);

                        return true;
                    }
                )
            )
            ->shouldBeCalled();

        $service = new UserIdentificationService($loggerProphecy->reveal());

        $id = $service->id([], 'a-different-id');
    }

    /**
     * @test
     * @dataProvider headerCombinations
     * @covers       ::__construct
     * @covers       ::id
     */
    public function it_can_uniquely_identify_a_request_with_headers(array $headers, string $expectedId): void
    {
        /**
 * @var ObjectProphecy|LoggerInterface $loggerProphecy
*/
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new UserIdentificationService($loggerProphecy->reveal());

        $parsedHeaders = [];
        foreach ($headers as $header) {
            $parsedHeaders[$header] = ['header-value'];
        }

        $id = $service->id($parsedHeaders, null);

        $this->assertEquals($expectedId, (string) $id);
    }

    public function headerCombinations(): array
    {
        return [
            'the realistic bare minimum unique thing to track' => [
                ['x-forwarded-for'],
                'cf1c9b26b75aac2a7a503639dbf9a6b5ec73dbafa05e895805971c6d22d05204',
            ],
            'the complete set'                                 => [
                [
                    'accept',
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'x-forwarded-for',
                ],
                'cbec0bcf9955ce05bf7a364d396c4be06325ccafe13d01280db019ad78564f71',
            ],
            'not a complete set'                               => [
                [
                    'user-agent',
                    'x-forwarded-for',
                ],
                '84f794c1ea68e208bff93d5a5b28cc6f4c5b78c5fb12e494ed3d2fb8bc5cf4de',
            ],
            'only use specified headers'                       => [
                [
                    'accept-encoding',
                    'accept-language',
                    'user-agent',
                    'not-a-real-header',
                    'x-forwarded-for',
                ],
                'ca93bf8e2e9a9f221c2aa9c67d207655693d82684af910b0d3ba14300736b4e3',
            ],
        ];
    }
}
