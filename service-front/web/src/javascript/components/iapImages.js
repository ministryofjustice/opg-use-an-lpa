export default class IapImages extends HTMLElement {
    connectedCallback() {
        const isWait = this.getAttribute('data-wait')

        if (isWait !== null) {
            this.token = isWait
            this._runInterval()
        }
    }

    _runInterval() {
        const _this = this
        /* istanbul ignore next */
        this.interval = setInterval(async () => {
            const json = await _this._getImagesData()

            if (json.status !== 'COLLECTION_IN_PROGRESS') {
                clearInterval(_this.interval)

                _this._updateComponents(
                    json.status === 'COLLECTION_ERROR',
                    json.signedUrls
                )
            }
        }, 5000)
    }

    async _getImagesData() {
        const response = await fetch(
            "/lpa/view-lpa/images?" + new URLSearchParams({
                lpa: this.token
            }),
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json'
                },
            }
        )

        if (!response.ok) {
            throw new Error(`HTTP error when fetching images status: ${response.status}`)
        }

        return response.json()
    }

    _updateComponents(hasError, signedUrls) {
        hasError
            ? this._displayError()
            : this._displayImages(signedUrls)
    }

    _displayError() {
        const insts = this.querySelector('iap-instructions')
        if (insts !== null) {
            insts.inError = true
        }

        const prefs = this.querySelector('iap-preferences')
        if (prefs !== null) {
            prefs.inError = true
        }

        const guidance = this.querySelector('#images-guidance')
        if (guidance !== null) {
            guidance.remove()
        }
    }

    _displayImages(signedUrls) {
       const insts = this.querySelector('iap-instructions')
        if (signedUrls.instructions.length > 0 && insts !== null) {
            insts.images = signedUrls.instructions
        }

        const prefs = this.querySelector('iap-preferences')
        if (signedUrls.preferences.length > 0 && prefs !== null) {
            prefs.images = signedUrls.preferences
        }

        const unknown = this.querySelector('iap-unknown')
        if (signedUrls.unknown.length > 0 && unknown !== null) {
            unknown.images = signedUrls.unknown
        }
    }
}
