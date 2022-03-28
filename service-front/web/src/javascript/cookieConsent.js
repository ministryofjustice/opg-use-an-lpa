import { getCookie, setConsentCookie } from './cookieHelper';
import GoogleAnalytics from './googleAnalytics';
import AnalyticsTracking from './analyticsTracking';
import AnalyticsPerformance from './analyticsPerformance';

export default class CookieConsent {
  constructor(bannerElement, isInCookiesPath) {
    this.bannerElement = bannerElement;
    const cookiePolicy = getCookie('cookie_policy');
    let isAnalyticsCookieSet = cookiePolicy !== null;

    this._toggleCookieMessage(!isAnalyticsCookieSet && !isInCookiesPath);

    if (isInCookiesPath) { 
      setConsentCookie(false);
    }

    if (cookiePolicy) {
      if (JSON.parse(cookiePolicy).usage) {
        this._setupAnalytics();
      }
    }
  }

  _setCookieBannerClickEvents() {
    var cookieBanner = document.getElementsByClassName(
      'govuk-cookie-banner',
    )[0];
    cookieBanner.addEventListener('click', (e) => {
      if (e.target && e.target.name === 'cookies') {
        if (e.target.value === 'accept') {
          this._toggleCookieMessage(false);
          setConsentCookie(true);
        }
        if (e.target.value === 'reject') {
          this._toggleCookieMessage(false);
          setConsentCookie(false);
        }
      }
    });
  }

  _toggleCookieMessage(show) {
    var cookieBanner = document.getElementsByClassName(
      'govuk-cookie-banner',
    )[0];
    cookieBanner.toggleAttribute('hidden', !show);
    if (show) {
      this._setCookieBannerClickEvents();
    }
  }

  _setupAnalytics() {
    window.useAnalytics = new GoogleAnalytics(window.gaConfig.uaId);
    window.analyticsTracking = new AnalyticsTracking();
    window.analyticsPerformance = new AnalyticsPerformance();
  }
}