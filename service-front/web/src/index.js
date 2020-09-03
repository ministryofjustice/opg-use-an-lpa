/* istanbul ignore file */
import './scss.js';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';
import disableButtonOnClick from './javascript/disableButtonOnClick';
import copyAccessCode from './javascript/copyAccessCode';
import cookieConsent from './javascript/cookieConsent';
import sessionDialog from './javascript/sessionDialog';
initAll();
jsEnabled(document.body);
disableButtonOnClick(document.getElementsByTagName('form'));
new cookieConsent(document.getElementsByClassName('cookie-banner')[0], window.location.pathname === '/cookies');
copyAccessCode();
// new sessionDialog(document.getElementById("dialog"), 20); // TODO: Disabled for now until we know how we are going to trigger it
