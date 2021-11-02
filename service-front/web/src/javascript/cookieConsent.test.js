import cookieConsent from './cookieConsent';
import '@testing-library/jest-dom'
import { getCookie } from './cookieHelper';
const cookieBannerHtml = `
<div class="cookie-banner govuk-width-container" role="region" aria-label="cookie banner">
    <div class="govuk-grid-row">
        <div class=" govuk-grid-column-two-thirds">
            <div class="cookie-banner__message">
                <h2 class="govuk-heading-m">Tell us whether you accept cookies</h2>
                <p class="govuk-body">GOV.UK uses cookies which are essential for the site to work. We also use non-essential cookies to help us
                    improve government digital services. Any data collected is anonymised.</p>
            </div>
            <div class="cookie-banner__buttons">
                <div class="cookie-banner__button cookie-banner__button-accept govuk-grid-column-full govuk-grid-column-one-half-from-desktop govuk-!-padding-left-0">
                    <button class="govuk-button button--inline" type="submit" role="button">Accept all cookies</button>
                </div>
                <div class="cookie-banner__button govuk-grid-column-full govuk-grid-column-one-half-from-desktop govuk-!-padding-left-0">
                    <a href="/cookies" class="govuk-button govuk-button--secondary button--inline" role="button">Set cookie preferences</a>
                </div>
            </div>
        </div>
    </div>
</div>
`;
jest.mock("./cookieHelper", () => ({
  getCookie: jest.fn(),
  setDefaultConsentCookie: jest.fn(),
  approveAllCookieTypes: jest.fn(),
  setCookie: jest.fn()
}));
describe('When the cookie banner is initiated', () => {
  describe('and there is no cookie set', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.querySelector('.cookie-banner'));
      const cookieBanner = document.querySelector('.cookie-banner');
      expect(cookieBanner).toHaveClass('cookie-banner--show');
    });
  });
  describe('and the accept cookies have been set to true', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce('true');
      getCookie.mockReturnValueOnce('true');
    });
    test('it should not show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.getElementsByClassName('cookie-banner')[0], false);
      const cookieBanner = document.querySelector('.cookie-banner');
      expect(cookieBanner).not.toHaveClass('cookie-banner--show');
    });
  });
  describe('and the accept all button has been clicked', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should hide the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.getElementsByClassName('cookie-banner')[0], false);
      const cookieBanner = document.querySelector('.cookie-banner');
      expect(cookieBanner).toHaveClass('cookie-banner--show');
      const button = document.querySelector('button');
      button.click();
      expect(cookieBanner).not.toHaveClass('cookie-banner--show');
    });
  });
  describe('and the usage option is set to true', () => {
    beforeEach(() => {
      window.gaConfig = {
        uaID: 'UA-12345'
      }
      getCookie.mockReturnValueOnce('{ "essential": true, "usage": true }');
      getCookie.mockReturnValueOnce('true');
    });
    test('it should setup useAnaltics', () => {
      expect(window.useAnalytics).toBeUndefined();
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.getElementsByClassName('cookie-banner')[0], false);
      expect(window.useAnalytics).not.toBeUndefined();
    });
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
});


describe('When the cookie banner is initiated on the cookies page', () => {
  describe('and I am on the cookies page but have not seen the message', () => {
    beforeEach(() => {
      getCookie.mockReturnValueOnce(null);
      getCookie.mockReturnValueOnce(null);
    });
    test('it should setup useAnaltics', () => {
      document.body.innerHTML = cookieBannerHtml;
      new cookieConsent(document.getElementsByClassName('cookie-banner')[0], true);
      const cookieBanner = document.querySelector('.cookie-banner');
      expect(cookieBanner).not.toHaveClass('cookie-banner--show');
    });
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
})
