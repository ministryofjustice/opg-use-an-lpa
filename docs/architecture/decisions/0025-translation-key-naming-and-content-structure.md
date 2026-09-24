# ADR: Translation key naming and content structure

## Context

The service currently uses user-facing English text directly as translation message identifiers within Twig templates.

For example:

   {% trans %}Request an activation key{% endtrans %}

This couples the translation identifier to the English wording and means that changes to user-facing content can require changes to the Twig template.
The activation key request page is the first page being migrated to explicit translation keys managed through Weblate.
A consistent convention is required for translation keys and for representing larger blocks of translated content.


## Decision

We will use translation keys rather than English user-facing text as translation identifiers. Translation keys will be structured around the feature, page and content type/purpose.


### Translation key convention
The general convention will be:

   <feature>.<page>.<type>.<name>

Examples:

   activation-key-request.start.title
   activation-key-request.start.back-link
   activation-key-request.start.content.can-only-ask-for
   activation-key-request.start.content.cannot-ask-for
   activation-key-request.start.button.continue


### Content blocks

A translation key should represent a logical block of user-facing content rather than an individual sentence or list item where those items form part of the same section.
The translation value may therefore contain the HTML required to render the complete content block.

For example:

   activation-key-request.start.content.can-only-ask-for may contain a paragraph followed by a GOV.UK bullet list.

This allows the content and its translation to be managed as a complete unit in Weblate without requiring changes to the Twig template for routine content changes.


### HTML content

Where required, HTML markup may be included in translation values.
The HTML should be limited to markup required for the presentation and structure of the content and should follow the application's GOV.UK/MOJ markup conventions.
The Weblate entry should use context and notes to make translators aware that HTML is present.


### Links

Where a translated content block requires a dynamic value, Twig will provide the value using a placeholder.

For example:

   {% trans with {'%link%': path('contact-us')} %}
   activation-key-request.start.content.details-need-to-give
   {% context %}html-link{% notes %}Contains GOV.UK HTML markup and a contact-us link using the %link% placeholder.{% endtrans %}

The `%link%` placeholder must be preserved by translators.


### Weblate context and notes

Translation entries containing HTML should use an appropriate context and explanatory notes.

For example:

   Context: html
   Context: html-link

Notes should explain the presence of HTML and any placeholders that must be preserved.


### Benefits of using translation keys

- Translation keys are independent of English wording.
- User-facing content can be managed through Weblate.
- Logical content blocks can be translated as a complete unit.
- Templates contain less user-facing copy.
- Dynamic application routes remain controlled by the application.
- A consistent convention can be applied to future pages.


### Trade-offs of using the convention mentioned

- Translation values may contain HTML.
- Translators need to preserve required HTML and placeholders.
- Incorrect HTML or modified placeholders could affect rendering.
- Developers need to follow the agreed naming convention when adding
  new translation keys.


## Consequence

This convention is introduced initially for the activation-key-request page.
Future pages introducing or migrating translation content should use this convention.


## Example of before and after formatting
Before:

   <p>
       {% trans %}You can only ask for an activation key if:{% endtrans %}
   </p>

   <ul class="govuk-list govuk-list--bullet">
       <li>
           {% trans %}you’re a donor or an attorney on the LPA...{% endtrans %}
       </li>
       <li>
           {% trans %}the LPA was made for use in England and Wales{% endtrans %}
       </li>
   </ul>


After:

   {% trans %}
      activation-key-request.start.content.can-only-ask-for
      {% context %}html{% notes %}
      Contains GOV.UK HTML elements and classes. Preserve the HTML structure and classes when translating.
   {% endtrans %}
