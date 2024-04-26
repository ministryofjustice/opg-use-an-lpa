/* istanbul ignore file */
require('es6-promise/auto');
import jsEnabled from './javascript/jsEnabled';
import {Accordion, initAll} from 'govuk-frontend';
import disableButtonOnClick from './javascript/disableButtonOnClick';
import copyAccessCode from './javascript/copyAccessCode';
import cookieConsent from './javascript/cookieConsent';
import sessionDialog from './javascript/sessionDialog';
import showHidePassword from './javascript/showHidePassword';
import addPolyFill from './javascript/polyfill.js';
import registerIapImagesComponents from './javascript/iapImages.js';

//always run this first as GDS FE dependent on it.
jsEnabled(document.body);

Accordion.prototype.updateOpenAllButton = function (expanded) {
    var newButtonText = expanded
        ? this.$module.dataset.closetext
        : this.$module.dataset.opentext;
    newButtonText += `<span class="govuk-visually-hidden"> ${this.$module.dataset.sectiontext}</span>`;
    this.$openAllButton.setAttribute('aria-expanded', expanded);
    this.$openAllButton.innerHTML = newButtonText;
};

initAll();

addPolyFill();
disableButtonOnClick(document.getElementsByTagName('form'));
new cookieConsent(
    document.getElementsByClassName('cookie-banner')[0],
    window.location.pathname === '/cookies',
);
copyAccessCode();
showHidePassword();
registerIapImagesComponents();

if (
    document.getElementsByClassName('js-signed-in').length > 0 &&
    document.getElementById('dialog') !== null
) {
    new sessionDialog(document.getElementById('dialog'));
}