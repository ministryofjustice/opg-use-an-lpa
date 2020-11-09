import sessionDialog from './sessionDialog';
import { fireEvent } from "@testing-library/dom";

const html = `
    <div id="dialog-overlay" class="hide jsHideTimeout" tabindex="-1"></div>
    <div id="dialog"
     class="hide"
     role="dialog"
     aria-labelledby="dialog-title"
     aria-describedby="dialog-description"
     aria-modal="true">
        <div tabindex="0" class="dialog-focus"></div>
        <h2 id="dialog-title" class="govuk-heading-l">We’re about to sign you out</h2>
        <p id="dialog-description" class="govuk-body">For your security, we’ll sign you out of this service in <span id="time">5</span> minutes.</p>
        <button class="govuk-button govuk-!-margin-bottom-1 govuk-!-margin-right-1 jsHideTimeout" aria-label="Close Navigation">
            Stay signed in
        </button>
        <a id="lastButton" class="govuk-button govuk-button--secondary govuk-!-margin-bottom-1" href="/">
            Sign out
        </a>
    </div>
`


describe('Session Dialog', () => {

    let sessionDialogElement;
    let dialog;
    let dialogOverlay;
    let dialogFocus;
    let jsHideTimeout;

    beforeEach(() => {
        jest.clearAllTimers();
        jest.useFakeTimers();

        document.body.innerHTML = html;

        delete window.location;
        window.location = {
            port: '80',
            protocol: 'https:',
            hostname: 'localhost',
            pathname: '/',
            href: ''
        };

        delete global.fetch;
        global.fetch = jest.fn();

        sessionDialogElement = new sessionDialog(document.getElementById("dialog"));

        dialog = document.getElementById('dialog');
        jsHideTimeout = document.querySelector('.jsHideTimeout');
        dialogOverlay = document.getElementById('dialog-overlay');
        dialogFocus = document.querySelector(".dialog-focus");
    });

    describe('Timeout Features', () => {
        describe('Given the timer counts down to 5 minutes', () => {
            test('it should show the dialog', async () => {

                jest.useRealTimers();

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

                window.fetch.mockResolvedValueOnce({
                    ok: true,
                    json: async () => Promise.resolve({
                        session_warning: true,
                        time_remaining: 295
                    })
                })
                await sessionDialogElement.checkSessionExpires();
                expect(window.fetch).toHaveBeenCalledTimes(1)

                expect(dialogOverlay.classList.contains('hide')).toBeFalsy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeTruthy();
                expect(dialog.classList.contains('hide')).toBeFalsy();
                expect(dialog.classList.contains('dialog')).toBeTruthy();

            });

            test('it should redirect to /session-expired', async () => {

                jest.useRealTimers();

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

                window.fetch.mockResolvedValueOnce({
                    ok: true,
                    redirected: true,
                    json: async () => Promise.resolve({
                        session_warning: true,
                        time_remaining: 0
                    })
                })
                await sessionDialogElement.checkSessionExpires();
                expect(window.fetch).toHaveBeenCalledTimes(1)

                expect(window.location.href).toBe('/session-expired');


            });
        });

        describe('Given the timer is greater than 5 minutes', () => {
            test('it should not show the dialog', async () => {

                jest.useRealTimers();

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

                window.fetch.mockResolvedValueOnce({
                    ok: true,
                    json: async () => Promise.resolve({
                        session_warning: false,
                        time_remaining: 1796
                    })
                })
                await sessionDialogElement.checkSessionExpires();
                expect(window.fetch).toHaveBeenCalledTimes(1)

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

            });
        });

        describe('Given the timer is greater than 5 minutes but says a warning', () => {
            test('it should not show the dialog', async () => {

                jest.useRealTimers();

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

                window.fetch.mockResolvedValueOnce({
                    ok: true,
                    json: async () => Promise.resolve({
                        session_warning: true,
                        time_remaining: 1796
                    })
                })
                await sessionDialogElement.checkSessionExpires();
                expect(window.fetch).toHaveBeenCalledTimes(1)

                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();

            });
        });
    });

    describe('Trap Focus', () => {

        beforeEach(async () => {
            window.fetch.mockResolvedValue({
                ok: true,
                json: async () => Promise.resolve({
                    session_warning: true,
                    time_remaining: 295
                })
            })
            await sessionDialogElement.checkSessionExpires();
        })

        describe('Given the user presses tab or esc in the dialog', () => {
            test('it should tab through active elements', async () => {
                expect(window.fetch).toHaveBeenCalledTimes(1)
                expect(document.activeElement.getAttribute('class')).toBe("dialog-focus");
                fireEvent.keyDown(dialog, { key: 'Tab', keyCode: 9 });
                expect(document.activeElement.getAttribute('class')).toBe("dialog-focus");
            });
            test('it should hide the dialog when ESC is pressed', async () => {
                expect(window.fetch).toHaveBeenCalledTimes(1)
                fireEvent.keyDown(dialog, { key: 'Esc', keyCode: 27 });
                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();
                expect(window.fetch).toHaveBeenCalledTimes(2)
            });
        })
        describe('Given the user presses any other character', () => {
            test('it should tab through active elements', async () => {
                expect(window.fetch).toHaveBeenCalledTimes(1)
                fireEvent.keyDown(dialog, { key: 'P', keyCode: 80 });
                expect(document.activeElement.getAttribute('class')).toBe("dialog-focus");
            });
        })
        describe('Given the user presses the close button', () => {
            test('it should hide the dialog box and refresh the session', async () => {
                expect(window.fetch).toHaveBeenCalledTimes(1)
                jsHideTimeout.click();
                expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
                expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
                expect(dialog.classList.contains('hide')).toBeTruthy();
                expect(dialog.classList.contains('dialog')).toBeFalsy();
                expect(window.fetch).toHaveBeenCalledTimes(2)
            });
        })
    })
});
