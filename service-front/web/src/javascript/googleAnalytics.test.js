import googleAnalytics from './googleAnalytics';

describe('given Google Analytics is enabled', () => {
    let useAnalytics;
    beforeEach(() => {
        document = document.documentElement;
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
        useAnalytics = new googleAnalytics('UA-12345');
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

});
