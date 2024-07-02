Behat Smoke Test Suite
======

The behat driven smoke test suite ensures that each of our application components are able to connect to their necessary dependencies i.e. api's and other services and that the application as deployed is functioning.

> ### Note
> The tests are designed to be minimal in scope and action. They are expensive to run (in terms of time) and therefore testing of the functionality of the applications MUST always be done at less expensive levels (acceptance, integration, unit)

Additionally, they test things that are not a part of the code base such as web server based https and redirections and cookies settings as interpreted by the browser.

## Running the tests

The smoke tests are designed to be run against a running environment, either the one you've spun up locally or, when running in CI, the one in the ephemeral environment. This brings some challenges in that the test environment must be setup locally and in CI to be the same, or as close to it as possible.

```shell
# ensure your use-an-lpa environment is running already
# use PHPStorm or a method devised in the top level README

# run tests when wanted
$ composer behat

# alternatively, using the top level Makefile (prompt to be at project root)
$ make smoke_tests

# create feature step definitions (output to CLI)
$ composer behat -- --snippets-for

# create feature step definitions (append to specified context class)
# please note that the class namespaces must be escaped with double '\\'
$ composer behat -- --snippets-for Test\\Context\\AccountContext --append-snippets
```

## Feature Flags

It's possible to implement feature flagging in the smoke tests by supplying environment variables and appropriately tagging the scenario's you need to flag (just like integration/acceptance tests). First you'll want to tag your smoke test scenario with a flag to use

```gherkin
@ff:my_tag_name:from_env
Scenario: My Scenario description
```

It's also possible, though maybe slightly less useful in the context of ephemeral environments, to provide the flag values statically.

```gherkin
@ff:my_tag_name:true
# or
@ff:my_tag_name:false
```

Setting the flag value is a case of editing the `.github/workflows/_run-behat-tests.yml` file to ensure the value is available. _There will probably be a whole other list of things you'll need to do in Terraform to expose the flag setting to the CI system_. When running the suite locally you'll be adding your value to the `tests/smoke/.env` file. 

> ### Environment variable naming
> Your tag name needs some transformation to become an environment variable the system will use.
> - Uppercase it
> - Prefix it with `BEHAT_FF_`

## The post One Login world

> ### TLDR
> The smoke test suite needs a fresh, unused (and so uncached) environment to function. Ensure you bring the whole stack down first before bringing it up to run the test suite. You MUST NOT use the environment before running the suite.
> 
> Additionally, after you're done with testing you'll need to repeat this process to make the environment good for local usage again.

One Login is an OIDC login provider at it's core and is a service provided by someone else, so we have little control over its workings. Because of this we use a mock service to drive local development and the test suites. 

The tests that we need to run require that we are signed in and so there are some special things that need setting up when running the tests locally and in the CI environments that mean subtly breaking the local environment. Namely, the Mock service that we run needs to be configured differently for local dev then it does for running the tests and because the app caches things that the mock provides it all falls over if you try to switch between the two.