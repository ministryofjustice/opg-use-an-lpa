---
name: project-standards
description: Use when writing, reviewing, formatting, or checking code standards in opg-use-an-lpa.
  Covers PHP_CodeSniffer, PHPCBF, Psalm, final classes and public API annotations, Terraform formatting,
  TFLint, secret detection, and the commands and standards enforced by pre-commit hooks and CI.
---

# Project standards

Fix the code rather than weakening a rule, adding a blanket suppression, or expanding a baseline to make a check pass.
Use the configuration belonging to the component being changed; similar configurations are not necessarily identical.
For behaviour tests, use the separate `project-testing` skill.

## Sources of truth

| Concern | Configuration |
| --- | --- |
| Commit hooks and their order | `.pre-commit-config.yaml` |
| PHP style | `service-front/app/phpcs.xml.dist`, `service-api/app/phpcs.xml.dist`, `tests/smoke/phpcs.xml.dist` |
| PHP static analysis | Each PHP component's `psalm.xml` and `psalm-baseline.xml` |
| PHP command wrappers | Front/API `composer.json` and `pre-commit-hooks/{phpcs,phpcbf}.sh` |
| PHP CI checks | `.github/workflows/php-linting.yml`, `.github/workflows/psalm-static-analysis.yml` |
| Terraform CI checks | `.github/workflows/_lint-terraform.yml` |
| Accepted secret-detection findings | `.secrets.baseline` |

## Project Documentation

- Unless a readme contains content that explictitly refers to the functionality of a feature being worked on, or if
  explicitly directed to, changes should not me be made to readme style documentation. Keep things up to date but
  not at the expense of an excess of unneccessary documentation.

## PHP_CodeSniffer and PHPCBF

PHP_CodeSniffer (`phpcs`) checks style; PHP Code Beautifier and Fixer (`phpcbf`) fixes the supported violations.
All three PHP components use PSR-12 plus Generic, Squiz, and Slevomat rules. Read the exact ruleset for exclusions
and rule properties.

### What the PHP standards mean

- **Layout and naming:** follow PSR-12, including four-space indentation, explicit member visibility, and consistent
  namespace, import, brace, and operator formatting. Production methods use camelCase; `*/test/*` is excluded from
  the camelCase method rule so specification-style snake_case test names are permitted.
- **Strict typing:** start PHP files with `<?php`, one blank line, then `declare(strict_types=1);`.
  Declare property types. Use nullable types explicitly when a parameter defaults to `null`. Use `?Type` where
  applicable; otherwise put `null` last in a union. Union/intersection operators have no surrounding spaces.
- **Modern PHP:** use `[]`, not `array()`, and `::class`, not string-based class-name references where the rule applies.
  Use constructor property promotion where applicable. Use `??`, `??=`, and `?->` instead of equivalent verbose
  null checks. Omit the exception variable in a catch when it is unused.
- **Comparisons:** use `===` and `!==` instead of `==` and `!=` to avoid implicit type coercion.
- **Arrays and declarations:** follow array indentation and bracket-placement rules; use trailing commas in
  multiline arrays and multiline function declarations. Let PHPCBF apply the configured spacing and alignment.
- **Strings and cleanup:** prefer single quotes when interpolation or escape sequences do not require double quotes.
  Avoid deprecated functions, unused closure captures, useless aliases, parentheses, and semicolons.
- **Complexity:** the cognitive-complexity sniff has a warning threshold of 11 and an error threshold of 16.
  Reduce nesting and separate responsibilities rather than suppressing complexity findings. The ternary-formatting
  rule has a 100-character line-length limit before requiring multiline formatting.
- **SOLID:** follow the Single Responsibility Principle (SRP), Open/Closed Principle (OCP),
  Liskov Substitution Principle (LSP), Interface Segregation Principle (ISP), and Dependency Inversion Principle (DIP).
  Classes that are not extended and not explicitly marked as public api should be final.
- **Comments:** avoid empty or redundant PHPDoc and generated comments such as "Created by", "Class Foo", or
  trivial constructor/getter descriptions. PHPDoc spacing is checked. Plain `@api`, `@author`, `@category`,
  `@copyright`, `@created`, `@license`, `@package`, `@subpackage`, and `@version` are forbidden, with a specific
  licensed-source exception in the rulesets. Use `@psalm-api` for genuine public API declarations.
- **Consiseness:** avoid cuddling unrelated lines or functionality - it's okay to use newlines to aid code readability.
  Group assignments where possible and prefer placement near or at the begining of a method - unless related lines
  immediately follow, for example, setting mock expectations on a newly created mock or property on a new object.
  Use blank lines to separate out different logical areas. The final return of a method should be separated from
  previous lines.
- **Readability:**
  - Unless method chaining fits on a single line all methods calls after the first must be on a new line.
  - Unless ternary statements fit on a single line all statements after the first must be on a new line - with
    the '?' and ':' characters starting the new lines

Default scans cover `config`, `src`, and `test` in front/API, excluding `config/config.php` and `config/routes.php`.
Smoke scans cover `src` and `context`. Hooks and PHP CI supply explicit changed-file paths rather than relying
solely on these default scan directories. CI checks added/modified front/API PHP files against `origin/main` and
also uses `-n`; full Composer checks report warnings as well.

### Commands

Run these in `service-front/app` or `service-api/app`; both provide the same script names:

```shell
composer run cs-check
composer run cs-fix
composer run cs-check -- -s src
composer run cs-check -- -s src/path/to/Example.php
composer run cs-fix -- src/path/to/Example.php
```

`-s` displays sniff identifiers so a failure can be traced to its rule.
These Composer scripts run PHP inside Docker, not on the host. Their Compose configuration includes
`docker-compose.override.yml`; the API scripts also include `docker-compose.testing.yml`.
Ensure the referenced Compose files exist as part of local setup.

To reproduce the hooks from the repository root:

```shell
pre-commit run phpcbf-front --all-files
pre-commit run phpcs-front --all-files
pre-commit run phpcbf-api --all-files
pre-commit run phpcs-api --all-files
```

Alternatively, run a full scan without the Composer override-file requirement:

```shell
docker compose -f docker-compose.yml run --rm --no-deps actor-app php /app/vendor/bin/phpcs
docker compose -f docker-compose.yml run --rm --no-deps api-app php /app/vendor/bin/phpcs
```

For an appropriately configured local PHP 8.4 environment with Composer development dependencies installed, run
`vendor/bin/phpcs` or `vendor/bin/phpcbf` from the owning PHP component directory.

## Psalm

Psalm checks types and code correctness, rather than just formatting. It detects problems such as invalid argument
and return types, unsafe nullable access, undefined members, and unused code. All three configurations use
`errorLevel="4"`, a component-specific `psalm-baseline.xml`, the PHPUnit plugin, and
`findUnusedPsalmSuppress="true"`. Lower error-level numbers are stricter; level 4 is not the strictest possible setting.
`ensureOverrideAttribute="false"` means Psalm does not require `#[Override]` on every overriding method.

Front/API analysis covers `src` and `config`; smoke analysis covers `src` and `context`. Vendor files are excluded
as analysis targets. Front/API load OpenTelemetry stubs and have targeted `PossiblyUnusedMethod` exemptions for
constructors and service/handler directories. These are not permission to ignore unrelated unused-code reports.

Prefer native types and precise PHPDoc for information native types cannot express, such as generics, array shapes, and
collection element types; validate unknown data rather than annotating it as safe. Fix Psalm findings introduced by your
changes, including INFO-level findings (--show-info=true reveals those hidden by default). When changing a PHP file,
also fix in-scope findings recorded for it in the baseline and remove their entries. Remove obsolete suppressions; don’t
add suppressions or expand the baseline to hide findings.

### Commands

Run in `service-front/app` or `service-api/app`:

```shell
composer run psalm
composer run check
composer run psalm -- --taint-analysis --ignore-baseline
```

`check` runs PHPCS followed by Psalm. The `psalm` script already supplies `--disable-extension=opentelemetry`,
matching CI. CI runs both normal analysis and a separate taint analysis with `--taint-analysis --ignore-baseline`.
Taint analysis tracks untrusted input reaching sensitive operations; ignoring the baseline ensures this scan does
not hide findings recorded there.

Without the Composer override-file requirement, run from the repository root:

```shell
docker compose -f docker-compose.yml run --rm --no-deps actor-app php /app/vendor/bin/psalm --disable-extension=opentelemetry
docker compose -f docker-compose.yml run --rm --no-deps api-app php /app/vendor/bin/psalm --disable-extension=opentelemetry
```

Append `--taint-analysis --ignore-baseline` to reproduce the separate taint scan.

For Docker, use the smoke image and configuration from the repository root (no running browser or application
dependencies are needed for these checks):

```shell
docker compose -f docker-compose.yml -f docker-compose.dependencies.yml -f tests/smoke/docker-compose.smoke.yml run --rm --no-deps smoke-tests php /app/vendor/bin/phpcs
docker compose -f docker-compose.yml -f docker-compose.dependencies.yml -f tests/smoke/docker-compose.smoke.yml run --rm --no-deps smoke-tests php /app/vendor/bin/psalm --disable-extension=opentelemetry
```

## Terraform formatting and TFLint

Terraform formatting standardises indentation, spacing, and layout; it does not prove the infrastructure is valid.
TFLint checks Terraform-specific mistakes and conventions, such as unused declarations and deprecated syntax.
There is no checked-in `.tflint.hcl`; do not assume an AWS-specific ruleset or custom plugin policy is enabled.

To format all Terraform files directly:

```shell
terraform fmt -recursive terraform
```

For read-only checks matching CI's directories:

```shell
(cd terraform/account && terraform fmt -check -recursive && tflint --recursive)
(cd terraform/environment && terraform fmt -check -recursive && tflint --recursive)
```

CI additionally runs `terraform init` and `terraform validate` in each root, and Snyk IaC with a medium severity
threshold and the root's `.snyk` policy. Validation checks configuration consistency beyond formatting/linting and
requires installed providers/modules; initialization may require backend credentials and private-module access.
These are not pre-commit hooks. Do not run `plan` or `apply` just to check style.

## Secrets

Secret detection looks for potential credentials, keys, and high-entropy values. A baseline finding is not proof
that a value is safe. Investigate new findings; remove real secrets and arrange rotation if exposed. Do not add
real credentials to the baseline or use an exclusion to conceal them.

If deliberately reviewing existing baseline classifications, use an installed `detect-secrets` CLI:

```shell
detect-secrets audit .secrets.baseline
```

Only classify a finding as a false positive after checking it. Keep commits on a working branch, without trailing
whitespace or surplus/missing final newlines.

## Whitespace

 - All files containing text should end with an empty newline character.
