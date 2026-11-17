---
name: project-structure
description: Use when creating, changing, or reviewing the structure of code in opg-use-an-lpa. Currently covers
  service-front multi-page workflows built from WorkflowState objects and WorkflowStep handlers, including where
  navigation logic lives and how nextPage, lastPage, and isMissingPrerequisite should be written.
---

# Project structure

## Workflows (service-front)

A workflow is a multi-page journey whose answers are held in a single state object that persists between requests.

Core pieces, in `service-front/app/src/Common/src/Workflow/`:

- `WorkflowState`: the interface for the serialisable state object. Implementations use the
  `Common\Workflow\Traits\JsonSerializable` trait and implement `reset()`.
- `WorkflowStep`: the interface that every handler (page) in the journey implements: `state()`,
  `isMissingPrerequisite()`, `nextPage()` and `lastPage()`.
- `State` trait: provides `loadState($request, StateClass::class)` for implementing `state()`.
- `StatePersistenceMiddleware` (`Common\Middleware\Workflow`): loads and saves states on each request.

Existing workflows to follow:

- Viewer paper verification: state `Viewer\Workflow\PaperVerificationCode`, base
  `Viewer\Handler\AbstractPaperVerificationCodeHandler`, steps in `Viewer/src/Handler/PaperVerification/`.
- Actor request activation key: state `Actor\Workflow\RequestActivationKey`, steps in
  `Actor/src/Handler/RequestActivationKey/`.
- Actor add LPA: state `Actor\Workflow\AddLpa`, steps in `Actor/src/Handler/AddLpa/`.

### Creating a workflow

1. Add a state class in `<Module>/src/Workflow/` implementing `WorkflowState`. Give it nullable public properties for
   each answer, a constructor that can rehydrate from serialised data, and a `reset()`.
2. Optionally add an abstract base handler for the workflow. It implements `WorkflowStep`, provides `state()` via the
   `State` trait, checks `isMissingPrerequisite()` in `handle()`, and dispatches to `handleGet`/`handlePost`.
3. Add one handler per page. Each implements `WorkflowStep` with `@template-implements WorkflowStep<StateClass>`.
4. Register routes in `service-front/app/config/routes.php`. Add unit tests for the state class beside the existing
   ones in `test/<Module>Test/Workflow/`.

### WorkflowStep rules

Keep each WorkflowStep self-contained: a developer should understand where a page leads by reading that handler
alone.

- `nextPage()` and `lastPage()` return route names. Write them as plain logical statements over the workflow state,
  such as ternaries, `if` returns, or a `match (true)`.

  ```php
  public function nextPage(WorkflowState $state): string
  {
      return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.postcode';
  }

  public function lastPage(WorkflowState $state): string
  {
      return $state->changingAnswer === true ? 'lpa.check-answers' : 'lpa.your-name';
  }
  ```

- Base decisions on workflow state. These methods may also use the step's own injected properties (for example,
  `$this->featureEnabled`) when the journey genuinely depends on them. Do not make requests, call services, read the
  session or request, or change state inside these methods. If another input seems necessary, ask the user before
  adding it.
- Keep helper usage minimal. Write the condition inline unless it becomes hard to read. When a helper is needed, put
  it in the WorkflowStep class itself. If several steps share the same decision, put it in the workflow's abstract
  base handler instead (for example, `AbstractPaperVerificationCodeHandler::shouldCheckAnswers()`). Do not put
  navigation helpers in services, traits, or other modules.
- Name each helper after the question it answers about state (`shouldCheckAnswers`, `hasCompletedDetails`). Helpers
  take the state object, not the request.
- `handlePost()` updates state from the validated form and then redirects with
  `$this->redirectToRoute($this->nextPage($this->state($request)))`. Templates get the back link from
  `$this->lastPage($this->state($request))`.
- `isMissingPrerequisite()` returns true when state needed to reach this page is missing, so the handler can redirect
  to the start of the workflow.  Write them as plain logical statements over the workflow state,
  such as ternaries, `if` returns, or a `match (true)`.
