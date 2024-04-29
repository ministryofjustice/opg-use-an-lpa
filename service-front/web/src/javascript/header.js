import { CrossServiceHeader } from './vendor/service-header.js';

const initGovUKHeader = ($selector) => {
    if ($selector) {
        new CrossServiceHeader($selector).init();
    }
}

export default initGovUKHeader;