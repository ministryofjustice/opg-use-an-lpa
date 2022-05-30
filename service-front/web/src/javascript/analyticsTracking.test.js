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
    <div class="govuk-error-summary" aria-labelledby="error-summary-title" role="alert" tabindex="-1" data-module="error-summary">
            <h2 class="govuk-error-summary__title" id="error-summary-title">
                There is a problem            </h2>
            <div class="govuk-error-summary__body">
                <ul class="govuk-list govuk-error-summary__list">
                                                                        <li>
                                <a data-gaEventType="onLoad" data-gaCategory="Form errors" data-gaAction="#email" data-gaLabel="#email - Enter an email address in the correct format, like name@example.com" href="#email">Enter an email address in the correct format,
                                 like name@example.com</a>
                            </li>
                                                                                                <li>
                                <a data-gaEventType="onLoad" data-gaCategory="Form errors" data-gaAction="#password" data-gaLabel="#password - Enter your password" href="#password">Enter your password</a>
                            </li>
                                                            </ul>
            </div>
        </div>
    `;

const lpaSummary = `
        <main class="govuk-main-wrapper" id="main-content" role="main">
            <nav class="moj-sub-navigation" aria-label="Sub navigation">
                <ul class="moj-sub-navigation__list">
                    <li class="moj-sub-navigation__item">
                        <a data-gaEventType="onClick" data-gaAction="Download" data-gaCategory="LPA summary" data-gaLabel="Download this LPA summary" class="govuk-link moj-sub-navigation__link moj-sub-navigation__link--underline"
                           href="https://localhost:9001/download-lpa">Download this LPA summary</a>
                    </li>
                </ul>
            </nav>
        </main>
    `;

const accessCodeReveal = `
        <main class="govuk-main-wrapper" id="main-content" role="main" >
            <details id="access-code-reveal" class="govuk-details" data-module="govuk-details" data-gaCategory="Details" data-gaAction="Access code" data-gaLabel="The code I\'ve been given does not begin with a V">
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

    test('it initialised correctly', () => {
        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                  '0': 'config',
                  '1': 'UA-12345',
                })
            ])
        )
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

    test('it fires click events on the 2 external links', () => {
        const linkSelector = document.querySelectorAll('a');
        for (let i = 0; i < linkSelector.length; i++) {
            linkSelector[i].click();
        }

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': 'click',
                    '2': {
                        event_category: 'outbound',
                        event_label: 'http://localhost',
                        value: ''
                    }
                }),
                expect.objectContaining({
                    '0': 'event',
                    '1': 'click',
                    '2': {
                        event_category: 'outbound',
                        event_label: 'https://localhost',
                        value: ''
                    }
                })
            ])
        )
    });

    test('it should fire events correctly', () => {
        analyticsTracking.sendGoogleAnalyticsEvent('event', 'event_category', 'event_label', 'value')

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': 'event',
                    '2': {
                        event_category: 'event_category',
                        event_label: 'event_label',
                        value: 'value'
                    }
                })
            ])
        )
    });

    test('it should sanitize the data being sent', () => {
        analyticsTracking.sendGoogleAnalyticsEvent('test@test.com', '01234567890', 'NG156WL', '28/06/1984')

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': '[sanitised]',
                    '2': {
                        event_category: '[sanitised]',
                        event_label: '[sanitised]',
                        value: '[sanitised]'
                    }
                })
            ])
        )
    });

    test('it should strip querystrings out of the pageview', () => {
        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '2': {
                        'linker': { 'domains': ['www.gov.uk'] },
                        'cookie_flags': 'SameSite=None;Secure',
                        'transport_type': 'beacon',
                        'anonymize_ip': true,
                        'allow_google_signals': false,
                        'allow_ad_personalization_signals': false,
                        'page_title': 'Test Page Title',
                        'page_path': '/use-lpa'
                      }
                })
            ])
        )
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

    test('it should fire off form error events correctly', () => {
        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': '#email',
                    '2': {
                        event_category: 'Form errors',
                        event_label: '#email - Enter an email address in the correct format, like [sanitised]',
                        value: ''
                    }
                }),
                expect.objectContaining({
                    '0': 'event',
                    '1': '#password',
                    '2': {
                        event_category: 'Form errors',
                        event_label: '#password - Enter your password',
                        value: ''
                    }
                })
            ])
        )
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

    test('it should fire an event when I click to download my lpa summary', () => {
        const linkSelector = document.querySelector('a[href$="/download-lpa"]');
        linkSelector.click();

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': 'Download',
                    '2': {
                        event_category: 'LPA summary',
                        event_label: 'Download this LPA summary',
                        value: ''
                    }
                })
            ])
        )
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

    test('it should fire an event when I click the access code reveal', () => {

        //test open
        analyticsTracking.observeMutations([
            {
                type: "attributes",
                oldValue: null,
                target: {
                    getAttribute: jest.fn(() => "govuk-details")
                        .mockImplementationOnce(() => 'govuk-details')
                        .mockImplementationOnce(() => 'Details')
                        .mockImplementationOnce(() => 'Access Codes')
                        .mockImplementationOnce(() => 'Some Label')
                }
            }],
            analyticsTracking
        )

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': 'Details',
                    '2': {
                        event_category: 'Access Codes',
                        event_label: 'Some Label open',
                        value: ''
                    }
                })
            ])
        )

        //test close
        analyticsTracking.observeMutations([
            {
                type: "attributes",
                oldValue: 'close',
                target: {
                    getAttribute: jest.fn(() => "govuk-details")
                        .mockImplementationOnce(() => 'govuk-details')
                        .mockImplementationOnce(() => 'Details')
                        .mockImplementationOnce(() => 'Access Codes')
                        .mockImplementationOnce(() => 'Some Label')
                }
            }],
            analyticsTracking
        )

        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'event',
                    '1': 'Details',
                    '2': {
                        event_category: 'Access Codes',
                        event_label: 'Some Label close',
                        value: ''
                    }
                })
            ])
        )
    });

    test('it wont fire an event when I click a details tag that does not have the govuk-details data module', () => {

        let dataLayerLength = global.dataLayer.length
        analyticsTracking.observeMutations([
            {
                type: "attributes",
                oldValue: null,
                target: {
                    getAttribute: jest.fn(() => "govuk-details")
                        .mockImplementationOnce(() => 'Details')
                        .mockImplementationOnce(() => 'Access Codes')
                        .mockImplementationOnce(() => 'Some Label')
                }
            }],
            analyticsTracking
        );

        expect(global.dataLayer == dataLayerLength);
    });
});
