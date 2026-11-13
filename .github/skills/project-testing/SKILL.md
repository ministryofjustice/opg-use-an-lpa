---
name: project-testing
description: Use when choosing, adding, running, or diagnosing tests in opg-use-an-lpa. Covers PHPUnit unit and
  functional tests, Behat integration/acceptance/UI and deployed smoke tests, Jest browser JavaScript tests, Go tests,
  Python unittest/pytest tests, Pact contracts, test locations, tags, and project-specific commands.
---

# Project testing

Use the cheapest test layer that proves the behaviour. Add tests beside the service that owns the behaviour and follow
nearby tests for fixtures, doubles, assertions, naming, and setup.

Before changing or running tests, inspect the relevant current configuration:

- API: `service-api/app/composer.json`, `phpunit.xml.dist`, and `behat.yml`
- Front PHP: `service-front/app/composer.json`, `phpunit.xml.dist`, and `behat.yml`
- Front JavaScript: `service-front/web/package.json` and `jest.config.cjs`
- Smoke: `tests/smoke/composer.json`, `behat.yml`, and `README.md`
- CI behaviour: `.github/workflows/_build-and-push.yml`, `_node-test.yml`, `_run-behat-tests.yml`, and `go-unit-test.yml`

Unit test coverage **MUST** not drop when code is altered or added. Tests should be created or modified to ensure
coverage is always improved.

## Keep tests focused on application behaviour

- Start with one representative test matching the application's actual usage, inputs, and expected outcome.
  Do not build a compatibility matrix for dependency capabilities that the application does not use.
- Use data providers only when each variation exercises a distinct responsibility or branch in our code.
  Do not multiply cases for alternative invocation styles, configuration options, or input combinations merely
  because a dependency supports them. Add cases when application behaviour or a specific regression requires them.
- Assert the behaviour our code owns: its decisions, transformations, delegation, outputs, and relevant side effects.
  Avoid assertions about unrelated dependency results or behaviour delegated entirely to a library.
- Use deterministic inputs and the smallest realistic fixture. Do not add extra records, optional fields, or
  hypothetical states without a concrete requirement. Include invalid inputs and edge cases where handling them
  is our code's responsibility.
- Real library objects are appropriate when necessary to exercise our integration with that library. Keep that
  setup minimal and replace external I/O, rather than exercising multiple real application services.
- Keep each assertion with the class that owns the behaviour. Test callers' delegation separately from collaborators'
  implementation; do not introduce a cross-class functional test when those focused tests prove the change.

## Choose the test layer

1. **PHPUnit unit test**: default for isolated PHP classes, middleware, forms, factories, and services. Mock
   only collaborators at the boundary.
2. **API PHPUnit functional/Pact test**: use for API clients and HTTP contracts that need the application container or
   Pact mock server.
3. **Behat integration test**: use for service behaviour across real internal components without browser-level
   navigation.
4. **Behat acceptance/UI test**: use for HTTP request/response journeys through a service. Front-end scenarios use
   `@ui`; API scenarios use `@acceptance`.
5. **Jest test**: use for JavaScript modules and DOM behaviour under jsdom.
6. **Go or Python unit test**: use inside the component written in that language.
7. **Smoke test**: use only for a minimal critical journey or deployed-environment concern that cheaper layers cannot
   prove, such as service connectivity, redirects, HTTPS/security headers, browser cookie handling, or public routing.

Do not move ordinary functional coverage into smoke tests. They require a running, fresh environment and Chrome, and are
intentionally expensive.

## Mandatory test boundaries

**Smoke tests are explicitly excluded from the isolation rules below.** They are
expected to cross service and infrastructure boundaries, exercising the deployed
application and its real dependencies. Follow the existing smoke-test environment
and setup requirements; do not mock away the connections they are intended to prove.

For all other test layers:

- Unit tests isolate the class under test from collaborating application services.
  Integration and acceptance tests may exercise real internal components, but must
  replace external dependencies at the owning service's boundary.
- External dependencies include Redis, databases, AWS services, identity providers,
  and other services' HTTP APIs. Use mocks, stubs, existing contract-test tooling,
  or in-memory fakes in test-only configuration. An acceptance or integration
  profile is not permission to connect to real external services.
- Keep the application behaviour being tested real. Replace the external adapter,
  not the application service whose behaviour the scenario asserts. For example,
  exercise SessionLpaCache with an in-memory tag-aware cache rather than Redis;
  preserve the tag invalidation semantics needed by logout scenarios.
- Before fixing a CI-only failure, inspect the suite's bootstrap, dependency
  overrides, and test doubles. A missing external connection usually means the
  test boundary is incomplete, not that CI needs another service.
- Do not add dependency containers, networks, emulators, deployed environments,
  or credentials to make these tests pass. Do not change production behaviour
  to accommodate test isolation. Any exception requires explicit user approval
  before implementation.
- Validate isolation with the real dependency unavailable or network access
  disabled. Passing against a running local stack does not prove isolation.

## PHPUnit conventions

- Tests should use snake_case naming in a specification-like format, using standard specification words such as
  `it_should_*`, `it_must_*`, or `it_may_*`. These are examples, not an exhaustive list.
- Existing `it_*` test method names are acceptable. Do not rename them solely to enforce a particular specification
  word when touching a test file.
- Existing tests make use of Prophecy for mocking. New tests and tests being touched should migrate to PHPUnits built
  in mocking capabilities.
- Test classes should;
  - make full use of `CoversClass` annotations and should cover single classes as much as possible.
  - be tightly scoped to the functioning of the covered class. Do not cover the functionality of other classes unless
    necessary for full coverage of the covered class.
  - not test functionality of classes that we don't own (libraries etc) unless necessary for full coverage of the
    covered class.
- Some types of classes are generally not unit tested. Before creating tests for a class check that similar types
  have tests and are not marked as excluded from coverage. For example Handlers and ConfigProviders are not unit tested.
- Unit tests must replace collaborating application services with mocks or stubs. Test delegation, arguments, callbacks
  and returned results—not the collaborator’s implementation. For example, LPA manager tests must double the session
  cache; cache behaviour belongs in SessionLpaCacheTest. Tests exercising multiple real services together belong in
  integration coverage.

## Behat conventions

- `~@wip` is configured globally, so `@wip` scenarios do not run.
- Reuse existing steps before adding a new step definition.
- Put a scenario at the lowest profile that proves the behaviour.
- Preserve service/profile tags because suites filter on them.
- Feature flags use tags such as `@ff:flag_name:true`, `@ff:flag_name:false`, or `@ff:flag_name:from_env`.
- When using `from_env`, the smoke environment variable is `BEHAT_FF_<UPPERCASE_FLAG_NAME>`.
- Pass Behat arguments after Composer's `--`.

## PHP: front service

- Production code: `service-front/app/src`
- PHPUnit tests: `service-front/app/test/{ActorTest,CommonTest,ViewerTest}`
- Behat features: `service-front/app/features`
- Behat contexts: `service-front/app/features/context`
- PHPUnit version: 12
- PHPUnit suite: `unit`
- Behat profiles: `integration` and `acceptance`
- The `acceptance` profile selects front scenarios with `@actor&&@ui` or `@viewer&&@ui`.

Run from the service folder root `service-front/app`:

```shell
composer run unit-test
```

Focused examples:

```shell
cd service-front/app && composer run unit-test -- --filter SomeTest
cd service-front/app && composer run int-test -- --name="scenario text"
cd service-front/app && composer run ui-test -- --tags="@featureTag"
```

## PHP: API service

- Production code: `service-api/app/src`
- Unit tests: `service-api/app/test/AppTest`
- Functional/Pact tests: `service-api/app/test/FunctionalTest`
- Behat features: `service-api/app/features`
- Behat contexts: `service-api/app/features/context`
- PHPUnit version: 10
- PHPUnit suites: `unit` and `functional`
- Behat profiles: `integration` and `acceptance`
- API feature scenarios use `@integration` and/or `@acceptance`.

Run from the service folder root `service-api/app`:

```shell
composer run unit-test -- --testsuit unit
```

Running API PHPUnit without a suite runs both `unit` and `functional`; CI does this to generate Pact files under
`service-api/app/build/pacts`.

Focused examples:

```shell
cd service-api/app && composer run unit-test --testsuite unit --filter SomeTest
cd service-api/app && composer run unit-test --testsuite functional --filter ActorCodesTest
cd service-api/app && composer run int-test -- --name="scenario text"
cd service-api/app && composer run acc-test -- --tags="@featureTag"
```

## Deployed smoke tests

- Features: `tests/features`
- Runner and contexts: `tests/smoke`
- Framework: Behat, Mink, Chrome
- Suites: `actor` (`@actor&&@smoke`) and `viewer` (`@viewer&&@smoke`)

Run from smoke test folder `tests/smoke`:

```shell
composer run behat
```

Or, for focused execution:

```shell
cd tests/smoke && composer run behat -- --tags="@actor&&@smoke"
cd tests/smoke && composer run behat -- --name="scenario text"
```

The application stack must already be running. Follow `tests/smoke/README.md`: One Login caching means the suite needs a
fresh, unused environment, and the environment should be reset again before normal local use.

## JavaScript

- Code and tests: `service-front/web/src`
- File convention: `*.test.js`
- Framework: Jest 30 with jsdom, Testing Library, and jest-dom
- Coverage: `src/**/*.js`
- JUnit output: `service-front/web/test-results/junit/results.xml`

Run:

```shell
make unit_test_javascript
```

Focused examples:

```shell
docker compose run --rm npm npm run jest -- src/javascript/cookieConsent.test.js --runInBand
docker compose run --rm npm npm run jest -- --testNamePattern="test name" --runInBand
```

Use DOM queries and observable user behaviour rather than implementation details. The Jest configuration resets mocks
between tests.

## Go

Primary suites:

- `service-admin/**/*_test.go`
- `lambda-functions/event-receiver/**/*_test.go`

Run in the owning Go module:

```shell
cd service-admin && go test ./...
cd lambda-functions/event-receiver && go test ./...
```

CI collects admin coverage with:

```shell
go test -coverpkg ./... -coverprofile=coverage.out -covermode=atomic ./...
```

Follow nearby use of the standard `testing` package and `testify`. Preserve `t.Parallel()` where the test and its state
are safe for parallel execution.

## Python

The upload-statistics Lambda uses `unittest` and Moto:

```shell
cd lambda-functions/upload-statistics
uv sync --locked --group test
cd app && uv run python -m unittest -v
```

`scripts/duplicate-accounts/test_merge_duplicate_identities.py` uses pytest-style tests; run it from that directory
with the repository's available Python/pytest environment.

## Validation strategy

- During development, run the narrowest affected file, class, test name, package, or Behat tag.
- Before finishing, run the owning suite for the changed component. For example, code changed in service-api would
  require acceptance, integration, function and unit tests to pass.
- Run broader cross-service or smoke coverage only when the change crosses those boundaries.
  If a test command differs from CI, compare with the relevant workflow and reproduce CI flags when diagnosing CI-only
failures.
- Do not update snapshots, fixtures, Pact contracts, or expected output merely to make failures pass; confirm the
  behaviour change is intended.
