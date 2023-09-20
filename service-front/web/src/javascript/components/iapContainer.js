export default class IapContainer extends HTMLElement {
    constructor() {
        super()

        this._images = []
        this._inError = false
    }

    connectedCallback() {
        this._displayWait()
    }

    set images(images) {
        let old = this._images
        this._images = images

        if (old !== this._images) {
            this._displayImages()
        }
    }

    set inError(inError) {
        this._inError = inError

        if (this._inError) {
            this._displayError()
        }
    }

    _displayError() {
        const errTmpl = document.getElementById(this.ERROR_TEMPLATE).content

        const spinner = this.querySelector('.iap-wait')

        if (spinner !== null) {
            spinner?.remove()
            this.appendChild(errTmpl.firstElementChild.cloneNode(true))
        }
    }

    _displayImages() {
        const imgsTmpl = document.getElementById('iap-images-container').content
        const imgsDiv = imgsTmpl.firstElementChild.cloneNode(true)
        const linkTmpl = document.getElementById('iap-img').content

        this._images.forEach(url => {
            let img = linkTmpl.firstElementChild.cloneNode(true)
            img.setAttribute('src', url.url)

            imgsDiv.appendChild(img)
        })

        this.querySelector('.iap-wait')?.remove()
        this.appendChild(imgsDiv)
    }

    _displayWait() {
        const waitTmpl = document.getElementById(this.WAIT_TEMPLATE).content

        const spinner = this.querySelector('.iap-wait')

        if (spinner !== null) {
            spinner?.remove()
            this.appendChild(waitTmpl.firstElementChild.cloneNode(true))
        }
    }
}
