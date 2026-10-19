<?php

declare(strict_types=1);

namespace CommonTest\Workflow;

use Actor\Workflow\AddLpa;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\StatesCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatesCollection::class)]
#[UsesClass(AddLpa::class)]
#[UsesClass(RequestActivationKey::class)]
final class StatesCollectionTest extends TestCase
{
    #[Test]
    public function it_should_return_the_requested_state_from_a_heterogeneous_collection(): void
    {
        $addLpa     = new AddLpa();
        $requestKey = new RequestActivationKey();
        $states     = new StatesCollection([AddLpa::class => $addLpa]);

        $states->add(RequestActivationKey::class, $requestKey);

        self::assertTrue($states->has(AddLpa::class));
        self::assertTrue($states->has(RequestActivationKey::class));
        self::assertSame($addLpa, $states->get(AddLpa::class));
        self::assertSame($requestKey, $states->get(RequestActivationKey::class));
        self::assertSame(
            [
                AddLpa::class               => $addLpa,
                RequestActivationKey::class => $requestKey,
            ],
            $states->jsonSerialize()
        );
    }

    #[Test]
    public function it_should_reject_a_missing_state(): void
    {
        $states = new StatesCollection();

        self::assertFalse($states->has(AddLpa::class));
        $this->expectException(StateNotInitialisedException::class);
        $this->expectExceptionMessage('State not available in state collection');

        $states->get(AddLpa::class);
    }

    #[Test]
    public function it_should_reject_a_mismatched_state_supplied_to_the_constructor(): void
    {
        $states = new StatesCollection([AddLpa::class => new RequestActivationKey()]);

        $this->expectException(StateNotInitialisedException::class);
        $this->expectExceptionMessage('Stored workflow state does not match the requested class');

        $states->get(AddLpa::class);
    }

    #[Test]
    public function it_should_reject_a_mismatched_state_added_to_the_collection(): void
    {
        $states = new StatesCollection();
        $states->add(AddLpa::class, new RequestActivationKey());

        $this->expectException(StateNotInitialisedException::class);
        $this->expectExceptionMessage('Stored workflow state does not match the requested class');

        $states->get(AddLpa::class);
    }
}
