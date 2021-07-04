<?php

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResponse;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\Transformer\LpaAlreadyAddedResponseTransformer;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ArrayObject;
use RuntimeException;

/**
 * Class AddLpaTest
 *
 * @property array $data
 * @property AddLpa $addLpa
 * @property array $lpaArrayData
 * @property ArrayObject $lpaParsedData
 *
 * @package CommonTest\Service\Lpa
 * @coversDefaultClass \Common\Service\Lpa\AddLpa
 */
class AddLpaTest extends TestCase
{
    /** @var \Prophecy\Prophecy\ObjectProphecy|ApiClient */
    private $apiClientProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|ParseLpaData */
    private $parseLpaDataProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|LoggerInterface */
    private $loggerProphecy;
    /** @var \Prophecy\Prophecy\ObjectProphecy|LpaAlreadyAddedResponseTransformer */
    private $alreadyAddedTransformerProphecy;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->prophesize(ApiClient::class);
        $this->parseLpaDataProphecy = $this->prophesize(ParseLpaData::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->alreadyAddedTransformerProphecy = $this->prophesize(LpaAlreadyAddedResponseTransformer::class);

        $this->data = [
            'uid' => '700000000321',
            'actor-code' => '4UAL33PEQNAY',
            'dob' => '1980-11-07'
        ];

        $this->apiClientProphecy->setUserTokenHeader('12-1-1-1-1234')->shouldBeCalled();

        $this->addLpa = new AddLpa(
            $this->apiClientProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->parseLpaDataProphecy->reveal(),
            $this->alreadyAddedTransformerProphecy->reveal()
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
                'lpa' => $lpa,
                'actor' => $actor
            ]
        );

        $this->lpaArrayData = [
            'lpa' => [
                'id' => 1111,
                'uId' => $this->data['uid']
            ],
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'id' => 25,
                    'uId' => '700000000997',
                ]
            ]
        ];
    }

    /** @test */
    public function it_will_validate_the_data_and_fetch_the_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willReturn($this->lpaArrayData);

        $this->parseLpaDataProphecy
            ->__invoke($this->lpaArrayData)
            ->willReturn($this->lpaParsedData);

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_FOUND, $result->getResponse());
        $this->assertEquals($this->lpaParsedData, $result->getData());
    }

    /** @test */
    public function it_will_fail_to_add_an_lpa_which_has_already_been_added(): void
    {
        $response = [
            'donorName' => 'Another Person',
            'caseSubtype' => 'hw',
            'lpaActorToken' => 'wxyz-4321'
        ];

        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willThrow(
                new ApiException(
                    'LPA already added',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    $response
                )
            );

        $dto = new LpaAlreadyAddedResponse();
        $dto->setDonorName($response['donorName']);
        $dto->setCaseSubtype($response['caseSubtype']);
        $dto->setLpaActorToken($response['lpaActorToken']);

        $this->alreadyAddedTransformerProphecy
            ->__invoke($response)
            ->willReturn($dto);

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_ALREADY_ADDED, $result->getResponse());
        $this->assertEquals($dto, $result->getData());
    }

    /** @test */
    public function it_will_fail_to_add_an_lpa_which_is_not_registered(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willThrow(
                new ApiException(
                    'LPA status is not registered',
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    null,
                    []
                )
            );

        $this->parseLpaDataProphecy
            ->__invoke([])
            ->willReturn(new ArrayObject());

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_NOT_ELIGIBLE, $result->getResponse());
        $this->assertEquals([], $result->getData());
    }

    /** @test */
    public function it_will_fail_to_add_an_lpa_if_code_validation_fails(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willThrow(
                new ApiException(
                    'Code validation failed',
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    null,
                    []
                )
            );

        $this->parseLpaDataProphecy
            ->__invoke([])
            ->willReturn(new ArrayObject());

        $result = $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_NOT_FOUND, $result->getResponse());
        $this->assertEquals([], $result->getData());
    }

    /** @test */
    public function it_will_fail_to_add_due_to_an_api_exception(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
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
        $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );
    }

    /** @test */
    public function it_will_fail_to_add_due_to_an_unknown_request_exception(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/validate',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willThrow(
                new ApiException(
                    'This message will not be recognised',
                    StatusCodeInterface::STATUS_BAD_REQUEST
                )
            );

        $this->parseLpaDataProphecy
            ->__invoke([])
            ->willReturn(new ArrayObject());

        $this->expectException(RuntimeException::class);
        $this->addLpa->validate(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );
    }

    /** @test */
    public function it_will_confirm_adding_the_lpa(): void
    {
        // this verifies the lpa was added successfully
        $this->lpaArrayData['user-lpa-actor-token'] = '1234-abcd';

        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/confirm',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willReturn($this->lpaArrayData);

        $result = $this->addLpa->confirm(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_SUCCESS, $result->getResponse());
    }

    /** @test */
    public function it_will_return_failed_event_code_if_failed_when_adding_the_lpa(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/confirm',
                [
                    'actor-code' => $this->data['actor-code'],
                    'uid' => $this->data['uid'],
                    'dob' => $this->data['dob']
                ]
            )->willReturn($this->lpaArrayData);

        $result = $this->addLpa->confirm(
            '12-1-1-1-1234',
            $this->data['actor-code'],
            $this->data['uid'],
            $this->data['dob']
        );

        $this->assertEquals(AddLpaApiResponse::ADD_LPA_FAILURE, $result->getResponse());
    }
}
