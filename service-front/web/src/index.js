/* istanbul ignore file */

import './scss.js';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';
import disableButtonOnClick from './javascript/disableButtonOnClick';
import copyAccessCode from './javascript/copyAccessCode';

initAll();

jsEnabled(document.body);
disableButtonOnClick(document.getElementsByTagName('form'));
copyAccessCode(document.getElementById("copybutton"));
