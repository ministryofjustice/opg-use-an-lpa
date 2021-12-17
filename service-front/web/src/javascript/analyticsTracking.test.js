import GoogleAnalytics from './googleAnalytics';
import AnalyticsTracking from './analyticsTracking';

    const linkList = `
    <div>
        <a href="/">Relative URL</a>
        <a href="http://localhost">HTTP URL</a>
        <a href="https://localhost">HTTPS URL</a>
    </div>
    `;

    const formErrors = `
        <form name="create_account" method="post" novalidate="">
        <fieldset class="govuk-fieldset">

            <legend class="govuk-fieldset__legend govuk-fieldset__legend--xl">
                <h1 class="govuk-fieldset__heading">Create an account</h1>
            </legend>

            <div class="govuk-form-group govuk-form-group--error">
                <label class="govuk-label" for="email">
                    Enter your email address
                </label>
                <span class="govuk-error-message">
                    <span class="govuk-visually-hidden">Error:</span> Enter an email address in the correct format, like name@example.com
                </span>
                <input class="govuk-input" id="email" name="email" type="email" value="" inputmode="email" spellcheck="false" autocomplete="email">
            </div>


            <div class="govuk-form-group govuk-form-group--error">
                <label class="govuk-label" for="show_hide_password">
                    Create a password
                </label>
                <span class="govuk-error-message">
                <span class="govuk-visually-hidden">Error:</span> Password must be 8 characters or more
                </span>
                <span class="govuk-error-message">
                <span class="govuk-visually-hidden">Error:</span> Password must include a number
                </span>
                <span class="govuk-error-message">
                <span class="govuk-visually-hidden">Error:</span> Password must include a capital letter
                </span>
                <input class="govuk-input govuk-input moj-password-reveal__input govuk-input--width-20" id="show_hide_password" name="show_hide_password" type="password" value="">
                <button class="govuk-button govuk-button--secondary moj-password-reveal__button" data-module="govuk-button" type="button" data-showpassword="Show" data-hidepassword="Hide">Show</button>
            </div>

            <button data-prevent-double-click="true" type="submit" class="govuk-button">Create account</button>
        </fieldset>
    </form>
    `;

    const lpaSummary = `
        <main class="govuk-main-wrapper" id="main-content" role="main">
            <nav class="moj-sub-navigation" aria-label="Sub navigation">
                <ul class="moj-sub-navigation__list">
                    <li class="moj-sub-navigation__item">
                        <a data-attribute="ga-event" data-gaAction="Download" data-gaCategory="LPA summary" data-gaLabel="Download this LPA summary" class="govuk-link moj-sub-navigation__link moj-sub-navigation__link--underline"
                           href="https://localhost:9001/download-lpa">Download this LPA summary</a>
                    </li>
                </ul>
            </nav>
        </main>
    `;

    const accessCodeReveal = `
        <main class="govuk-main-wrapper" id="main-content" role="main">
            <details data-attribute="ga-event" data-gaAction="AccessCodeReveal" data-gaCategory="Access code" data-gaLabel="The code I\'ve been given does not begin with a V" id="access-code-reveal" id="access-code-reveal" class="govuk-details" data-module="govuk-details">
                <summary class="govuk-details__summary" role="button">
                    <span class="govuk-details__summary-text">
                        {% trans %}The code I've been given does not begin with a V{% endtrans %}
                    </span>
                </summary>
                <div class="govuk-details__text">
                    <p>{% trans %}The donor or attorney may have given you the wrong code.{% endtrans %}</p>
                    <p>{% trans %}Ask them to go to www.gov.uk/use-lpa to create an LPA access code for your organisation.{% endtrans %}</p>
                </div>
            </details>
        </main>
    `;

describe('given Google Analytics datalayer is not setup', () => {
    let useAnalytics;
    let analyticsTracking;
    beforeEach(() => {
        global.dataLayer = [];
        document.body.innerHTML = linkList;
        useAnalytics = new GoogleAnalytics('UA-12345');
        analyticsTracking = new AnalyticsTracking();
    });

    /**
     * Description of data layer 2 and 3 in gtag
     * [Arguments] {
          '0': 'event',
          '1': 'event',
          '2': {
                event_category: 'event_category',
                event_label: 'event_label',
                value: 'value'
             }
            }
     */
    test('it initialised correctly', () => {
        const linkSelector = document.querySelectorAll('a');
        for (let i = 0; i < linkSelector.length; i++) {
            linkSelector[i].click();
        }

        expect(global.dataLayer[2][0]).toBe('event');
        expect(global.dataLayer[2][1]).toBe('click');
        expect(global.dataLayer[2][2].event_category).toBe('outbound');
        expect(global.dataLayer[2][2].event_label).toBe('http://localhost');
        expect(global.dataLayer[3][0]).toBe('event');
        expect(global.dataLayer[3][1]).toBe('click');
        expect(global.dataLayer[3][2].event_category).toBe('outbound');
        expect(global.dataLayer[3][2].event_label).toBe('https://localhost');
        expect(global.dataLayer.length).toEqual(4);
    });
});

describe('given Google Analytics is enabled', () => {
    let useAnalytics;
    let analyticsTracking;
    beforeEach(() => {
        document = document.documentElement;
        document.body.innerHTML = linkList;
        document.title = 'Test Page Title';
        delete global.window.location;
        global.window = Object.create(window);
        global.window.location = {
            port: '80',
            protocol: 'https:',
            host: 'localhost',
            hostname: 'localhost',
            pathname: '/use-lpa?email=email@test.com',
            search: "?v=email@test.com"
        };
        global.dataLayer = [];
        useAnalytics = new GoogleAnalytics('UA-12345');
        analyticsTracking = new AnalyticsTracking();
    });

    /**
     * Description of data layer 2 and 3 in gtag
     * [Arguments] {
      '0': 'event',
      '1': 'event',
      '2': {
            event_category: 'event_category',
            event_label: 'event_label',
            value: 'value'
         }
        }
     */
    test('it fires click events on the 2 external links', () => {
        const linkSelector = document.querySelectorAll('a');
        for (let i = 0; i < linkSelector.length; i++) {
            linkSelector[i].click();
        }

        expect(global.dataLayer[2][0]).toBe('event');
        expect(global.dataLayer[2][1]).toBe('click');
        expect(global.dataLayer[2][2].event_category).toBe('outbound');
        expect(global.dataLayer[2][2].event_label).toBe('http://localhost');
        expect(global.dataLayer[4][0]).toBe('event');
        expect(global.dataLayer[4][1]).toBe('click');
        expect(global.dataLayer[4][2].event_category).toBe('outbound');
        expect(global.dataLayer[4][2].event_label).toBe('https://localhost');
        expect(global.dataLayer.length).toEqual(6);
    });


        /**
         * Description of data layer 2 in gtag
         * [Arguments] {
          '0': 'event',
          '1': 'event',
          '2': {
                event_category: 'event_category',
                event_label: 'event_label',
                value: 'value'
             }
            }
         */
    test('it should fire events correctly', () => {
        analyticsTracking.sendGoogleAnalyticsEvent('event', 'event_category', 'event_label', 'value')

        expect(global.dataLayer.length).toEqual(3);
        expect(global.dataLayer[2]).not.toBeUndefined();
        expect(global.dataLayer[2][0]).toBe('event');
        expect(global.dataLayer[2][1]).toBe('event');

        expect(global.dataLayer[2][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[2][2].event_category).toBe('event_category');

        expect(global.dataLayer[2][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[2][2].event_label).toBe('event_label');

        expect(global.dataLayer[2][2].value).not.toBeUndefined();
        expect(global.dataLayer[2][2].value).toBe('value');
    });

        /**
         * Description of data layer 2 in gtag
         * [Arguments] {
          '0': 'event',
          '1': 'event',
          '2': {
                event_category: 'event_category',
                event_label: 'event_label',
                value: 'value'
             }
            }
         */
    test('it should sanitize the data being sent', () => {
        analyticsTracking.sendGoogleAnalyticsEvent('test@test.com', '01234567890', 'NG156WL', '28/06/1984')
        expect(global.dataLayer.length).toEqual(3);
        expect(global.dataLayer[2]).not.toBeUndefined();
        expect(global.dataLayer[2][0]).toBe('event');
        expect(global.dataLayer[2][1]).toBe('[sanitised]');

        expect(global.dataLayer[2][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[2][2].event_category).toBe('[sanitised]');

        expect(global.dataLayer[2][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[2][2].event_label).toBe('[sanitised]');

        expect(global.dataLayer[2][2].value).not.toBeUndefined();
        expect(global.dataLayer[2][2].value).toBe('[sanitised]');
    });

    test('it should strip querystrings out of the pageview', () => {
        expect(global.dataLayer[1][2].page_title).toBe('Test Page Title');
        expect(global.dataLayer[1][2].page_path).toBe('/use-lpa');
    });
});


describe('given a form has reported validation errors', () => {
    let useAnalytics;
    let analyticsTracking;
    beforeEach(() => {
        document = document.documentElement;
        document.body.innerHTML = formErrors;
        useAnalytics = new GoogleAnalytics('UA-12345');
        analyticsTracking = new AnalyticsTracking();
    });

    /**
     * Description of data layer 2 and 3 in gtag
     * [Arguments] {
          '0': 'event',
          '1': 'event',
          '2': {
                event_category: 'event_category',
                event_label: 'event_label',
                value: 'value'
             }
            }
     */
    test('it should fire off form error events correctly', () => {
        expect(global.dataLayer[4][1]).toBe('Enter your email address');
        expect(global.dataLayer[4][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[4][2].event_category).toBe('Form errors');
        expect(global.dataLayer[4][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[4][2].event_label).toBe('#email - Enter an email address in the correct format, like [sanitised]');

        expect(global.dataLayer[5][1]).toBe('Create a password');
        expect(global.dataLayer[5][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[5][2].event_category).toBe('Form errors');
        expect(global.dataLayer[5][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[5][2].event_label).toBe('#show_hide_password - Password must be 8 characters or more');

        expect(global.dataLayer[6][1]).toBe('Create a password');
        expect(global.dataLayer[6][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[6][2].event_category).toBe('Form errors');
        expect(global.dataLayer[6][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[6][2].event_label).toBe('#show_hide_password - Password must include a number');

        expect(global.dataLayer[7][1]).toBe('Create a password');
        expect(global.dataLayer[7][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[7][2].event_category).toBe('Form errors');
        expect(global.dataLayer[7][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[7][2].event_label).toBe('#show_hide_password - Password must include a capital letter');
    });
});

describe('given I am viewing the LPA summary', () => {
    let useAnalytics;
    let analyticsTracking;
    beforeEach(() => {
        document.body.innerHTML = lpaSummary;
        useAnalytics = new GoogleAnalytics('UA-12345');
        analyticsTracking = new AnalyticsTracking();
    });

    /**
     * Description of data layer 2 and 3 in gtag
     * [Arguments] {
      '0': 'event',
      '1': 'event',
      '2': {
            event_category: 'event_category',
            event_label: 'event_label',
            value: 'value'
         }
        }
     */
    test('it should fire an event when I click to download my lpa summary', () => {
        const linkSelector = document.querySelector('a[href$="/download-lpa"]');
        linkSelector.click();

        expect(global.dataLayer[11][1]).toBe('Download');
        expect(global.dataLayer[11][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[11][2].event_category).toBe('LPA summary');
        expect(global.dataLayer[11][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[11][2].event_label).toBe('Download this LPA summary');
    });
});

describe('given I click the access code reveal', () => {
    let useAnalytics;
    let analyticsTracking;
    beforeEach(() => {
        document.body.innerHTML = accessCodeReveal;
        useAnalytics = new GoogleAnalytics('UA-12345');
        analyticsTracking = new AnalyticsTracking();
    });

    /**
     * Description of data layer 2 and 3 in gtag
     * [Arguments] {
      '0': 'event',
      '1': 'event',
      '2': {
            event_category: 'event_category',
            event_label: 'event_label',
            value: 'value'
         }
        }
     */
    test('it should fire an event when I click the access code reveal', () => {
        const revealSelector = document.querySelector('details[id$="access-code-reveal"]');
        revealSelector.click();

        expect(global.dataLayer[22][1]).toBe('AccessCodeReveal');
        expect(global.dataLayer[22][2].event_category).not.toBeUndefined();
        expect(global.dataLayer[22][2].event_category).toBe('Access code');
        expect(global.dataLayer[22][2].event_label).not.toBeUndefined();
        expect(global.dataLayer[22][2].event_label).toBe('The code I\'ve been given does not begin with a V');
    });
});
