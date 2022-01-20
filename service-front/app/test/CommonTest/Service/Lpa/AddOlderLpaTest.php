<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use App\Exception\BadRequestException;
use Common\Entity\CaseActor;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\OlderLpaMatchResponse;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class AddOlderLpaTest
 *
 * @property array olderLpa
 * @property AddOlderLpa $sut
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
    /** @var \Prophecy\Prophecy\ObjectProphecy|ParseActivationKeyExistsResponse */
    private $parseKeyExistsProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|ParseLpaAlreadyAddedResponse */
    private $parseAlreadyAddedProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|ParseOlderLpaMatchResponse */
    private $parseOlderLpaMatchProphecy;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->prophesize(ApiClient::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->parseKeyExistsProphecy = $this->prophesize(ParseActivationKeyExistsResponse::class);
        $this->parseAlreadyAddedProphecy = $this->prophesize(ParseLpaAlreadyAddedResponse::class);
        $this->parseOlderLpaMatchProphecy = $this->prophesize(ParseOlderLpaMatchResponse::class);

        $this->olderLpa = [
            'reference_number'  => 700000000000,
            'first_names'       => 'Test',
            'last_name'         => 'Example',
            'dob'               => new DateTime('1980-11-07'),
            'postcode'          => 'EX4 MPL',
        ];

        $this->apiClientProphecy->setUserTokenHeader('12-1-1-1-1234')->shouldBeCalled();

        $this->sut = new AddOlderLpa(
            $this->apiClientProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->parseAlreadyAddedProphecy->reveal(),
            $this->parseKeyExistsProphecy->reveal(),
            $this->parseOlderLpaMatchProphecy->reveal()
        );
    }

    /**
     * @test
     * @covers ::validate
     */
    public function it_will_successfully_finds_an_lpa(): void
    {
        $response = [
            'lpa-id' => (string)$this->olderLpa['reference_number'],
            'actor-id' => '700000000001',
            'caseSubtype' => 'hw',
            'donor' => [
                'uId' => '',
                'firstname' => '',
                'middlenames' => '',
                'surname' => '',
            ],
        ];

        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'          => (string) $this->olderLpa['reference_number'],
                    'first_names'               => $this->olderLpa['first_names'],
                    'last_name'                 => $this->olderLpa['last_name'],
                    'dob'                       => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'                  => $this->olderLpa['postcode'],
                    'force_activation_key'      => false
                ]
            )->willReturn($response);

        $donor = new CaseActor();
        $donor->setUId($response['donor']['uId']);
        $donor->setFirstname($response['donor']['firstname']);
        $donor->setMiddlenames($response['donor']['middlenames']);
        $donor->setSurname($response['donor']['surname']);

        $dto = new OlderLpaMatchResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype($response['caseSubtype']);

        $this->parseOlderLpaMatchProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::FOUND, $result->getResponse());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_validate_an_ineligible_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'          => (string) $this->olderLpa['reference_number'],
                    'first_names'               => $this->olderLpa['first_names'],
                    'last_name'                 => $this->olderLpa['last_name'],
                    'dob'                       => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'                  => $this->olderLpa['postcode'],
                    'force_activation_key'      => false
                ]
            )->willThrow(
                new ApiException(
                    'LPA not eligible due to registration date',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::NOT_ELIGIBLE, $result->getResponse());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_validate_due_to_a_bad_data_match(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'          => (string) $this->olderLpa['reference_number'],
                    'first_names'               => $this->olderLpa['first_names'],
                    'last_name'                 => $this->olderLpa['last_name'],
                    'dob'                       => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'                  => $this->olderLpa['postcode'],
                    'force_activation_key'      => false

                ]
            )->willThrow(
                new ApiException(
                    'LPA details do not match',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::DOES_NOT_MATCH, $result->getResponse());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_let_know_user_LPA_has_an_active_activation_key(): void
    {
        $createdDate = (new DateTime())->modify('-14 days');
        $response = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'activationKeyDueDate' => $createdDate->format('c')
        ];

        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'LPA has an activation key already',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    $response
                )
            );

        $donor = new CaseActor();
        $donor->setUId($response['donor']['uId']);
        $donor->setFirstname($response['donor']['firstname']);
        $donor->setMiddlenames($response['donor']['middlenames']);
        $donor->setSurname($response['donor']['surname']);

        $dto = new ActivationKeyExistsResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype($response['caseSubtype']);

        $this->parseKeyExistsProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::HAS_ACTIVATION_KEY, $result->getResponse());
        $this->assertEquals($dto, $result->getData());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_let_know_user_they_have_already_requested_an_activation_key_for_an_LPA(): void
    {
        $response = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
        ];

        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'Activation key already requested for LPA',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    $response
                )
            );

        $donor = new CaseActor();
        $donor->setUId($response['donor']['uId']);
        $donor->setFirstname($response['donor']['firstname']);
        $donor->setMiddlenames($response['donor']['middlenames']);
        $donor->setSurname($response['donor']['surname']);

        $dto = new ActivationKeyExistsResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype($response['caseSubtype']);

        $this->parseKeyExistsProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::KEY_ALREADY_REQUESTED, $result->getResponse());
        $this->assertEquals($dto, $result->getData());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_fail_if_they_have_already_added_the_LPA(): void
    {
        $response = [
            'donor'         => [
                'uId'           => '12345',
                'firstname'     => 'Example',
                'middlenames'   => 'Donor',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'wxyz-4321'
        ];

        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'LPA already added',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    $response
                )
            );

        $donor = new CaseActor();
        $donor->setUId($response['donor']['uId']);
        $donor->setFirstname($response['donor']['firstname']);
        $donor->setMiddlenames($response['donor']['middlenames']);
        $donor->setSurname($response['donor']['surname']);

        $dto = new LpaAlreadyAddedResponse();
        $dto->setDonor($donor);
        $dto->setCaseSubtype($response['caseSubtype']);
        $dto->setLpaActorToken($response['lpaActorToken']);

        $this->parseAlreadyAddedProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::LPA_ALREADY_ADDED, $result->getResponse());
        $this->assertEquals($dto, $result->getData());
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::notFoundReturned
     */
    public function it_will_fail_to_validate_due_to_not_finding_the_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'Not Found',
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $result = $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );

        $this->assertEquals(OlderLpaApiResponse::NOT_FOUND, $result->getResponse());
    }

    /**
     * @test
     * @covers ::validate
     */
    public function it_will_fail_to_validate_due_to_an_api_exception(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'Service Error',
                    StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
                )
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service Error');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );
    }

    /**
     * @test
     * @covers ::validate
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_validate_due_to_an_unknown_request_exception(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/older-lpa/validate',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => false
                ]
            )->willThrow(
                new ApiException(
                    'This message will not be recognised',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $this->expectException(RuntimeException::class);
        $this->sut->validate(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode']
        );
    }

    /**
     * @test
     * @covers ::confirm
     * @covers ::badRequestReturned
     */
    public function generate_new_activation_key_again_for_user(): void
    {
        $response = [];

        $this->apiClientProphecy
            ->httpPatch(
                '/v1/older-lpa/confirm',
                [
                    'reference_number'      => (string)$this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => true,
                ]
            )->willReturn($response);

        $dto = new OlderLpaMatchResponse();
        $this->parseOlderLpaMatchProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->sut->confirm(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            true
        );

        $this->assertEquals(OlderLpaApiResponse::SUCCESS, $result->getResponse());
    }

    /**
     * @test
     * @covers ::confirm
     */
    public function it_will_fail_to_confirm_due_to_an_api_exception(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/older-lpa/confirm',
                [
                    'reference_number' => (string)$this->olderLpa['reference_number'],
                    'first_names' => $this->olderLpa['first_names'],
                    'last_name' => $this->olderLpa['last_name'],
                    'dob' => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode' => $this->olderLpa['postcode'],
                    'force_activation_key' => true
                ]
            )->willThrow(
                new ApiException(
                    'Service Error',
                    StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
                )
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service Error');

        $this->sut->confirm(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            true
        );
    }

    /**
     * @test
     * @covers ::confir
     */
    public function it_will_fail_to_add_lpa_when_lpa_is_not_cleansed(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/older-lpa/confirm',
                [
                    'reference_number'      => (string) $this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => true
                ]
            )->willThrow(
                new ApiException(
                    'LPA needs cleansing',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    ['actor_id' => '1234'],
                )
            );

        $result = $this->sut->confirm(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            true
        );

        $this->assertEquals('1234', $result->getData()['actor_id']);
        $this->assertEquals(OlderLpaApiResponse::OLDER_LPA_NEEDS_CLEANSING, $result->getResponse());
    }

    /**
     * @test
     * @covers ::confirm
     * @covers ::badRequestReturned
     */
    public function it_will_fail_to_add_lpa_due_to_an_unknown_request_exception(): void
    {
        $this->apiClientProphecy
            ->httpPatch(
                '/v1/older-lpa/confirm',
                [
                    'reference_number'      => (string)$this->olderLpa['reference_number'],
                    'first_names'           => $this->olderLpa['first_names'],
                    'last_name'             => $this->olderLpa['last_name'],
                    'dob'                   => ($this->olderLpa['dob'])->format('Y-m-d'),
                    'postcode'              => $this->olderLpa['postcode'],
                    'force_activation_key'  => true,
                ]
            )->willThrow(
                new ApiException(
                    'This message will not be recognised',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $this->expectException(ApiException::class);
        $this->sut->confirm(
            '12-1-1-1-1234',
            $this->olderLpa['reference_number'],
            $this->olderLpa['first_names'],
            $this->olderLpa['last_name'],
            $this->olderLpa['dob'],
            $this->olderLpa['postcode'],
            true
        );
    }
}
