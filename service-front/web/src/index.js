/* istanbul ignore file */

import './scss.js';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';
import disableButtonOnClick from './javascript/disableButtonOnClick';
import copyAccessCode from './javascript/copyAccessCode';
import cookieConsent from './javascript/cookieConsent';

initAll();

jsEnabled(document.body);
disableButtonOnClick(document.getElementsByTagName('form'));
new cookieConsent(document.getElementsByClassName('cookie-banner')[0]);
copyAccessCode(document.getElementById("copybutton"));

