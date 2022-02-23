<?php

declare(strict_types=1);

namespace Common\Middleware\Workflow;

use Common\Workflow\StatesCollection;
use Common\Workflow\WorkflowState;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Responsible for processing the request/response for WorkflowStates and persisting/loading them from Session storage
 */
class StatePersistenceMiddleware implements MiddlewareInterface
{
    /**
     * The attribute name under which workflow state is stored in the request
     */
    public const WORKFLOW_STATE_ATTRIBUTE = 'workflowStates';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // load states from session and attach to request
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        // get array of possible states from the session
        $sessionStates = $session->get(self::WORKFLOW_STATE_ATTRIBUTE, []);

        // process those states into state objects
        $validStates = new StatesCollection();
        array_walk($sessionStates, function (array $data, string $class) use (&$validStates) {
            $this->classIsWorkflow($class);
            $validStates->add($class, new $class(...$data));
        });

        // handle the request
        $response = $handler->handle(
            $request->withAttribute(self::WORKFLOW_STATE_ATTRIBUTE, $validStates)
        );

        // persist states to session
        // get array of states from the request and persist
        $session->set(self::WORKFLOW_STATE_ATTRIBUTE, $validStates);

        return $response;
    }

    /**
     * @param class-string<WorkflowState> $class
     *
     * @return void
     *
     * @throws RuntimeException Requested workflow state class is not a valid class or does not implement WorkflowState
     */
    private function classIsWorkflow(string $class): void
    {
        try {
            $reflect = new ReflectionClass($class);

            if (! $reflect->implementsInterface(WorkflowState::class)) {
                throw new RuntimeException(
                    'Requested WorkflowState class does not implement \Common\Workflow\WorkflowState'
                );
            }
        } catch (ReflectionException $rex) {
            throw new RuntimeException(
                'Requested WorkflowState class is not a valid class name',
                500,
                $rex
            );
        }
    }
}
