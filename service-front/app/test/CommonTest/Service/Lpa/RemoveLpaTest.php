<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\RemoveLpa;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(RemoveLpa::class)]
class RemoveLpaTest extends TestCase
{
    private CaseActor $actor;
    private string $actorLpaToken;
    private ApiClient&MockObject $apiClientProphecy;
    private Lpa $lpa;

    /** @var array<string, mixed> */
    private array $lpaArrayData;
    private ArrayObject $lpaParsedData;
    private LoggerInterface&MockObject $loggerProphecy;
    private RemoveLpa $removeLpa;

    public function setUp(): void
    {
        $this->actorLpaToken = '0123-01-01-01-012345';

        $this->apiClientProphecy = $this->createMock(ApiClient::class);
        $this->loggerProphecy    = $this->createMock(LoggerInterface::class);

        $this->removeLpa = new RemoveLpa(
            $this->apiClientProphecy,
            $this->loggerProphecy,
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
                'donor'       => [
                    'uId'        =>  '700000000997',
                    'firstname'  => 'Firstname',
                    'middlename' => 'Middlename',
                    'surname'    => 'Surname',
                ],
                'caseSubtype' => 'hw',
            ],
        ];
    }

    #[Test]
    public function it_returns_lpa_data_when_lpa_successfully_removed(): void
    {
        $this->apiClientProphecy
            ->method('httpDelete')->with('/v1/lpas/' . $this->actorLpaToken)
            ->willReturn($this->lpaArrayData);

        $result = ($this->removeLpa)($this->actorLpaToken);

        $this > self::assertEquals($result, new ArrayObject($this->lpaArrayData));
    }

    #[Test]
    public function it_will_fail_if_actor_lpa_token_not_found(): void
    {
        $this->apiClientProphecy
            ->method('httpDelete')->with('/v1/lpas/' . $this->actorLpaToken)
            ->willThrowException(
                new ApiException(
                    'User actor lpa record not found for actor token - ' . $this->actorLpaToken,
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('User actor lpa record not found for actor token - ' . $this->actorLpaToken);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);

        ($this->removeLpa)($this->actorLpaToken);
    }

    #[Test]
    public function it_will_fail_if_actor_lpa_token_does_not_match_user_id(): void
    {
        $this->apiClientProphecy
            ->method('httpDelete')->with('/v1/lpas/' . $this->actorLpaToken)
            ->willThrowException(
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

        ($this->removeLpa)($this->actorLpaToken);
    }
}
