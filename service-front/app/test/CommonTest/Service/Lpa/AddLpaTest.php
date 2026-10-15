<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Context\UserContext;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResult;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @property array $data
 * @property AddLpa $addLpa
 * @property array $lpaArrayData
 * @property ArrayObject $lpaParsedData
 */
#[CoversClass(AddLpa::class)]
class AddLpaTest extends TestCase
{
    private ApiClient&MockObject $apiClientProphecy;
    private ParseLpaData&MockObject $parseLpaDataProphecy;
    private LoggerInterface&MockObject $loggerProphecy;
    private ParseLpaAlreadyAdded&MockObject $parseAlreadyAddedProphecy;

    public function setUp(): void
    {
        $this->apiClientProphecy         = $this->createMock(ApiClient::class);
        $this->parseLpaDataProphecy      = $this->createMock(ParseLpaData::class);
        $this->loggerProphecy            = $this->createMock(LoggerInterface::class);
        $this->parseAlreadyAddedProphecy = $this->createMock(ParseLpaAlreadyAdded::class);

        $this->data = [
            'uid'        => '700000000321',
            'actor-code' => '4UAL33PEQNAY',
            'dob'        => '1980-11-07',
        ];

        $this->apiClientProphecy->expects($this->once())->method('setUserContext')
            ->with(new UserContext('12-1-1-1-1234'));

        $this->addLpa = new AddLpa(
            $this->apiClientProphecy,
            $this->loggerProphecy,
            $this->parseLpaDataProphecy,
            $this->parseAlreadyAddedProphecy
        );

        $actor = new CaseActor();
        $actor->setId(2222);
        $actor->setUId('700000000997');
        $actor->setFirstname('Firstname');
        $actor->setSurname('Surname');

        $lpa = new Lpa();
        $lpa->setId(1111);
        $lpa->setUId($this->data['uid']);
        $lpa->setDonor($actor);
        $lpa->setCaseSubtype('pfa');

        $this->lpaParsedData = new ArrayObject(
            [
                'lpa'   => $lpa,
                'actor' => $actor,
            ]
        );

        $this->lpaArrayData = [
            'lpa'   => [
                'id'  => 1111,
                'uId' => $this->data['uid'],
            ],
            'actor' => [
                'type'    => 'primary-attorney',
                'details' => [
                    'id'  => 25,
                    'uId' => '700000000997',
                ],
            ],
        ];
    }

    #[Test]
    public function it_will_validate_the_data_and_fetch_the_lpa(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willReturn($this->lpaArrayData);

        $this->parseLpaDataProphecy
            ->method('__invoke')->with($this->lpaArrayData)
            ->willReturn($this->lpaParsedData);

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_FOUND, $result->getResponse());
        $this->assertEquals($this->lpaParsedData, $result->getData());
    }

    #[Test]
    public function it_will_fail_to_add_an_lpa_which_has_already_been_added(): void
    {
        $response = [
            'donor'         => [
                'uId'         => '12345',
                'firstname'   => 'Example',
                'middlenames' => 'Donor',
                'surname'     => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'wxyz-4321',
        ];

        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willThrowException(
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

        $dto = new LpaAlreadyAdded();
        $dto->setDonor($donor);
        $dto->setCaseSubtype($response['caseSubtype']);
        $dto->setLpaActorToken($response['lpaActorToken']);

        $this->parseAlreadyAddedProphecy
            ->method('__invoke')->with($response)
            ->willReturn($dto);

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_ALREADY_ADDED, $result->getResponse());
        $this->assertEquals($dto, $result->getData());
    }

    #[Test]
    public function it_will_fail_to_add_an_lpa_which_is_not_registered(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willThrowException(
                new ApiException(
                    'LPA status is not registered',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    []
                )
            );

        $this->parseLpaDataProphecy
            ->method('__invoke')->with([])
            ->willReturn(new ArrayObject());

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_NOT_ELIGIBLE, $result->getResponse());
        $this->assertEquals([], $result->getData());
    }

    #[Test]
    public function it_will_fail_to_add_an_lpa_if_code_validation_fails(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willThrowException(
                new ApiException(
                    'Code validation failed',
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    null,
                    []
                )
            );

        $this->parseLpaDataProphecy
            ->method('__invoke')->with([])
            ->willReturn(new ArrayObject());

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_NOT_FOUND, $result->getResponse());
        $this->assertEquals([], $result->getData());
    }

    #[Test]
    public function it_will_fail_to_add_due_to_an_api_exception(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willThrowException(
                new ApiException(
                    'Service Error',
                    StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
                )
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service Error');
        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );
    }

    #[Test]
    public function it_will_fail_to_add_due_to_an_unknown_request_exception(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willThrowException(
                new ApiException(
                    'This message will not be recognised',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $this->parseLpaDataProphecy
            ->method('__invoke')->with([])
            ->willReturn(new ArrayObject());

        $this->expectException(RuntimeException::class);
        $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );
    }

    #[Test]
    public function it_will_confirm_adding_the_lpa(): void
    {
        // this verifies the lpa was added successfully
        $this->lpaArrayData['user-lpa-actor-token'] = '1234-abcd';

        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/confirm',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willReturn($this->lpaArrayData);

        $result = $this->addLpa->confirm(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_SUCCESS, $result->getResponse());
    }

    #[Test]
    public function it_will_return_failed_event_code_if_failed_when_adding_the_lpa(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/add-lpa/confirm',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid'        => $this->data['uid'],
                    'dob'        => $this->data['dob'],
                ]
            )->willReturn($this->lpaArrayData);

        $result = $this->addLpa->confirm(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResult::ADD_LPA_FAILURE, $result->getResponse());
    }
}
