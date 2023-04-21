import googleAnalytics from './googleAnalytics';

describe('given Google Analytics is enabled', () => {
    const oldWindowLocation = window.location;

    let useAnalytics;
    beforeEach(() => {
        document = document.documentElement;
        document.title = 'Test Page Title';
        delete window.location;
        window.location = {
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
    afterAll(() => {
        // restore `window.location` to the original `jsdom`
        // `Location` object
        window.location = oldWindowLocation
    })

    test('it should have the correct config setup', () => {
        expect(global.dataLayer).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    '0': 'config',
                    '1': 'UA-12345',
                    '2': {
                        'linker': { 'domains': ['www.gov.uk'] },
                        'cookie_flags': 'SameSite=Strict;Secure',
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
