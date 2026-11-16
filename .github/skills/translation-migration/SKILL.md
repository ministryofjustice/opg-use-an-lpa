---
name: translation-migration
description: Use when migrating service-front Twig templates from English message IDs to explicit translation keys. Covers ADR-25 key naming, grouping HTML content blocks, gettext context and placeholders, catalog extraction, and validation.
---

# Translation migration

Use this workflow when converting a `service-front` Twig template to explicit translation keys. The goal is to separate stable translation identifiers from English copy, while keeping translated content coherent and minimizing template-level content blocks.

## Read the project conventions first

1. Read `docs/architecture/decisions/0025-translation-key-naming-and-content-structure.md`.
2. Read “Adding and managing translation keys” in `service-front/app/README.md`.
3. Inspect the target template and one or more migrated templates, especially:
   - `service-front/app/src/Actor/templates/actor/request-activation-key/before-requesting-activation-key-info.html.twig`
   - `service-front/app/src/Actor/templates/actor/add-lpa/activation-key.html.twig`
4. Check the target file and translation catalogs for existing changes before editing. Preserve unrelated work.

## Plan the keys and content blocks

- Replace English message IDs with keys following `<feature>.<page>.<type>.<name>[-<suffix>]`. Keep the page/feature segment consistent within the template.
- For form fields, use `field-<field-name>` as the type and `label` or `hint` as the name, for example `add-lpa.activation-key.field-activation-key.label` and `add-lpa.date-of-birth.field-dob.hint`. Append variants as suffixes, for example `add-lpa.lpa-reference-number.field-reference-number.hint-with-paper-verification`.
- Reuse a `common.<type>-<name>` key only when both the wording and meaning are generic and reused across journeys. The migrated pages establish `common.back-link`, `common.button-continue`, and `common.button-cancel` but others might be identified later. If new common keys are identified prompt for approval.
- List each user-visible string and its role before editing: title, heading, body instructions, form label, hint, button, and so on. Do not leave a translated English sentence as an identifier.
- Treat adjacent paragraphs and lists that explain the same subject as one logical content block. Put their required GOV.UK HTML structure into one translation value rather than creating a key per sentence or list item. This reduces the number of keys while allowing translators to manage the whole section together. A heading may be a good place to introduce a new block.
- Keep distinct UI roles as separate entries: for example, a page title or heading, a form label, and a field hint are not part of the explanatory body block. Do not combine unrelated sections merely to reduce the key count.
- Preserve existing copy, punctuation, link destinations, markup, classes, and functional structure unless the requested migration includes a content change. Existing copy should be sourced from the English (en_GB) messages.po file as it may differ from the English found in the templates. Translation migration must not silently rewrite product wording.

## Update the Twig template

- Use `{% trans %}key{% endtrans %}` for standalone translated values and the translation filter where the template expects a translated string value, such as a form label.
- For translated content containing HTML, use a logical `.content.<name>` key, gettext context `html`, and translator notes describing the markup to preserve.
- Include a content block's paragraph markup in its translation value even when the block contains only one paragraph; do not leave a paragraph wrapper around the translation call in Twig.
- For HTML containing a dynamic URL, keep the route under application control: pass it as a named placeholder in `{% trans with {'%link%': path(...)} %}`, use `%link%` in the translation value, and use context `html-link`. Notes should describe the link and any placeholders translators must preserve.
- Use an `html` context and a short note for a hint that contains HTML such as line breaks. Capture the translated value in Twig when the consuming form helper needs a string value.
- Do not put dynamic route generation or application logic into a translation value.

Example content block:

```twig
{% trans with {'%link%': path('contact-us')} %}
    feature.page.content.details
{% context %}html-link{% notes %}Contains GOV.UK HTML markup and a contact-us link using the %link% placeholder.{% endtrans %}
```

## Update catalogs

From `service-front/app`, extract the keys:

```shell
composer run extract
```

Review `languages/messages.pot` for the new keys, gettext contexts, and placeholders. Extraction may also refresh source references or metadata elsewhere in the catalog; inspect the diff and avoid retaining unrelated churn.

Follow the catalog process in the service README: merge the POT into `languages/en_devel/LC_MESSAGES/messages.po`, and provide the designed English content and HTML there as appropriate for the initial change. Do not manually edit the Weblate-managed English/Welsh catalogs or generate/commit MO files; those translations are handled through Weblate after merge.

## Validate the migration

- Search the edited template for remaining English message IDs in `{% trans %}` blocks or `|trans` filters. Static attributes and analytics labels are not automatically translation strings; migrate only user-facing copy.
- Confirm each key follows ADR-25 naming, each content block is grouped logically, and all placeholders/routes and required markup match between template and catalog.
- Check that `messages.pot` contains the expected new keys and contexts, and that `en_devel/messages.po` has the corresponding content and preserves placeholders.
- Run `msgfmt --check -o /dev/null service-front/app/languages/en_devel/LC_MESSAGES/messages.po` and `git diff --check`.
- Use the IDE's template inspections or the project's available Twig/template validation to check the edited file. Review the complete diff to catch accidental catalog or unrelated changes.

## UI tests

Run the behat UI tests for service-front and fix broken test steps with one of two methods. By preference use the first option but it may be more appropriate to use the second if the first is not deterministic enough.

- Replace instances of `$this->ui->assertPageContainsText` with `$this->assertPageContainsTranslatedText`
- Add a `data-content-id` with value (e.g. "h.cannot-find-lpa" for a heading that original read "We cannot find the LPA") to the HTML element being validated by the original assertion and check it in the test with `$this->ui->assertElementOnPage('h1[data-content-id="<element_id_chosen>"]');`
