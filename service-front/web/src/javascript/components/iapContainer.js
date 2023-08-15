export default class IapContainer extends HTMLElement {
    displayError() {
        const errTmpl = document.getElementById(this.ERROR_TEMPLATE).content

        const spinner = this.querySelector('.iap-wait')

        if (spinner !== null) {
            spinner?.remove()
            this.appendChild(errTmpl.firstElementChild.cloneNode(true))
        }
    }

    displayImages(signedUrls)
    {
        const imgsTmpl = document.getElementById('iap-images-container').content
        const imgsDiv = imgsTmpl.firstElementChild.cloneNode(true)
        const linkTmpl = document.getElementById('iap-img').content

        signedUrls.forEach(url => {
            let img = linkTmpl.firstElementChild.cloneNode(true)
            img.setAttribute('src', url.url)

            imgsDiv.appendChild(img)
        })

        this.querySelector('.iap-wait')?.remove()
        this.appendChild(imgsDiv)
    }
}
