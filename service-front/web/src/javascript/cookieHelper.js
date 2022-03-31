const DEFAULT_COOKIE_CONSENT = {
    'essential': true,
    'usage': false
}

const APPROVED_COOKIE_CONSENT = {
    'essential': true,
    'usage': true
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

const setCookie = (name, value, options) => {
    // Default expiry date of 30 days
    if (typeof options === 'undefined' || options.days === undefined) {
        options = { days: 30 }
    }

    const maxAge = options.days * 24 * 60 * 60;
    const cookie =`${name}=${encodeURIComponent(value)}` + "; path=/" + "; max-age=" + maxAge + "; Secure";
    document.cookie = cookie;
    return cookie;
}

const setConsentCookie = (accept) => {
    setCookie('cookie_policy', JSON.stringify(accept ? APPROVED_COOKIE_CONSENT : DEFAULT_COOKIE_CONSENT), { days: 365 });
}

export { getCookie, setCookie, setConsentCookie };
