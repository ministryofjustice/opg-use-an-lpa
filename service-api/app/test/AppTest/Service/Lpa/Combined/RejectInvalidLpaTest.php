<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\Combined;

use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\LpaStore\LpaStore;
use App\Exception\GoneException;
use App\Exception\MissingCodeExpiryException;
use App\Exception\NotFoundException;
use App\Service\Lpa\Combined\RejectInvalidLpa;
use App\Service\Lpa\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class RejectInvalidLpaTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_rejects_code_data_with_no_stored_expiry(): void
    {
        $sut = new RejectInvalidLpa(
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(MissingCodeExpiryException::class);

        $sut(
            $this->prophesize(LpaInterface::class)->reveal(),
            'code',
            'surname',
            [
                'ViewerCode' => 'code',
            ],
        );
    }

    #[Test]
    public function it_rejects_code_data_with_a_badly_formated_expiry(): void
    {
        $sut = new RejectInvalidLpa(
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(MissingCodeExpiryException::class);

        $sut(
            $this->prophesize(LpaInterface::class)->reveal(),
            'code',
            'surname',
            [
                'ViewerCode' => 'code',
                'Expires'    => 'NOT_A_DATETIMEINTERFACE',
            ],
        );
    }

    #[Test]
    public function it_rejects_lpas_with_a_non_matching_surname(): void
    {
        /** @var Lpa<LpaStore> $lpaStoreResponse */
        $lpaStoreResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $sut = new RejectInvalidLpa(
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(NotFoundException::class);

        $sut(
            $lpaStoreResponse,
            'code',
            'surname',
            [
                'ViewerCode' => 'code',
                'Expires'    => new DateTimeImmutable('+1 hour'),
            ],
        );
    }

    #[Test]
    public function it_rejects_viewercodes_that_have_expired(): void
    {
        /** @var Lpa<LpaStore> $lpaStoreResponse */
        $lpaStoreResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $sut = new RejectInvalidLpa(
            $this->prophesize(ClockInterface::class)
                ->now()
                ->willReturn(new DateTimeImmutable('now'))
                ->getObjectProphecy()->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(GoneException::class);

        $sut(
            $lpaStoreResponse,
            'code',
            $lpaStoreResponse->getData()->getDonor()->getSurname(),
            [
                'ViewerCode' => 'code',
                'Expires'    => new DateTimeImmutable('-1 hour'),
            ],
        );
    }

    #[Test]
    public function it_rejects_viewercodes_that_have_been_cancelled(): void
    {
        /** @var Lpa<LpaStore> $lpaStoreResponse */
        $lpaStoreResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $sut = new RejectInvalidLpa(
            $this->prophesize(ClockInterface::class)
                ->now()
                ->willReturn(new DateTimeImmutable('now'))
                ->getObjectProphecy()->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(GoneException::class);

        $sut(
            $lpaStoreResponse,
            'code',
            $lpaStoreResponse->getData()->getDonor()->getSurname(),
            [
                'ViewerCode' => 'code',
                'Expires'    => new DateTimeImmutable('+1 hour'),
                'Cancelled'  => new DateTimeImmutable('-1 hour'),
            ],
        );
    }

    private function loadTestLpaStoreLpaFixture(array $overwrite = []): LpaStore
    {
        $lpaData = json_decode(file_get_contents(__DIR__ . '/../../../../fixtures/4UX3.json'), true);
        $lpaData = array_merge($lpaData, $overwrite);

        /** @var LpaStore */
        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }
}
