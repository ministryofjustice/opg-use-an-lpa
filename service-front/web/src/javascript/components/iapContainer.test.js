import {expect} from '@jest/globals';
import IapContainer from "./iapContainer.js"

const stubElement = class extends IapContainer {
    ERROR_TEMPLATE='iap-stub-error'
    WAIT_TEMPLATE='iap-stub-wait'
}

const html = `
    <template id="iap-img">
        <img class="opg-ip__image" src="" alt="A scanned copy of the donor’s preferences and/or instructions - the text cannot be digitised at present">
    </template>
    <template id="iap-images-container">
        <div class="opg-ip"></div>
    </template>
    <template id="iap-stub-error">
        <div class="govuk-warning-text">
            <span class="govuk-warning-text__icon" aria-hidden="true">!</span>
            <strong class="govuk-warning-text__text">
                <span class="govuk-warning-text__assistive">{% trans %}Warning{% endtrans %}</span>
                This is an error message
            </strong>
        </div>
    </template>
    <template id="iap-stub-wait">
        <div class="govuk-warning-text iap-wait">
            <strong class="govuk-warning-text__text">
                <div class="iap-loader"></div>
                <span class="govuk-warning-text__assistive">Warning</span>
                A scanned image of the donor’s instructions will appear here soon. The first time may take up to 10 minutes.
                You do not need to stay on the page or refresh it whilst you wait.
            </strong>
        </div>
    </template>
    <iap-test>
        <dl class="govuk-summary-list govuk-summary-list--no-border">
            <div class="govuk-summary-list__row">
                <dt class="govuk-summary-list__key">Instructions</dt>
                <dd class="govuk-summary-list__value">Yes, the donor set instructions on their LPA.</dd>
            </div>
        </dl>

        <div class="govuk-warning-text iap-wait">
            <strong class="govuk-warning-text__text">
                <div class="iap-loader"></div>
                <span class="govuk-warning-text__assistive">Warning</span>
                A scanned image of the donor’s instructions will appear here soon. The first time may
                take up to 10 minutes. Please refresh this page.
            </strong>
        </div>
    </iap-test>`

describe('it provides shared methods for inheriting classes', () => {
    beforeAll(() => {
        customElements.define('iap-test', stubElement)
    })

    beforeEach(() => {
        document.body.innerHTML = html
    })

    test('it can be told to display errors', () => {
        let sut = document.body.querySelector('iap-test')

        sut.inError = true

        expect(sut.innerHTML).toEqual(expect.stringContaining('This is an error message'))
        expect(sut.innerHTML).not.toEqual(expect.stringContaining('/<div class="govuk-warning-text iap-wait">/'))
    })

    test('it wont show errors if there is no spinner', () => {
        document.querySelector('iap-test .iap-wait').remove()

        let sut = document.body.querySelector('iap-test')
        sut.inError = true

        expect(sut.innerHTML).not.toEqual(expect.stringContaining('This is an error message'))
    })

    test('it will update with images when provided', () => {
        let sut = document.body.querySelector('iap-test')

        sut.images = [
            {name: 'image-name', url: 'image-url'},
            {name: 'image-name-continuation', url: 'image-url-continuation'}
        ]

        expect(sut.innerHTML).not.toEqual(expect.stringContaining('/<div class="govuk-warning-text iap-wait">/'))
        expect(sut.innerHTML).toEqual(expect.stringContaining('<div class="opg-ip">'))
        expect(sut.innerHTML).toEqual(expect.stringContaining('<img class="opg-ip__image"'))
    })

    test('it will update the static wait message', () => {
        let sut = document.body.querySelector('iap-test')

        expect(sut.innerHTML).toEqual(expect.stringContaining('You do not need to stay on the page or refresh it whilst you wait'))
        expect(sut.innerHTML).not.toEqual(expect.stringContaining('Please refresh this page'))
    })
})
