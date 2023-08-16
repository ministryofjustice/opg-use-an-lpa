import IapContainer from "./iapContainer.js"

const stubElement = class extends IapContainer {
    ERROR_TEMPLATE='iap-stub-error'
}

const html = `
    <template id="iap-stub-error">
        <div>This is an error message</div>
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

        sut.displayError()

        expect(sut.innerHTML).toContain('<div>This is an error message</div>')
        expect(sut.innerHTML).not.toContain('<div class="govuk-warning-text iap-wait">')
    })

    test('it wont show errors if there is no spinner', () => {
        document.querySelector('iap-test .iap-wait').remove()

        let sut = document.body.querySelector('iap-test')
        sut.displayError()

        expect(sut.innerHTML).not.toContain('<div>This is an error message</div>')
    })
})
