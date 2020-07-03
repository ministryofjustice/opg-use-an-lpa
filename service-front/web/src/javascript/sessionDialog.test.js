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
        <a class="govuk-button govuk-button--secondary govuk-!-margin-bottom-1" href="/">
            Sign out
        </a>
    </div>
    <div>
        <button id="show-timeout" class="jsShowTimeout">Show Timeout</button>
        <button id="hide-timeout" class="jsHideTimeout">Hide Timeout</button>
    </div>
`

describe('Session Dialog', () => {
    jest.useFakeTimers();
    let sessionDialogElement;
    let dialog;
    let showButton;
    let hideButton;
    let dialogOverlay;
    let dialogFocus;

    beforeEach(() => {
        jest.clearAllTimers();
        document.body.innerHTML = html;
        sessionDialogElement = new sessionDialog(document.getElementById("dialog"), 20);

        dialog = document.getElementById('dialog');
        showButton = document.getElementById('show-timeout');
        hideButton = document.getElementById('hide-timeout');
        dialogOverlay = document.getElementById('dialog-overlay');
        dialogFocus = document.querySelector(".dialog-focus");
    });

    describe('Given I use buttons to show and hide the dialog box', () => {
        test('it should show the dialog', () => {
            showButton.click();
            expect(dialogOverlay.classList.contains('hide')).toBeFalsy();
            expect(dialogOverlay.classList.contains('dialog-overlay')).toBeTruthy();
            expect(dialog.classList.contains('hide')).toBeFalsy();
            expect(dialog.classList.contains('dialog')).toBeTruthy();
        });
        test('it should hide the dialog', () => {
            showButton.click();
            hideButton.click();
            expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
            expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
            expect(dialog.classList.contains('hide')).toBeTruthy();
            expect(dialog.classList.contains('dialog')).toBeFalsy();
        });
    });
    /*describe('Given the timer counts down to 5 minutes', () => {
        test('it should show the dialog', () => {
            jest.advanceTimersByTime(12000);
            expect(dialogOverlay.classList.contains('hide')).toBeFalsy();
            expect(dialogOverlay.classList.contains('dialog-overlay')).toBeTruthy();
            expect(dialog.classList.contains('hide')).toBeFalsy();
            expect(dialog.classList.contains('dialog')).toBeTruthy();
        });
    });*/
    describe('Given the user presses tab or esc in the dialog', () => {
        test('it should tab through active elements', () => {
            showButton.click();
            expect(document.activeElement.getAttribute('class')).toBe("dialog-focus");
            fireEvent.keyDown(dialog, { key: 'Tab', keyCode: 9 });
            //expect(document.activeElement.innerHTML).toBe("Stay signed in");
            expect(document.activeElement.getAttribute('class')).toBe("dialog-focus");
        });
        test('it should hide the dialog when ESC is pressed', () => {
            showButton.click();
            fireEvent.keyDown(dialog, { key: 'Esc', keyCode: 27 });
            expect(dialogOverlay.classList.contains('hide')).toBeTruthy();
            expect(dialogOverlay.classList.contains('dialog-overlay')).toBeFalsy();
            expect(dialog.classList.contains('hide')).toBeTruthy();
            expect(dialog.classList.contains('dialog')).toBeFalsy();
        });
    })
});
