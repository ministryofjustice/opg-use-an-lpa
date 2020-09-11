import showHidePassword from './showHidePassword';
describe('given a password and confirm password input is defined', () => {
    test('it hides the confirm password box and changes the password type to text', () => {
        document.body.innerHTML = `
            <div class="js-showhidepassword">
                <div class="govuk-form-group">
                    <label class="govuk-label" for="password">
                        Create a password
                    </label>
                    <input class="govuk-input js-showhidepassword-input" id="password" name="password" type="password" value="">
            </div>

            <button class="govuk-button govuk-button--secondary js-showhidepassword-button" data-module="govuk-button" type="button" data-showpassword="Show password" data-hidepassword="Hide password">Show password</button>

            <div class="govuk-form-group" id="confirm-password">
                <label class="govuk-label" for="password_confirm">
                    Confirm your password
                </label>
                <input class="govuk-input js-showhidepassword-confirm" id="password_confirm" name="password_confirm" type="password" value="">
            </div>
            <input id="skip_password_confirm" name="skip_password_confirm" type="hidden" value="false">
    `;
        const button = document.querySelector(".js-showhidepassword-button");
        const confirmPassword = document.querySelector("#confirm-password");
        const passwordInput = document.querySelector(".js-showhidepassword-input");
        const skipConfirmPassword = document.querySelector('#skip_password_confirm');

        showHidePassword();
        expect(confirmPassword.hidden).toBeFalsy();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show password');
        expect(skipConfirmPassword.value).toBe("false");
        button.click();
        expect(confirmPassword.hidden).toBeTruthy();
        expect(passwordInput.getAttribute('type')).toBe('text');
        expect(button.innerText).toBe('Hide password');
        expect(skipConfirmPassword.value).toBe("true");
        button.click();
        expect(confirmPassword.hidden).toBeFalsy();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show password');
        expect(skipConfirmPassword.value).toBe("false");
    });
});

describe('given only a password input is defined', () => {
    test('it shows and hides the password as text', () => {
        document.body.innerHTML = `
            <div class="js-showhidepassword">
                <div class="govuk-form-group">

                    <label class="govuk-label" for="password">
                        Create a password
                    </label>

                    <input class="govuk-input js-showhidepassword-input" id="password" name="password" type="password" value="">
            </div>

            <button class="govuk-button govuk-button--secondary js-showhidepassword-button" data-module="govuk-button" type="button" data-showpassword="Show password" data-hidepassword="Hide password">Show password</button>

        </div>
    `;
        const button = document.querySelector(".js-showhidepassword-button");
        const passwordInput = document.querySelector(".js-showhidepassword-input");

        showHidePassword();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show password');
        button.click();
        expect(passwordInput.getAttribute('type')).toBe('text');
        expect(button.innerText).toBe('Hide password');
        button.click();
        expect(passwordInput.getAttribute('type')).toBe('password');
        expect(button.innerText).toBe('Show password');
    });
});
