<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
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

    public function setUp(): void
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
                'id' => '70000000047',
                'status' => 'Registered'
            ],
            'actor' => [
                'details' => [
                ]
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
}
