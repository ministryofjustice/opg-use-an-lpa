/* istanbul ignore file */

import './scss.js';
import { initAll } from 'govuk-frontend';
import jsEnabled from './javascript/jsEnabled';

initAll();

jsEnabled(document.body);

var btn = document.getElementsByClassName('govuk-button');
debugger;
for (var i = 0; i < btn.length; i++) {

    btn[i].addEventListener('click', function disableButton() {
        debugger;
        this.disabled = true;

    })
}





