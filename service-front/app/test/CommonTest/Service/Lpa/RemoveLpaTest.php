<?php

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\RemoveLpa;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Class RemoveLpaTest
 *
 * @package CommonTest\Service\Lpa
 */
class RemoveLpaTest extends TestCase
{
    private CaseActor $actor;
    private string $actorLpaToken;
    /** @var ObjectProphecy|ApiClient */
    private $apiClientProphecy;
    private Lpa $lpa;
    private array $lpaArrayData;
    private ArrayObject $lpaParsedData;
    /** @var ObjectProphecy|ParseLpaData */
    private $parseLpaDataProphecy;
    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;
    private RemoveLpa $removeLpa;
    private string $userToken;

    public function setUp(): void
    {
        $this->actorLpaToken = '0123-01-01-01-012345';
        $this->userToken = '12-1-1-1-1234';

        $this->apiClientProphecy = $this->prophesize(ApiClient::class);
        $this->parseLpaDataProphecy = $this->prophesize(ParseLpaData::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->apiClientProphecy->setUserTokenHeader($this->userToken)->shouldBeCalled();

        $this->removeLpa = new RemoveLpa(
            $this->apiClientProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->parseLpaDataProphecy->reveal()
        );

        $this->actor = new CaseActor();
        $this->actor->setId(2222);
        $this->actor->setUId('700000000997');
        $this->actor->setFirstname('Firstname');
        $this->actor->setSurname('Surname');

        $this->lpa = new Lpa();
        $this->lpa->setId(1111);
        $this->lpa->setUId('700000001111');
        $this->lpa->setDonor($this->actor);
        $this->lpa->setCaseSubtype('pfa');

        $this->lpaParsedData = new ArrayObject(['lpa' => $this->lpa]);

        $this->lpaArrayData = [
            'lpa' => [
                'id' => 1111,
                'uId' => '700000001111'
            ]
        ];
    }

    /** @test */
    public function it_returns_lpa_data_when_lpa_successfully_removed()
    {
        $this->apiClientProphecy
            ->httpDelete('/v1/lpas/' . $this->actorLpaToken)
            ->willReturn($this->lpaArrayData);

        $this->parseLpaDataProphecy
            ->__invoke($this->lpaArrayData)
            ->willReturn($this->lpaParsedData);

        $result = ($this->removeLpa)($this->userToken, $this->actorLpaToken);

        $this->assertContains($this->lpa->getUId(), $result->getArrayCopy()['lpa']->getUId());
    }

    /** @test */
    public function it_will_fail_if_actor_lpa_token_not_found()
    {
        $this->apiClientProphecy
            ->httpDelete('/v1/lpas/' . $this->actorLpaToken)
        ->willThrow(
            new ApiException(
                'User actor lpa record not found for actor token - ' . $this->actorLpaToken,
                StatusCodeInterface::STATUS_NOT_FOUND
            )
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('User actor lpa record not found for actor token - ' . $this->actorLpaToken);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        ($this->removeLpa)($this->userToken, $this->actorLpaToken);
    }

    /** @test */
    public function it_will_fail_if_actor_lpa_token_does_not_match_user_id()
    {
        $this->apiClientProphecy
            ->httpDelete('/v1/lpas/' . $this->actorLpaToken)
            ->willThrow(
                new ApiException(
                    'User Id passed does not match the user in userActorLpaMap for token - ' . $this->actorLpaToken,
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage(
            'User Id passed does not match the user in userActorLpaMap for token - ' .
            $this->actorLpaToken
        );
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        ($this->removeLpa)($this->userToken, $this->actorLpaToken);
    }
}
