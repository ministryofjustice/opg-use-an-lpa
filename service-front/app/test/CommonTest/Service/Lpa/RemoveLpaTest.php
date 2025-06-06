<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\RemoveLpa;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(RemoveLpa::class)]
class RemoveLpaTest extends TestCase
{
    use ProphecyTrait;

    private CaseActor $actor;
    private string $actorLpaToken;
    private ObjectProphecy|ApiClient $apiClientProphecy;
    private Lpa $lpa;
    private array $lpaArrayData;
    private ArrayObject $lpaParsedData;
    private ObjectProphecy|LoggerInterface $loggerProphecy;
    private RemoveLpa $removeLpa;
    private string $userToken;

    public function setUp(): void
    {
        $this->actorLpaToken = '0123-01-01-01-012345';
        $this->userToken     = '12-1-1-1-1234';

        $this->apiClientProphecy    = $this->prophesize(ApiClient::class);
        $this->loggerProphecy       = $this->prophesize(LoggerInterface::class);

        $this->apiClientProphecy->setUserTokenHeader($this->userToken)->shouldBeCalled();

        $this->removeLpa = new RemoveLpa(
            $this->apiClientProphecy->reveal(),
            $this->loggerProphecy->reveal(),
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

        $this->lpaArrayData = [
            'lpa' => [
                'donor'  => [
                    'uId'   =>  '700000000997',
                    'firstname' => 'Firstname',
                    'middlename' => 'Middlename',
                    'surname' => 'Surname',
                ],
                'caseSubtype' => 'hw',
            ],
        ];
    }

    #[Test]
    public function it_returns_lpa_data_when_lpa_successfully_removed(): void
    {
        $this->apiClientProphecy
            ->httpDelete('/v1/lpas/' . $this->actorLpaToken)
            ->willReturn($this->lpaArrayData);

        $result = ($this->removeLpa)($this->userToken, $this->actorLpaToken);

        $this>self::assertEquals($result, new ArrayObject($this->lpaArrayData));
    }

    #[Test]
    public function it_will_fail_if_actor_lpa_token_not_found(): void
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

    #[Test]
    public function it_will_fail_if_actor_lpa_token_does_not_match_user_id(): void
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
