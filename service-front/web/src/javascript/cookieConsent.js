import {getCookie, setCookie, setDefaultConsentCookie, approveAllCookieTypes} from './cookieHelper';
import googleAnalytics from "./googleAnalytics";

export default class CookieConsent {
    constructor(bannerElement)
    {
        this.bannerElement = bannerElement
        const cookiePolicy = getCookie('cookie_policy')
        const seenCookieMessage = getCookie('seen_cookie_message')
        if (seenCookieMessage !== "true") {
            if (!this._isInCookiesPage()) {
                this._toggleCookieMessage(true)
            }
            cookiePolicy || setDefaultConsentCookie()
        }

        const acceptButton = bannerElement.querySelector('.cookie-banner__button-accept > button')
        this._bindEnableAllButton(acceptButton)

        //to remove later
        window.useAnalytics = new googleAnalytics(window.gaConfig.uaId);
        window.useAnalytics.trackEvents("actionValue", "categoryValue","labelValue", "value");
    }

    _bindEnableAllButton(element)
    {
        this._enableAllCookies = this._enableAllCookies.bind(this);
        element.addEventListener('click', this._enableAllCookies)
    }

    _enableAllCookies(event)
    {
        approveAllCookieTypes()
        setCookie('seen_cookie_message', 'true')

        this._toggleCookieMessage(false);

        new googleAnalytics(window.gaConfig.uaId);
    }

    _toggleCookieMessage(show)
    {
        this.bannerElement.classList.toggle('cookie-banner--show', show)
    }

    _isInCookiesPage()
    {
        return '/cookies' === window.location.pathname
    }
}