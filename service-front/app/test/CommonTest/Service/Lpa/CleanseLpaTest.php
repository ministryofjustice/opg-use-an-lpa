<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\AccessForAllApiResult;
use Common\Service\Lpa\CleanseLpa;
use Common\Service\Lpa\Response\AccessForAllResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CleanseLpa::class)]
class CleanseLpaTest extends TestCase
{
    private string $actorId;
    private string $additionalInfo;
    private ApiClient&MockObject $apiClientProphecy;
    private LoggerInterface&MockObject $loggerProphecy;
    private string $lpaUid;
    private CleanseLpa $sut;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->createMock(ApiClient::class);
        $this->loggerProphecy    = $this->createMock(LoggerInterface::class);

        $this->lpaUid         = '70000000013';
        $this->additionalInfo = "This is a notes field with \n information about the user \n over multiple lines";
        $this->actorId        = '1';
    }

    #[Test]
    public function submit_cleanse_request_successfully(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/older-lpa/cleanse',
                [
                    'reference_number' => (string) $this->lpaUid,
                    'notes'            => $this->additionalInfo,
                ]
            )->willReturn([]);

        $this->sut = new CleanseLpa($this->apiClientProphecy, $this->loggerProphecy);
        $response  = $this->sut->cleanse($this->lpaUid, $this->additionalInfo, null);

        self::assertEquals(new AccessForAllApiResult(AccessForAllResult::SUCCESS, []), $response);
    }

    #[Test]
    public function submit_cleanse_request_successfully_with_actorId(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/older-lpa/cleanse',
                [
                    'reference_number' => (string) $this->lpaUid,
                    'notes'            => $this->additionalInfo,
                    'actor_id'         => $this->actorId,
                ]
            )->willReturn([]);

        $this->sut = new CleanseLpa($this->apiClientProphecy, $this->loggerProphecy);
        $response  = $this->sut->cleanse($this->lpaUid, $this->additionalInfo, $this->actorId);

        self::assertEquals(new AccessForAllApiResult(AccessForAllResult::SUCCESS, []), $response);
    }

    #[Test]
    public function submit_cleanse_request_with_api_failure(): void
    {
        $this->apiClientProphecy
            ->method('httpPost')->with(
                '/v1/older-lpa/cleanse',
                [
                    'reference_number' => (string) $this->lpaUid,
                    'notes'            => $this->additionalInfo,
                ]
            )->willThrowException(new ApiException(''));

        $this->expectException(ApiException::class);

        $this->sut = new CleanseLpa($this->apiClientProphecy, $this->loggerProphecy);
        $response  = $this->sut->cleanse((string) $this->lpaUid, $this->additionalInfo, null);
    }
}
