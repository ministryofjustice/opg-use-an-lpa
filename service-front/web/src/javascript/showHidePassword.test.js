import showHidePassword from './showHidePassword';
describe('given a password and confirm password input is defined', () => {
    test('it hides the confirm password box and changes the password type to text', () => {
        document.body.innerHTML = `
            <div class="govuk-form-group">

                <label class="govuk-label" for="show_hide_password">
                    Create a password
                </label>

                <span id="show_hide_password-hint" class="govuk-hint">
                    Your password needs to have:
                    <ul class="govuk-list--bullet">
                        <li>at least 8 characters</li>
                        <li>at least one lower-case and one capital letter</li>
                        <li>at least one number</li>
                    </ul>
                </span>

                <div class="moj-password-reveal">
                    <input class="govuk-input govuk-input moj-password-reveal__input govuk-input--width-20" id="show_hide_password" name="show_hide_password" type="password" value="">

                    <button class="govuk-button govuk-button--secondary moj-password-reveal__button" data-module="govuk-button" type="button" data-showpassword="Show" data-hidepassword="Hide">Show</button>
                </div>
            </div>
    `;
        const button = document.querySelector(".moj-password-reveal__button");
        const passwordInput = document.querySelector(".moj-password-reveal__input");

        showHidePassword();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show');
        button.click();
        expect(passwordInput.getAttribute('type')).toBe('text');
        expect(button.innerText).toBe('Hide');
        button.click();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show');
    });
});
