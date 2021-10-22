<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\CleanseLpa;
use Common\Service\Lpa\OlderLpaApiResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CleanseLpaTest extends TestCase
{
    private string $actorId;
    private string $additionalInfo;
    private $apiClientProphecy;
    private $loggerProphecy;
    private int $lpaUid;
    private CleanseLpa $sut;
    private string $userToken;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->prophesize(ApiClient::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->userToken = '00000000-0000-4000-A000-000000000000';
        $this->lpaUid = 70000000013;
        $this->additionalInfo = "This is a notes field with \n information about the user \n over multiple lines";
        $this->actorId = '1';

        $this->apiClientProphecy->setUserTokenHeader($this->userToken)->shouldBeCalled();
    }

    /**
     * @test
     */
    public function submit_cleanse_request_successfully(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/cleanse',
                [
                    'reference_number'  => (string) $this->lpaUid,
                    'notes'             => $this->additionalInfo
                ]
            )->willReturn([]);

        $this->sut = new CleanseLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $response = $this->sut->cleanse($this->userToken, $this->lpaUid, $this->additionalInfo, null);

        self::assertEquals(new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, []), $response);
    }

    /**
     * @test
     */
    public function submit_cleanse_request_successfully_with_actorId(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/cleanse',
                [
                    'reference_number'  => (string) $this->lpaUid,
                    'notes'             => $this->additionalInfo,
                    'actor_id'          => $this->actorId
                ]
            )->willReturn([]);

        $this->sut = new CleanseLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $response = $this->sut->cleanse($this->userToken, $this->lpaUid, $this->additionalInfo, $this->actorId);

        self::assertEquals(new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, []), $response);
    }

    /**
     * @test
     */
    public function submit_cleanse_request_with_api_failure(): void
    {
        $this->apiClientProphecy
            ->httpPost(
                '/v1/add-lpa/cleanse',
                [
                    'reference_number'  => (string) $this->lpaUid,
                    'notes'             => $this->additionalInfo
                ]
            )->willThrow(new ApiException(''));

        $this->expectException(ApiException::class);

        $this->sut = new CleanseLpa($this->apiClientProphecy->reveal(), $this->loggerProphecy->reveal());
        $response = $this->sut->cleanse($this->userToken, $this->lpaUid, $this->additionalInfo, null);
    }
}
