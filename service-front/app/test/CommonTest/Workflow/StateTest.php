<?php

declare(strict_types=1);

namespace CommonTest\Workflow;

use Actor\Workflow\AddLpa;
use Actor\Workflow\RequestActivationKey;
use Common\Middleware\Workflow\StatePersistenceMiddleware;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\StatesCollection;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversTrait(State::class)]
#[UsesClass(AddLpa::class)]
#[UsesClass(RequestActivationKey::class)]
#[UsesClass(StatesCollection::class)]
final class StateTest extends TestCase
{
    use State;

    #[Test]
    public function it_should_create_and_reuse_different_states_with_the_same_loader(): void
    {
        $states  = new StatesCollection();
        $request = (new ServerRequest())->withAttribute(
            StatePersistenceMiddleware::WORKFLOW_STATE_ATTRIBUTE,
            $states
        );

        $addLpa                 = $this->loadState($request, AddLpa::class);
        $addLpa->activationKey  = 'activation-key';
        $requestKey             = $this->loadState($request, RequestActivationKey::class);
        $requestKey->firstNames = 'Example';

        self::assertInstanceOf(AddLpa::class, $addLpa);
        self::assertInstanceOf(RequestActivationKey::class, $requestKey);
        self::assertSame($addLpa, $states->get(AddLpa::class));
        self::assertSame($requestKey, $states->get(RequestActivationKey::class));
        self::assertSame($addLpa, $this->loadState($request, AddLpa::class));
        self::assertSame($requestKey, $this->loadState($request, RequestActivationKey::class));
        self::assertSame('activation-key', $this->loadState($request, AddLpa::class)->activationKey);
        self::assertSame('Example', $this->loadState($request, RequestActivationKey::class)->firstNames);
    }

    #[Test]
    public function it_should_return_a_previously_stored_state_without_resetting_it(): void
    {
        $state   = new AddLpa(activationKey: 'existing-key');
        $request = (new ServerRequest())->withAttribute(
            StatePersistenceMiddleware::WORKFLOW_STATE_ATTRIBUTE,
            new StatesCollection([AddLpa::class => $state])
        );

        self::assertSame($state, $this->loadState($request, AddLpa::class));
        self::assertSame('existing-key', $state->activationKey);
    }

    #[Test]
    public function it_should_propagate_a_mismatched_cached_state_error(): void
    {
        $request = (new ServerRequest())->withAttribute(
            StatePersistenceMiddleware::WORKFLOW_STATE_ATTRIBUTE,
            new StatesCollection([AddLpa::class => new RequestActivationKey()])
        );

        $this->expectException(StateNotInitialisedException::class);
        $this->expectExceptionMessage('Stored workflow state does not match the requested class');

        $this->loadState($request, AddLpa::class);
    }
}
