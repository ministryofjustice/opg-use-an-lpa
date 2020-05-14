import cookieConsent from './cookieConsent';
import '@testing-library/jest-dom'
import { getCookie } from './cookieHelper';

const cookieBannerHtml = `
<div class="global-cookie-message" role="region" aria-label="cookie banner">
  <p class="global-cookie-message__text">
      GOV.UK uses cookies which are essential for the site to work. We also use non-essential cookies to help us
      improve government digital services. Any data collected is anonymised.
  </p>
  <div class="global-cookie-message__buttons">
      <div>
          <button class="global-cookie-message__button global-cookie-message__button_accept" type="submit">Accept all cookies</button>
          <a class="global-cookie-message__button-link global-cookie-message__button-link_settings" role="button" href="/cookies">Cookie settings</a>
      </div>
  </div>
</div>
`;

jest.mock("./cookieHelper", () => ({
  getCookie: jest.fn(),
}));

describe('When the cookie banner is initiated', () => {
  describe('When the cookie is null', () => {
    getCookie.mockReturnValueOnce(null)
    test('it should show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      cookieConsent(document.querySelector('.global-cookie-message'));
      const cookieBanner = document.querySelector('.global-cookie-message');
      expect(cookieBanner).toHaveClass('global-cookie-message__show');
    });
  });

  describe('When the cookie is set to true', () => {
    getCookie.mockReturnValueOnce(true)
    test('it should not show the banner', () => {
      document.body.innerHTML = cookieBannerHtml;
      cookieConsent(document.getElementsByClassName('global-cookie-message')[0]);
      const cookieBanner = document.querySelector('.global-cookie-message');
      expect(cookieBanner).not.toHaveClass('global-cookie-message__show');
    });
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
});
