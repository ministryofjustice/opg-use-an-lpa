import googleAnalytics from './googleAnalytics';

    const linkList = `
    <div>
        <a href="/">Relative URL</a>
        <a href="http://localhost">HTTP URL</a>
        <a href="https://localhost">HTTPS URL</a>
    </div>
    `;

describe('given Google Analytics datalayer is not setup', () => {
    let useAnalytics;
    beforeEach(() => {
        document.body.innerHTML = linkList;
        useAnalytics = new googleAnalytics('UA-12345');
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
        expect(global.dataLayer[2][2].event_label).toBe('http://localhost/');
        expect(global.dataLayer[3][0]).toBe('event');
        expect(global.dataLayer[3][1]).toBe('click');
        expect(global.dataLayer[3][2].event_category).toBe('outbound');
        expect(global.dataLayer[3][2].event_label).toBe('https://localhost/');
        expect(global.dataLayer.length).toEqual(4);
    });
});

describe('given Google Analytics is enabled', () => {
    let useAnalytics;
    beforeEach(() => {
        document.body.innerHTML = linkList;
        document.title = 'Test Page Title';
        delete global.window.location;
        global.window = Object.create(window);
        global.window.location = {
            port: '80',
            protocol: 'https:',
            host: 'localhost',
            hostname: 'localhost',
            pathname: '/use-lpa',
            search: "?v=email@test.com"
        };
        global.dataLayer = [];
        useAnalytics = new googleAnalytics('UA-12345');
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
        expect(global.dataLayer[2][2].event_label).toBe('http://localhost/');
        expect(global.dataLayer[3][0]).toBe('event');
        expect(global.dataLayer[3][1]).toBe('click');
        expect(global.dataLayer[3][2].event_category).toBe('outbound');
        expect(global.dataLayer[3][2].event_label).toBe('https://localhost/');
        expect(global.dataLayer.length).toEqual(4);
    });

    /**
     * Description of data layer 1 in gtag
     *  'config',
     *  this.analyticsId, {
            'linker': {
                'domains': ['www.gov.uk']
        },
        'transport_type': 'beacon',
        'anonymize_ip': true,
        'allow_google_signals': false, //display-features
        'allow_ad_personalization_signals': false //display-features
     *
     */
    test('it should have the correct config setup', () => {
        expect(global.dataLayer.length).toEqual(2);
        expect(global.dataLayer[1][0]).toBe('config');
        expect(global.dataLayer[1][1]).toBe('UA-12345');

        expect(global.dataLayer[1][2].linker).not.toBeUndefined();
        expect(global.dataLayer[1][2].linker.domains.length).toBe(1);
        expect(global.dataLayer[1][2].linker.domains[0]).toBe('www.gov.uk');

        expect(global.dataLayer[1][2].anonymize_ip).not.toBeUndefined();
        expect(global.dataLayer[1][2].anonymize_ip).toBe(true);

        expect(global.dataLayer[1][2].transport_type).not.toBeUndefined();
        expect(global.dataLayer[1][2].transport_type).toBe('beacon');

        expect(global.dataLayer[1][2].allow_google_signals).not.toBeUndefined();
        expect(global.dataLayer[1][2].allow_google_signals).toBe(false);

        expect(global.dataLayer[1][2].allow_ad_personalization_signals).not.toBeUndefined();
        expect(global.dataLayer[1][2].allow_ad_personalization_signals).toBe(false);
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
        useAnalytics.trackEvent('event', 'event_category', 'event_label', 'value')

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
        useAnalytics.trackEvent('test@test.com', '01234567890', 'NG156WL', '28/06/1984')
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
        console.log(global.dataLayer)
        expect(global.dataLayer[1][2].page_title).toBe('Test Page Title');
        expect(global.dataLayer[1][2].page_path).toBe('https://localhost/use-lpa');
    });
});
