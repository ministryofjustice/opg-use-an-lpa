import IapImages from './iapImages.js'

describe('it provides progressive enhancement to iap images', () => {
    beforeAll(() => {
        customElements.define('iap-images', IapImages)
    })

    beforeEach(() => {
        jest.clearAllTimers()
        jest.useFakeTimers()

        delete global.fetch
        global.fetch = jest.fn(() => Promise.resolve({
            ok: true,
            json: async () => Promise.resolve({
                'status': 'COLLECTION_IN_PROGRESS'
            })
        }))

        jest.spyOn(global, 'setInterval')
        jest.spyOn(global, 'clearInterval')
    })

    test('it does not do anything unless a wait value is provided', () => {
        document.body.innerHTML = `<iap-images></iap-images>`

        expect(setInterval).toHaveBeenCalledTimes(0)
    })

    test('it begins checking for updates when an update token is provided', () => {
        document.body.innerHTML = `<iap-images data-wait="token"></iap-images>`

        expect(setInterval).toHaveBeenCalledTimes(1)
        expect(setInterval).toHaveBeenLastCalledWith(expect.any(Function), 5000)
    })

    test('it fetches from the api to check for images updates', async () => {
        document.body.innerHTML = `<iap-images data-wait="token"></iap-images>`

        await jest.runOnlyPendingTimersAsync()

        expect(fetch).toHaveBeenCalledTimes(1)
        expect(fetch).toHaveBeenCalledWith(
            '/lpa/view-lpa/images?lpa=token',
            {"headers": {"Accept": "application/json"}, "method": "GET"}
        )
    })

    test('it stops checking when it receives a non-in-progress response', async () => {
        document.body.innerHTML = `<iap-images data-wait="token"></iap-images>`

        fetch.mockImplementationOnce(() => Promise.resolve({
            ok: true,
            json: async () => Promise.resolve({
                'status': 'COLLECTION_COMPLETE',
                'signedUrls': {
                    'instructions': [],
                    'preferences': [],
                    'unknown': [],
                },
            })
        }))

        await jest.runOnlyPendingTimersAsync()

        expect(clearInterval).toHaveBeenCalledTimes(1)
    })
})
