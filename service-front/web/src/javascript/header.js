import { CrossServiceHeader } from 'govuk-one-login-service-header/dist/scripts/service-header.js';

const initGovUKHeader = ($selector) => {
    if ($selector) {
        new CrossServiceHeader($selector).init();
    }
}

export default initGovUKHeader;