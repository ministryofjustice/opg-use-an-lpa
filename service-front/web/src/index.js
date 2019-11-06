/* istanbul ignore file */

import './main.scss';
import './scss/lpa.scss';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';

initAll();
jsEnabled(document.body);
