<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\GroupLpas;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\PopulateLpaMetadata;
use Common\Service\Lpa\SortLpas;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LpaServiceTest extends TestCase
{
    /** @var Client */
    private $apiClientProphecy;
    /** @var LoggerInterface */
    private $loggerProphecy;
    /** @var ParseLpaData */
    private $parseLpaData;
    /** @var PopulateLpaMetadata */
    private $populateLpaMetadata;
    /** @var SortLpas */
    private $sortLpas;
    /** @var GroupLpas */
    private $groupLpas;
    /** @var LpaService */
    private $lpaService;

    public function setUp()
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);
        $this->parseLpaData = $this->prophesize(ParseLpaData::class);
        $this->populateLpaMetadata = $this->prophesize(PopulateLpaMetadata::class);
        $this->sortLpas = $this->prophesize(SortLpas::class);
        $this->groupLpas = $this->prophesize(GroupLpas::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->lpaService = new LpaService(
            $this->apiClientProphecy->reveal(),
            $this->parseLpaData->reveal(),
            $this->populateLpaMetadata->reveal(),
            $this->sortLpas->reveal(),
            $this->groupLpas->reveal(),
            $this->loggerProphecy->reveal()
        );
    }

    /** @test */
    public function it_gets_a_list_of_lpas_for_a_user()
    {
        $token = '01234567-01234-01234-01234-012345678901';

        $lpaData = [
            '0123-01-01-01-012345' => [
                'lpa' => [
                    'uId' => '123456789012',
                    'donor' => [
                        'uId' => '123456789012',
                        'dob' => '1980-01-01'
                    ]
                ]
            ]
        ];

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' => new ArrayObject(), // data content doesn't matter for this test
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->httpGet('/v1/lpas')->willReturn($lpaData);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpas = $this->lpaService->getLpas($token);

        $this->assertInstanceOf(ArrayObject::class, $lpas);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpas);
    }

    /** @test */
    public function it_gets_a_list_of_sorted_lpas_for_a_user()
    {
        $token = '01234567-01234-01234-01234-012345678901';

        $lpaData = [
            '0123-01-01-01-012345' => [
                'lpa' => [
                    'uId' => '123456789012',
                    'donor' => [
                        'uId' => '123456789012',
                        'dob' => '1980-01-01'
                    ]
                ]
            ]
        ];

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' => new ArrayObject(), // data content doesn't matter for this test
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->httpGet('/v1/lpas')->willReturn($lpaData);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);
        $this->populateLpaMetadata->__invoke($parsedLpaData, $token)->willReturn($parsedLpaData);
        $this->sortLpas->__invoke($parsedLpaData)->willReturn($parsedLpaData);
        $this->groupLpas->__invoke($parsedLpaData)->willReturn($parsedLpaData);

        $lpas = $this->lpaService->getLpas($token, true);

        $this->assertInstanceOf(ArrayObject::class, $lpas);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpas);
    }

    /** @test */
    public function it_gets_an_lpa_by_passcode_and_surname_for_summary()
    {
        $lpaData = [
            'lpa' => [],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->httpPost(
            '/v1/viewer-codes/summary',
            [
                'code' => 'P9H8A6MLD3AM',
                'name' => 'Sanderson',
            ]
        )->willReturn($lpaData);

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    /** @test */
    public function it_gets_an_lpa_by_passcode_and_surname_for_full()
    {
        $lpaData = [
            'lpa' => [],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->httpPost(
            '/v1/viewer-codes/full',
            [
                'code' => 'P9H8A6MLD3AM',
                'name' => 'Sanderson',
                'organisation' => 'Santander'
            ]
        )->willReturn($lpaData);

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', 'Santander');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }


    /** @test */
    public function it_finds_a_cancelled_share_code_by_passcode_and_surname()
    {
        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrow(new ApiException('Share code cancelled', StatusCodeInterface::STATUS_GONE));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);
        $this->expectExceptionMessage('Share code cancelled');

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    /** @test */
    public function it_finds_an_expired_share_code_by_passcode_and_surname()
    {

        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrow(new ApiException('Share code expired', StatusCodeInterface::STATUS_GONE));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    /** @test */
    public function lpa_not_found_by_passcode_and_surname()
    {
        $this->apiClientProphecy->httpPost('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])->willThrow(new ApiException('', StatusCodeInterface::STATUS_NOT_FOUND));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    /** @test */
    public function it_gets_an_Lpa_by_Id()
    {
        $token = '01234567-01234-01234-01234-012345678901';

        $lpaData = [
            'lpa' => [
                'id' => '70000000047'
            ],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaData['lpa']['id'])
            ->willReturn($lpaData);

        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaById($token, $lpaData['lpa']['id']);

        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    /** @test */
    public function an_invalid_Lpa_id_throws_exception()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $lpaId)
            ->willThrow(new ApiException('Error whilst making http GET request', 404));

        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);

        $this->lpaService->getLpaById($token, $lpaId);
    }

    /** @test */
    public function it_finds_an_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $lpaData = [
            'uId' => $referenceNumber,
            'donor' => [
                'dob' => $dob
            ]
        ];

        $lpa = new Lpa();
        $lpa->setUId($referenceNumber);

        $donor = new CaseActor();
        $donor->setDob(new \DateTime($dob));
        $lpa->setDonor($donor);

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([
                'lpa' => $lpaData
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke(
            [
                'lpa' => $lpaData,
            ]
        )->willReturn(new ArrayObject(['lpa' => $lpa], ArrayObject::ARRAY_AS_PROPS));

        $lpa = $this->lpaService->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa['lpa']);
        $this->assertEquals(123456789012, ($lpa['lpa'])->getUId());
        $this->assertEquals($donor, ($lpa['lpa'])->getDonor());
        $this->assertEquals($dob, ($lpa['lpa'])->getDonor()->getDob()->format('Y-m-d'));
    }


    /** @test */
    public function it_finds_a_cancelled_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';
        $cancellationDate = (new \DateTime('-1 days'))->format('Y-m-d');

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $lpaData = [
            'uId' => $referenceNumber,
            'cancellationDate' => $cancellationDate,
            'donor' => [
                'dob' => $dob
            ]
        ];

        $lpa = new Lpa();
        $lpa->setUId($referenceNumber);

        $donor = new CaseActor();
        $donor->setDob(new \DateTime($dob));
        $lpa->setDonor($donor);

        $lpa->setCancellationDate(new \DateTime($cancellationDate));

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([
                'lpa' => $lpaData
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke(
            [
                'lpa' => $lpaData,
            ]
        )->willReturn(new ArrayObject(['lpa' => $lpa], ArrayObject::ARRAY_AS_PROPS));

        $lpa = $this->lpaService->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa['lpa']);
        $this->assertEquals(123456789012, ($lpa['lpa'])->getUId());
        $this->assertEquals($donor, ($lpa['lpa'])->getDonor());
        $this->assertEquals($dob, ($lpa['lpa'])->getDonor()->getDob()->format('Y-m-d'));
        $this->assertEquals($cancellationDate, ($lpa['lpa'])->getCancellationDate()->format('Y-m-d'));
    }

    /** @test */
    public function an_invalid_find_by_passcode_response_returns_null()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/summary', $params)
            ->willReturn([ 'bad-response' => 'bad']);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $lpa = $this->lpaService->getLpaByPasscode($token, $passcode, $referenceNumber, $dob);

        $this->assertNull($lpa);
    }

    /** @test */
    public function it_confirms_an_lpa_by_passcode()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/confirm', $params)
            ->willReturn([
                'user-lpa-actor-token' => 'actor-lpa-code'
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $lpaCode = $this->lpaService->confirmLpaAddition($token, $passcode, $referenceNumber, $dob);

        $this->assertEquals('actor-lpa-code', $lpaCode);
    }

    /** @test */
    public function an_invalid_confirmation_of_lpa_by_passcode_returns_null()
    {
        $token = '01234567-01234-01234-01234-012345678901';
        $passcode = '123456789012';
        $referenceNumber = '123456789012';
        $dob = '1980-01-01';

        $params = [
            'actor-code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $this->apiClientProphecy->httpPost('/v1/actor-codes/confirm', $params)
            ->willReturn([
                'bad_response' => 'bad'
            ]);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $lpaCode = $this->lpaService->confirmLpaAddition($token, $passcode, $referenceNumber, $dob);

        $this->assertNull($lpaCode);
    }

    /** @test */
    public function it_returns_null_if_an_lpa_is_not_already_added()
    {
        $token = '01234567-01234-01234-01234-012345678901';

        $lpaData = [
            '0123-01-01-01-012345' => []
        ];

        $lpa = new Lpa();
        $lpa->setUId('123456789012');

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' => new ArrayObject(['lpa' => $lpa], ArrayObject::ARRAY_AS_PROPS),
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->httpGet('/v1/lpas')->willReturn($lpaData);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpaAdded = $this->lpaService->isLpaAlreadyAdded('333333333333', $token);

        $this->assertNull($lpaAdded);
    }

    /** @test */
    public function it_returns_userLpaToken_if_an_lpa_is_already_added()
    {
        $token = '01234567-01234-01234-01234-012345678901';

        $lpa = new Lpa();
        $lpa->setUId('123456789012');

        $lpaData = [
            '0123-01-01-01-012345' => [
                'user-lpa-actor-token' => '0123-01-01-01-012345',
                'lpa' => $lpa
            ]
        ];

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' =>  new ArrayObject(
                    [
                        'user-lpa-actor-token' => '0123-01-01-01-012345',
                        'lpa' => $lpa,
                    ],
                    ArrayObject::ARRAY_AS_PROPS
                ),
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->httpGet('/v1/lpas')->willReturn($lpaData);
        $this->apiClientProphecy->setUserTokenHeader($token)->shouldBeCalled();

        $this->parseLpaData->__invoke($lpaData)->willReturn($parsedLpaData);

        $lpaAdded = $this->lpaService->isLpaAlreadyAdded('123456789012', $token);

        $this->assertArrayHasKey('user-lpa-actor-token', $lpaAdded);
        $this->assertArrayHasKey('lpa', $lpaAdded);
        $this->assertEquals('0123-01-01-01-012345', $lpaAdded['user-lpa-actor-token']);
        $this->assertEquals($lpa, $lpaAdded['lpa']);
    }
}
