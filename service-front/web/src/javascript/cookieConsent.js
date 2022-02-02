import { getCookie, setCookie, setDefaultConsentCookie, approveAllCookieTypes } from './cookieHelper';
import GoogleAnalytics from "./googleAnalytics";
import AnalyticsTracking from "./analyticsTracking";

export default class CookieConsent {
    constructor(bannerElement, isInCookiesPath) {
        this.bannerElement = bannerElement;
        const cookiePolicy = getCookie('cookie_policy');
        const seenCookieMessage = getCookie('seen_cookie_message');
        if (seenCookieMessage !== "true") {
            if (!isInCookiesPath) {
                this._toggleCookieMessage(true);
            }
            cookiePolicy || setDefaultConsentCookie();
        }

        const acceptButton = bannerElement.querySelector('.cookie-banner__button-accept > button');
        this._bindEnableAllButton(acceptButton);

        if (cookiePolicy) {
            if (JSON.parse(cookiePolicy).usage) {
                window.useAnalytics = new GoogleAnalytics(window.gaConfig.uaId);
                window.analyticsTracking = new AnalyticsTracking();
            }
        }
    }

    _bindEnableAllButton(element) {
        this._enableAllCookies = this._enableAllCookies.bind(this);
        element.addEventListener('click', this._enableAllCookies);
    }

    _enableAllCookies(event) {
        approveAllCookieTypes();
        setCookie('seen_cookie_message', 'true');

        this._toggleCookieMessage(false);
    }

    _toggleCookieMessage(show) {
        this.bannerElement.classList.toggle('cookie-banner--show', show);
    }
}
