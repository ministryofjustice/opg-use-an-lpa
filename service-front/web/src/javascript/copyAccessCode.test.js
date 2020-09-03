// import copyAccessCode from './copyAccessCode';
// describe('given a copy access code is found', () => {
//     beforeEach(() => {
//         document.execCommand = jest.fn();
//     });
//     jest.useFakeTimers();
//     test('it should copy the code', () => {
//         document.body.innerHTML = `
//       <input readonly type="text" value="this-is-a-code-test" id="accesscodecopy" class="offscreen" aria-hidden="true">
//       <button id="copybutton" class="app-copy-button">Copy code</button>
//     `;
//         const button = document.getElementById("copybutton");
//         copyAccessCode(button);
//         expect(button.innerHTML).toBe("Copy code");
//         button.click();
//         expect(document.execCommand).toHaveBeenCalledWith('copy');
//         expect(button.innerHTML).toBe("Code copied");
//         jest.advanceTimersByTime(4000);
//         expect(button.innerHTML).toBe("Copy code");
//     });
//     test('it should not copy the code if the id is not found', () => {
//         document.body.innerHTML = `
//       <input readonly type="text" value="this-is-a-code-test" id="accesscodecopy" class="offscreen" aria-hidden="true">
//       <button class="app-copy-button">Copy code</button>
//     `;
//         const button = document.getElementById("copybutton");
//         copyAccessCode(button);
//         expect(button).toBeNull();
//     });
// });
import copyAccessCode from './copyAccessCode';
describe('given a copy access code is found', () => {
    beforeEach(() => {
        document.execCommand = jest.fn();
    });
    jest.useFakeTimers();
    test('it should copy the code', () => {
        document.body.innerHTML = `
        <div class="js-accesscodecopy">
        <input readonly type="text" value="12345" class="js-accesscodecopy-button offscreen" aria-hidden="true">
        <button class="js-accesscodecopy-button govuk-button govuk-button__copy">
            <span class="js-accesscodecopy-default">{% trans %}Copy code{% endtrans %}</span>
            <span class="js-accesscodecopy-success hidden">{% trans %}Code copied{% endtrans %}</span>
        </button>
      </div>
    `;
        const button = document.querySelector(".js-accesscodecopy-button");
        const deafultText = document.querySelector(".js-accesscodecopy-default");
        const successText = document.querySelector(".js-accesscodecopy-success");
        copyAccessCode();
        expect(deafultText.classList).toHaveLength(1);
        expect(deafultText.classList).not.toContain('hidden');
        expect(successText.classList).toHaveLength(2);
        expect(successText.classList).toContain('hidden');
        button.click();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(deafultText.classList).toHaveLength(2);
        expect(deafultText.classList).toContain('hidden');
        expect(successText.classList).toHaveLength(1);
        expect(successText.classList.not).toContain('hidden');
        jest.advanceTimersByTime(4000);
        expect(deafultText.classList).toHaveLength(1);
        expect(deafultText.classList).not.toContain('hidden');
        expect(successText.classList).toHaveLength(2);
        expect(successText.classList).toContain('hidden');
    });
    test('it should not copy the code if the id is not found', () => {
        document.body.innerHTML = `
        <div class="">
        <input readonly type="text" value="{{ add_hyphen_to_viewer_code(code) }}" class="js-accesscodecopy-button offscreen" aria-hidden="true">
        <button class="js-accesscodecopy-button govuk-button govuk-button__copy">
            <span class="js-accesscodecopy-default">{% trans %}Copy code{% endtrans %}</span>
            <span class="js-accesscodecopy-success hidden">{% trans %}Code copied{% endtrans %}</span>
        </button>
      </div>
    `;
        copyAccessCode();
        expect(button).toBeNull();
    });
});
