import cookieConsent from './cookieConsent';
import '@testing-library/jest-dom'

describe('disableButtonOnClick', () => {
  // jsdom doesn't implement form submission so this will keep it from showing errors in the console
  window.HTMLFormElement.prototype.submit = () => { };

  describe('When no cookie is set', () => {
    test('clicking button will not disable it', () => {
      document.body.innerHTML = `
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

      cookieConsent(document.querySelector('.global-cookie-message'));

      const cookieBanner = document.querySelector('.global-cookie-message');
      expect(cookieBanner).toHaveClass('global-cookie-message__show');

      button.click();
      expect(button.disabled).toBe(false);
    });
  });

  describe('with the attribute', () => {
    test('clicking button will disable it', () => {
      document.body.innerHTML = `
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

      cookieConsent(document.getElementsByClassName('global-cookie-message')[0]);

      const button = document.querySelector('button');
      expect(button.disabled).toBe(false);

      button.click();
      expect(button.disabled).toBe(true);
    });
  });
});
