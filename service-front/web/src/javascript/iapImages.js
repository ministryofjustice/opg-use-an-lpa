/* istanbul ignore file */

import iapImages from "./components/iapImages.js"
import iapInstructions from "./components/iapInstructions.js"
import iapPreferences from "./components/iapPreferences.js"
import iapUnknown from "./components/iapUnknown.js"

const registerIapImageComponents = () => {
    customElements.define('iap-images', iapImages)
    customElements.define('iap-instructions', iapInstructions)
    customElements.define('iap-preferences', iapPreferences)
    customElements.define('iap-unknown', iapUnknown)
}

export default registerIapImageComponents
