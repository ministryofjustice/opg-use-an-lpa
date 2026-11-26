---
name: translation-migration
description: Use when migrating service-front Twig templates from English message IDs to explicit translation keys. Covers ADR-25 key naming, grouping HTML content blocks, sourcing English and Welsh copy, gettext context and placeholders, updating the POT, en_devel, en_GB and cy catalogs, and validation.
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

1. List each user-visible string in the template and its role: title, heading, body content, form label, hint, button, and so on. Static attributes and analytics labels are not translation strings; migrate only user-facing copy.
2. List the current common keys from `service-front/app`, as the set grows over time:

   ```shell
   grep 'msgid "common\.' languages/messages.pot
   ```

3. Assign a key to each string or content block using the rules below.

### Key naming

- Follow `<feature>.<page>.<type>-<name>[-<suffix>]`. Keep the feature and page segments consistent within a template.
- Use a period only for levels that descend the HTML or visual hierarchy (`feature.page.visual_hierarchy_1.visual_hierarchy_2`), for example a field and its label: `field-dob.hint`. Join an element's type and variant with a hyphen, because the name is a variant of the type, not a child of it. Use `content-details`, `button-continue`, `label-donor-name`, `link-change-role`, `value-not-provided` and `warning-consent`, never `content.details`, `button.continue`, `label.donor-name`, `link.change-role`, `value.not-provided` or `warning.consent`. Page-level keys such as `title` and `heading-page` follow the same rule.
- For form fields, use `field-<field-name>` as the type and `label` or `hint` as the name, for example `add-lpa.activation-key.field-activation-key.label` and `add-lpa.date-of-birth.field-dob.hint`. Append variants as suffixes, for example `add-lpa.lpa-reference-number.field-reference-number.hint-with-paper-verification`.
- Where the en_GB copy renames a field or value relative to the template (for example “Town or city” becomes “Address line 3”), name the key after the en_GB copy (for example `field-actor-address-3.label`).
- For `<details>` reveal elements, name the pair `<feature>.<page>.reveal-<variant>-title` and `<feature>.<page>.reveal-<variant>-content`, where `<variant>` describes the reveal (for example `request-activation-key.info.reveal-registration-date-title` and `request-activation-key.info.reveal-registration-date-content`). Do not put `content-` in the reveal content key.
- Name a block of translated HTML content `content-<name>`.
- Do not leave an English sentence as an identifier.

### Common keys

- Use an existing `common.<type>-<name>` key when both the wording and the meaning match.
- Propose a new common key only when both the wording and meaning are generic and reused across journeys. Search all templates for candidates, including ones already migrated. Migrated templates contain keys rather than English, so match them on the English `msgstr` values in `languages/en_devel/LC_MESSAGES/messages.po`.
- Ask the user to approve each new common key before using it, listing every page it would affect.

### Content blocks

- Treat adjacent paragraphs and lists that explain the same subject as one logical content block. Put their GOV.UK HTML structure into one translation value rather than creating a key per sentence or list item, so translators can manage the whole section together. A heading is often a good place to start a new block.
- Keep distinct UI roles as separate entries: a page title or heading, a form label and a field hint are not part of an explanatory body block. Do not combine unrelated sections merely to reduce the key count.

## Source the copy

Translation values are copied from the existing catalogs, not written or reworded. The en_GB and cy translations are the translators’ work: copy them exactly and do not review or correct their wording. Preserve existing link destinations, markup, classes and functional structure unless the requested migration includes a content change.

- **English**: take the English from the original string’s `msgstr` in `languages/en_GB/LC_MESSAGES/messages.po`, not from the template. It may differ from the template wording, and en_GB is correct. Report each difference between the template and en_GB to the user for information.
- **Welsh**: take the Welsh from the original string’s `msgstr` in `languages/cy/LC_MESSAGES/messages.po`. For a merged content block, combine the Welsh of each original string in the same structure as the English. Do not write new Welsh. Report to the user any original string whose Welsh is missing or empty.
- **Punctuation or text outside the translation**: where the template has punctuation or text directly next to a translation tag, for example the full stop in `{% trans %}We may need to contact you{% endtrans %}.`, move it into the translation value in every language, even though the original entries lack it. This keeps the rendered copy unchanged.

Apply only these formatting changes to the copied values, in every language:

- **LPA abbreviation**: in values rendered as HTML (not titles or other plain-text values), wrap each unwrapped “LPA” in an `<abbr>`: `<abbr title="lasting power of attorney">LPA</abbr>` in English and `<abbr title="atwrneiaeth arhosol">LPA</abbr>` in Welsh. For the plural, use `<abbr title="lasting powers of attorney">LPAs</abbr>` and `<abbr title="atwrneiaethau arhosol">LPAs</abbr>`. Remove `<abbr>` markup from plain-text values.
- **Quotes**: replace ASCII quotes and apostrophes (`'` and `"`) in text with their unicode counterparts (`‘ ’` and `“ ”`). Do not change quotes inside HTML attributes.

## Update the Twig template

- Use `{% trans %}key{% endtrans %}` for translated values in the template body.
- Use the `| trans` filter only for plain-text values passed to a helper, such as a form label. The filter cannot carry a gettext context or translator notes, so extraction always records its key without a context.
- When a value passed to a form helper (for example a label, hint or radio option) contains HTML, capture it with `{% set %}` and a `{% trans %}` tag that declares the `html` context and a short note, then pass the variable:

  ```twig
  {% set referenceNumberHint %}
      {% trans %}
          feature.page.field-reference-number.hint
      {% context %}html{% notes %}Contains a line break. Preserve the HTML markup when translating.{% endtrans %}
  {% endset %}

  {{ govuk_form_element(form.get('reference_number'), {'hint': referenceNumberHint}) }}
  ```

- For content containing HTML, use gettext context `html` and translator notes describing the markup to preserve. Include a content block’s paragraph markup in its translation value even when the block contains only one paragraph; do not leave a paragraph wrapper around the translation call in Twig.
- For a URL resolved at runtime (such as an internal route), keep the route under application control: pass it as a named placeholder in `{% trans with {'%link%': path(...)} %}`, use `%link%` in the translation value, and use context `html-link`. Notes should describe the link and any placeholders translators must preserve. Use a descriptive placeholder name (for example `%contact-link%`) if a block needs more than one.
- For a fully qualified external URL (for example `https://www.gov.uk/...`, `mailto:...` or `tel:...`), do not use a placeholder: write the URL directly into the translation value, use context `html`, and say in the notes that link destinations must be preserved.
- Do not put dynamic route generation or application logic into a translation value.
- Keep the template readable: separate each section with a blank line. A section is a heading with the content block it introduces, a standalone content block, a warning or other component, a partial include, or a button group. Keep a heading directly above its content block without a blank line between them. Retain any existing blank lines, and use spaces rather than tabs to indent lines you change.

Example content block:

```twig
{% trans with {'%link%': path('contact-us')} %}
    feature.page.content-details
{% context %}html-link{% notes %}Contains GOV.UK HTML markup and a contact-us link using the %link% placeholder.{% endtrans %}
```

Example section spacing:

```twig
<h1 class="govuk-heading-xl">{% trans %}feature.page.heading-page{% endtrans %}</h1>

{% trans %}
    feature.page.content-intro
{% context %}html{% notes %}Contains GOV.UK HTML elements and classes.{% endtrans %}

<h2 class="govuk-heading-m">{% trans %}feature.page.heading-details{% endtrans %}</h2>
{% trans %}
    feature.page.content-details
{% context %}html{% notes %}Contains GOV.UK HTML elements and classes.{% endtrans %}

{{ include('@partials/contact-details/email.html.twig') }}
```

## Update catalogs

Weblate is under a change freeze while migrations are in progress, so all three catalogs are updated in the same change, doing the work Weblate would otherwise do. Because the change freeze might be removed at some point always ask if it is still in effect before starting work. If it is not in effect do not update the `cy` or `en_GB` catalogs. Do not generate or commit MO files.

Edit each PO file one entry block at a time. Do not re-save a whole catalog with a tool that rewraps entries (such as `msgmerge` or a PO library), as that creates unrelated churn.

### messages.pot

From `service-front/app`, extract the keys:

```shell
composer run extract
```

- Check that `languages/messages.pot` contains the new keys with the expected contexts, notes and placeholders. If they are missing, the extraction may not have finished writing: run it again.
- Extraction does not remove entries that are no longer used. Remove POT entries for strings and keys that no template uses any more.
- Extraction may refresh source references or metadata elsewhere in the file. Inspect the diff and avoid retaining unrelated churn.

### en_devel

In `languages/en_devel/LC_MESSAGES/messages.po`:

- Add each new key with its English value (see “Source the copy”), copying its notes, references and context from the POT.
- Update the `#:` source references of existing entries for the edited templates to match the POT.
- Remove original string entries that map one-to-one onto a new key, including identical strings replaced by a common key.
- Keep each original string that was merged with others into a single content block, with its English `msgstr`, and mark it `#, fuzzy`, so the manual copy-across work can be managed.

### en_GB and cy

In `languages/en_GB/LC_MESSAGES/messages.po` and `languages/cy/LC_MESSAGES/messages.po`:

- **Never remove or edit existing entries**, including original string entries no template uses any more. Weblate removes them later. Only add entries.
- Add every key in `messages.pot` that is missing from the file. Append the new entries to the end of the file in POT order, copying the POT block’s translator notes (`#.`), source references (`#:`), `msgctxt` and `msgid` lines verbatim. Do not change the file header.
- Use the en_devel value for en_GB, and the Welsh copy for cy (see “Source the copy”), with the same HTML structure, classes, placeholders and links as the en_devel value. If the Welsh is missing, add the entry with an empty `msgstr`.
- Write `msgstr` on a single line, splitting into continuation lines only after each `\n`, as Weblate does.

## Validate the migration

- Search the edited template for remaining English message IDs in `{% trans %}` blocks or `| trans` filters.
- Confirm each key follows ADR-25 naming, each content block is grouped logically, and all placeholders, routes and required markup match between template and catalogs.
- Check that every key in `messages.pot` exists in en_devel, en_GB and cy, and that each new en_GB and cy entry has the same HTML tags and placeholders as its en_devel value.
- Check that the en_GB and cy diffs contain only added lines.
- Run `msgfmt --check -o /dev/null` on the en_devel, en_GB and cy `messages.po` files, and `git diff --check`.
- Use the IDE’s template inspections or the project’s available Twig/template validation to check each edited template. Review the complete diff to catch accidental catalog or unrelated changes.

## UI tests

The UI tests use the compiled MO files, which are gitignored. Rebuild them, then run the tests from `service-front/app`:

```shell
for L in en_GB cy en_devel; do msgfmt -o languages/$L/LC_MESSAGES/messages.mo languages/$L/LC_MESSAGES/messages.po; done
composer run ui-test -- --format=progress --no-colors
```

Fix broken test steps by preference with the first method below. Use the second when the first is not deterministic enough.

- Replace `$this->ui->assertPageContainsText` with `$this->assertPageContainsTranslatedText`, passing the key.
- Add a `data-content-id` to the HTML element being checked (for example `h.cannot-find-lpa` for a heading that originally read “We cannot find the LPA”), and check it with `$this->ui->assertElementOnPage('h1[data-content-id="<element_id_chosen>"]');`.
