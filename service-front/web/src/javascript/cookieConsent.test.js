import cookieConsent from './cookieConsent';
import '@testing-library/jest-dom';
import { getCookie, setConsentCookie } from './cookieHelper';
const cookieBannerHtml = `
<div class="govuk-cookie-banner " data-nosnippet="" role="region" aria-label="Cookies on Use a lasting power of attorney">
        <div class="govuk-cookie-banner__message govuk-width-container">
            <div class="govuk-grid-row">
                <div class="govuk-grid-column-two-thirds">
                    <h2 class="govuk-cookie-banner__heading govuk-heading-m">Cookies on Use a lasting power of attorney</h2>

                    <div class="govuk-cookie-banner__content">
                        <p class="govuk-body">We use some essential cookies to make this service work.</p>
                        <p class="govuk-body">We’d also like to use analytics cookies so we can understand how you use the service and make improvements.</p>
                    </div>
                </div>
            </div>
            <div class="govuk-button-group">
                <button value="accept" type="button" name="cookies" class="govuk-button" data-module="govuk-button">
                    Accept analytics cookies
                </button>
                <button value="reject" type="button" name="cookies" class="govuk-button" data-module="govuk-button">
                    Reject analytics cookies
                </button>
                <a class="govuk-link" href="/cookies">View cookies</a>
            </div>
        </div>
    </div>
`;
jest.mock('./cookieHelper', () => ({
  getCookie: jest.fn(),
  setCookie: jest.fn(),
  setConsentCookie: jest.fn(),
}));

describe('When the cookie banner is initiated', () => {
  describe('and there is no cookie set', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(cookieBanner.getAttribute('hidden')).toBe(null);
    });
  });
  describe('and the accept cookies have been set to true', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce('true');
      getCookie.mockReturnValueOnce('true');
    });
    test('it should not show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(cookieBanner.getAttribute('hidden')).not.toBe(null);
    });
  });
  describe('and the accept button has been clicked', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should hide the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(cookieBanner.getAttribute('hidden')).toBe(null);
      const button = document.querySelector("button[value='accept']");
      button.click();
      expect(cookieBanner.getAttribute('hidden')).not.toBe(null);
      expect(setConsentCookie).toHaveBeenCalledWith(true);
    });
  });
  describe('and the reject button has been clicked', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should hide the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(cookieBanner.getAttribute('hidden')).toBe(null);
      const button = document.querySelector("button[value='reject']");
      button.click();
      expect(cookieBanner.getAttribute('hidden')).not.toBe(null);
      expect(setConsentCookie).toHaveBeenCalledWith(false);
    });
  });
  describe('and the no button has been clicked', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('nothing happens', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(cookieBanner.getAttribute('hidden')).toBe(null);
      cookieBanner.click();
      expect(cookieBanner.getAttribute('hidden')).toBe(null);
      expect(setConsentCookie).not.toHaveBeenCalled();
    });
  });
  describe('and the usage option is set to true', () => {
    beforeEach(() => {
      window.gaConfig = {
        uaID: 'UA-12345',
      };
      getCookie.mockReturnValueOnce('{ "essential": true, "usage": true }');
      getCookie.mockReturnValueOnce('true');
    });
    test('it should setup useAnaltics', () => {
      expect(window.useAnalytics).toBeUndefined();
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'));
      expect(window.useAnalytics).not.toBeUndefined();
    });
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
});

describe('When the cookie banner is initiated on the cookies page', () => {
  describe('and I am on the cookies page but have not seen the message', () => {
    test('it should setup useAnaltics', () => {

      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);

      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'), true);
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(setConsentCookie).toHaveBeenCalledWith(false);
      expect(cookieBanner.getAttribute('hidden')).not.toBe(null);
    });
    test('When analytics is already set up nothing is called', () => {

      getCookie.mockReturnValueOnce('{ "essential": true, "usage": true }');
      getCookie.mockReturnValueOnce('{ "essential": true, "usage": true }');

      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.govuk-cookie-banner'), true);
      const cookieBanner = document.querySelector('.govuk-cookie-banner');

      expect(setConsentCookie).not.toHaveBeenCalled();
      expect(cookieBanner.getAttribute('hidden')).not.toBe(null);
    });
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
});
