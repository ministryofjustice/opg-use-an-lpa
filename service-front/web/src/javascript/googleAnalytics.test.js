/**
 * @jest-environment jsdom
 * @jest-environment-options {"url": "https://localhost/use-lpa?email=email@test.com"}
 */

import googleAnalytics from './googleAnalytics';

describe('given Google Analytics is enabled', () => {
    let useAnalytics;
    beforeEach(() => {
        document = document.documentElement;
        document.title = 'Test Page Title';

        global.dataLayer = [];
        useAnalytics = new googleAnalytics('UA-12345');
    });

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
