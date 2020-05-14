/* istanbul ignore file */

import './scss.js';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';
import disableButtonOnClick from './javascript/disableButtonOnClick';
import copyAccessCode from './javascript/copyAccessCode';
import cookieConsent from './javascript/cookieConsent';

require('../')

initAll();

jsEnabled(document.body);
disableButtonOnClick(document.getElementsByTagName('form'));
copyAccessCode(document.getElementById("copybutton"));
cookieConsent(document.getElementsByClassName('.global-cookie-message')[0]);
