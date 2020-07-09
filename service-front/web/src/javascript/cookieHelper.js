const DEFAULT_COOKIE_CONSENT = {
    'essential': true,
    'usage': false
}

const getCookie = name => {
    const nameEQ = name + '=';
    const cookies = document.cookie.split(';');
    for (let i = 0, len = cookies.length; i < len; i++) {
        let cookie = cookies[i];
        while (cookie.charAt(0) === ' ') {
            cookie = cookie.substring(1, cookie.length);
        }
        if (cookie.indexOf(nameEQ) === 0) {
            return decodeURIComponent(cookie.substring(nameEQ.length));
        }
    }
    return null;
}

const createCookie = (name, value, options) => {
    // Default expiry date of 30 days
    if (typeof options === 'undefined' || options.days === undefined) {
        options = { days: 30 }
    }

    let cookieString = `${name}=${encodeURIComponent(value)}; path=/`;
    const date = new Date(Date.now());
    date.setTime(date.getTime() + (options.days * 24 * 60 * 60 * 1000));
    cookieString = `${cookieString}; expires=${date.toGMTString()}`;

    /* istanbul ignore next */
    if (window.location.protocol === 'https:') {
        cookieString = `${cookieString}; Secure`;
    }

    return cookieString;
}

const setCookie = (name, value, options) => {
    document.cookie = createCookie(name, value, options);
}

const approveAllCookieTypes = () => {
    const approvedConsent = {
        'essential': true,
        'usage': true
    }

    setCookie('cookie_policy', JSON.stringify(approvedConsent), { days: 365 });
}

const setDefaultConsentCookie = () => {
    setCookie('cookie_policy', JSON.stringify(DEFAULT_COOKIE_CONSENT), { days: 365 });
}

export { getCookie, setCookie, setDefaultConsentCookie, approveAllCookieTypes, createCookie };
