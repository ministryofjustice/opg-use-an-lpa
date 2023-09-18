/* istanbul ignore file */

import IapContainer from "./iapContainer.js";

export default class IapUnknown extends IapContainer {
    displayImages(signedUrls) {
        const imgsTmpl = document.getElementById('iap-unknown-section').content
        this.replaceChildren(imgsTmpl.firstElementChild.cloneNode(true))

        super.displayImages(signedUrls)
    }
}
