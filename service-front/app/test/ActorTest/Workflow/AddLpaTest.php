<?php

declare(strict_types=1);

namespace ActorTest\Workflow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Actor\Workflow\AddLpa;
use DateTimeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddLpa::class)]
class AddLpaTest extends TestCase
{
    #[Test]
    public function it_can_be_created_empty(): void
    {
        $sut = new AddLpa();

        Assert::assertInstanceOf(AddLpa::class, $sut);
    }

    #[Test]
    public function it_can_be_created_with_data(): void
    {
        $sut = new AddLpa(
            'activationKey',
            '1955-11-05T00:00:00+00:00', // States serialises to ATOM format
            '700000000054',
        );

        Assert::assertInstanceOf(AddLpa::class, $sut);
        Assert::assertEquals('activationKey', $sut->activationKey);
        Assert::assertInstanceOf(DateTimeInterface::class, $sut->dateOfBirth);
        Assert::assertEquals('1955-11-05T00:00:00+00:00', $sut->dateOfBirth->format('c'));
        Assert::assertEquals('700000000054', $sut->lpaReferenceNumber);
    }

    #[Test]
    public function it_can_be_reset(): void
    {
        $sut = new AddLpa(
            'activationKey',
            '1955-11-05T00:00:00+00:00', // ATOM format date
            '700000000054',
        );

        $sut->reset();

        Assert::assertNull($sut->activationKey);
        Assert::assertNull($sut->dateOfBirth);
        Assert::assertNull($sut->lpaReferenceNumber);
    }
}
