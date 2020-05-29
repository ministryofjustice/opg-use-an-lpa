import {getCookie, setCookie, setDefaultConsentCookie, approveAllCookieTypes} from './cookieHelper';

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
        console.log(event);

        this._toggleCookieMessage(false)

         // TO DO - enable analytics and fire off a pageview
         //window.GOVUK.analyticsSetup(window)
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