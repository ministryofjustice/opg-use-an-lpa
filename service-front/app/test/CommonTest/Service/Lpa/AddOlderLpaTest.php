<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\OlderLpaApiResponse;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class AddOlderLpaTest
 *
 * @property array olderLpa
 *
 * @package CommonTest\Service\Lpa
 * @coversDefaultClass \Common\Service\Lpa\AddOlderLpa
 */
class AddOlderLpaTest extends TestCase
{
    /** @var \Prophecy\Prophecy\ObjectProphecy|ApiClient */
    private $apiClientProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->prophesize(ApiClient::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->olderLpa = [
            'reference_number' => 700000000000,
            'first_names' => 'Test',
            'last_name' => 'Example',
            'dob' => (new DateTime('1980-11-07')),
            'postcode' => 'EX4 MPL',
        ];

        $this->apiClientProphecy->setUserTokenHeader('12-1-1-1-1234')->shouldBeCalled();
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_will_successfully_add_an_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob']),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willReturn(
                [
                    'lpa-id' => (string) $this->olderLpa['reference_number'],
                    'actor-id' => '700000000001'
                ]
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());

        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob']->format('Y-m-d'),
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);




        $this->assertEquals(OlderLpaApiResponse::SUCCESS, $result->getResponse());
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_add_an_ineligible_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willThrow(
                new ApiException(
                    'LPA not eligible due to registration date',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);

        $this->assertEquals(OlderLpaApiResponse::NOT_ELIGIBLE, $result->getResponse());
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_add_due_to_a_bad_data_match(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null

                ]
            )->willThrow(
                new ApiException(
                    'LPA details do not match',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);

        $this->assertEquals(OlderLpaApiResponse::DOES_NOT_MATCH, $result->getResponse());
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_add_due_to_active_activation_key(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willThrow(
                new ApiException(
                    'LPA has an activation key already',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    [
                        //'activation_key_created' => (new DateTime())->modify('-14 days')->format('Y-m-d')
                        'lpa_type' => 'pfa',
                        'donor_name' => ['abc','lmn','xyz']
                    ]

                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);

        $this->assertEquals(OlderLpaApiResponse::HAS_ACTIVATION_KEY, $result->getResponse());
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::notFoundReturned
     */
    public function it_will_fail_to_add_due_to_not_finding_the_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willThrow(
                new ApiException(
                    'Not Found',
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);

        $this->assertEquals(OlderLpaApiResponse::NOT_FOUND, $result->getResponse());
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_will_fail_to_add_due_to_an_api_exception(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willThrow(
                new ApiException(
                    'Service Error',
                    StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service Error');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);
    }

    /**
     * @test
     * @covers ::__invoke
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_add_due_to_an_unknown_request_exception(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/lpas/request-letter',
                [
                    'reference_number' => (string) $this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => null
                ]
            )->willThrow(
                new ApiException(
                    'This message will not be recognised',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $sut = new AddOlderLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $data = [
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            null
        ];
        $result  = $sut($data);
    }
}
