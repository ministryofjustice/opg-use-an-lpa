import { getCookie } from './cookieHelper';

const CookieConsent = element => {
  const cookiePolicy = getCookie('cookie_policy');

  if (cookiePolicy === null) {
    toggleCookieMessage(element, true);
  } else {
    toggleCookieMessage(element, false);
    if (cookiePolicy === true) {
      loadAnalytics();
    }
  }
};

const toggleCookieMessage = (element, show) => {
  element.classList.toggle('global-cookie-message__show', show);
}

const loadAnalytics = () => {
  return true;
}

export default CookieConsent;
