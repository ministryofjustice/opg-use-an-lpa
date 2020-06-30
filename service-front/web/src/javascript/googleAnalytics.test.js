import googleAnalytics from './googleAnalytics';

describe('given Google Analytics is enabled', () => {
    let useAnalytics;
    beforeEach(() => {
        global.dataLayer = [];
        useAnalytics = new googleAnalytics('UA-12345');
    });
    test('it should have the correct config setup', () => {
        expect(global.dataLayer.length).toEqual(2);
        expect(global.dataLayer[1][0]).toBe('config');
        expect(global.dataLayer[1][1]).toBe('UA-12345');

        expect(global.dataLayer[1][2].linker).not.toBeUndefined();
        expect(global.dataLayer[1][2].linker.domains.length).toBe(1);
        expect(global.dataLayer[1][2].linker.domains[0]).toBe('www.gov.uk');

        expect(global.dataLayer[1][2].anonymize_ip).not.toBeUndefined();
        expect(global.dataLayer[1][2].anonymize_ip).toBe(true);

        expect(global.dataLayer[1][2].allow_google_signals).not.toBeUndefined();
        expect(global.dataLayer[1][2].allow_google_signals).toBe(false);

        expect(global.dataLayer[1][2].allow_ad_personalization_signals).not.toBeUndefined();
        expect(global.dataLayer[1][2].allow_ad_personalization_signals).toBe(false);
    });

    test('it should fire events correctly', () => {
        useAnalytics.trackEvent('event','event_category','event_label','value')
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

    test('it should sanitize the data being sent', () => {
        useAnalytics.trackEvent('test@test.com','01234567890','NG156WL','28/06/1984')
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
});
