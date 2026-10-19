<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Context\UserContext;
use Common\Service\Lpa\GroupLpas;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\PopulateLpaMetadata;
use Common\Service\Lpa\SortLpas;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(LpaService::class)]
class LpaServiceTest extends TestCase
{
    private Client&MockObject $apiClientProphecy;
    private LoggerInterface&MockObject $loggerProphecy;
    private ParseLpaData&MockObject $parseLpaData;
    private PopulateLpaMetadata&MockObject $populateLpaMetadata;
    private SortLpas&MockObject $sortLpas;
    private GroupLpas&MockObject $groupLpas;
    private LpaService $lpaService;

    public function setUp(): void
    {
        $this->apiClientProphecy   = $this->createMock(Client::class);
        $this->parseLpaData        = $this->createMock(ParseLpaData::class);
        $this->populateLpaMetadata = $this->createMock(PopulateLpaMetadata::class);
        $this->sortLpas            = $this->createMock(SortLpas::class);
        $this->groupLpas           = $this->createMock(GroupLpas::class);
        $this->loggerProphecy      = $this->createMock(LoggerInterface::class);

        $this->lpaService = new LpaService(
            $this->apiClientProphecy,
            $this->parseLpaData,
            $this->populateLpaMetadata,
            $this->sortLpas,
            $this->groupLpas,
            $this->loggerProphecy,
            new UserContext('01234567-01234-01234-01234-012345678901', 'session-id'),
        );
    }

    #[Test]
    public function it_gets_a_list_of_lpas_for_a_user(): void
    {
        $lpaData = [
            '0123-01-01-01-012345' => [
                'lpa' => [
                    'uId'   => '123456789012',
                    'donor' => [
                        'uId' => '123456789012',
                        'dob' => '1980-01-01',
                    ],
                ],
            ],
        ];

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' => new ArrayObject(), // data content doesn't matter for this test
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->method('httpGet')->with('/v1/lpas')->willReturn($lpaData);

        $this->parseLpaData->method('__invoke')->with($lpaData)->willReturn($parsedLpaData);

        $lpas = $this->lpaService->getLpas();

        $this->assertInstanceOf(ArrayObject::class, $lpas);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpas);
    }

    #[Test]
    public function it_gets_a_list_of_sorted_lpas_for_a_user(): void
    {
        $lpaData = [
            '0123-01-01-01-012345' => [
                'lpa' => [
                    'uId'   => '123456789012',
                    'donor' => [
                        'uId' => '123456789012',
                        'dob' => '1980-01-01',
                    ],
                ],
            ],
        ];

        $parsedLpaData = new ArrayObject(
            [
                '0123-01-01-01-012345' => new ArrayObject(), // data content doesn't matter for this test
            ],
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->apiClientProphecy->method('httpGet')->with('/v1/lpas')->willReturn($lpaData);

        $this->parseLpaData->method('__invoke')->with($lpaData)->willReturn($parsedLpaData);
        $this->populateLpaMetadata->method('__invoke')
            ->with($parsedLpaData)
            ->willReturn($parsedLpaData);
        $this->sortLpas->method('__invoke')->with($parsedLpaData)->willReturn($parsedLpaData);
        $this->groupLpas->method('__invoke')->with($parsedLpaData)->willReturn($parsedLpaData);

        $lpas = $this->lpaService->getLpas(true);

        $this->assertInstanceOf(ArrayObject::class, $lpas);
        $this->assertArrayHasKey('0123-01-01-01-012345', $lpas);
    }

    #[Test]
    public function it_gets_an_lpa_by_passcode_and_surname_for_summary(): void
    {
        $lpaData = [
            'lpa' => [],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->method('httpPost')->with(
            '/v1/viewer-codes/summary',
            [
                'code' => 'P9H8A6MLD3AM',
                'name' => 'Sanderson',
            ]
        )->willReturn($lpaData);

        $this->parseLpaData->method('__invoke')->with($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    #[Test]
    public function it_gets_an_lpa_by_passcode_and_surname_for_full(): void
    {
        $lpaData = [
            'lpa' => [],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->method('httpPost')->with(
            '/v1/viewer-codes/full',
            [
                'code'         => 'P9H8A6MLD3AM',
                'name'         => 'Sanderson',
                'organisation' => 'Santander',
            ]
        )->willReturn($lpaData);

        $this->parseLpaData->method('__invoke')->with($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', 'Santander');

        $this->assertInstanceOf(ArrayObject::class, $lpa);
        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    #[Test]
    public function it_finds_a_cancelled_share_code_by_passcode_and_surname(): void
    {
        $this->apiClientProphecy->method('httpPost')->with('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrowException(new ApiException('Share code cancelled', StatusCodeInterface::STATUS_GONE));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);
        $this->expectExceptionMessage('Share code cancelled');

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    #[Test]
    public function it_finds_an_expired_share_code_by_passcode_and_surname(): void
    {
        $this->apiClientProphecy->method('httpPost')->with('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])
            ->willThrowException(new ApiException('Share code expired', StatusCodeInterface::STATUS_GONE));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_GONE);

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    #[Test]
    public function lpa_not_found_by_passcode_and_surname(): void
    {
        $this->apiClientProphecy->method('httpPost')->with('/v1/viewer-codes/summary', [
            'code' => 'P9H8A6MLD3AM',
            'name' => 'Sanderson',
        ])->willThrowException(new ApiException('', StatusCodeInterface::STATUS_NOT_FOUND));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        $this->lpaService->getLpaByCode('P9H8-A6ML-D3AM', 'Sanderson', null);
    }

    #[Test]
    public function it_gets_an_Lpa_by_Id(): void
    {
        $lpaData = [
            'lpa'   => [
                'id'     => '70000000047',
                'status' => 'Registered',
            ],
            'actor' => [
                'details' => [],
            ],
        ];

        $parsedLpaData = new ArrayObject(['lpa' => new Lpa()], ArrayObject::ARRAY_AS_PROPS);

        $this->apiClientProphecy->method('httpGet')->with('/v1/lpas/' . $lpaData['lpa']['id'])
            ->willReturn($lpaData);

        $this->parseLpaData->method('__invoke')->with($lpaData)->willReturn($parsedLpaData);

        $lpa = $this->lpaService->getLpaById($lpaData['lpa']['id']);

        $this->assertInstanceOf(Lpa::class, $lpa->lpa);
    }

    #[Test]
    public function an_invalid_Lpa_id_throws_exception(): void
    {
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $this->apiClientProphecy->method('httpGet')->with('/v1/lpas/' . $lpaId)
            ->willThrowException(new ApiException('Error whilst making http GET request', 404));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);

        $this->lpaService->getLpaById($lpaId);
    }
}
