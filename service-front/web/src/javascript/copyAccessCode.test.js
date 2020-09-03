import copyAccessCode from './copyAccessCode';
describe('given a copy access code is found', () => {
    beforeEach(() => {
        document.execCommand = jest.fn();
    });
    jest.useFakeTimers();
    test('it should copy the code', () => {
        document.body.innerHTML = `
      <input readonly type="text" value="this-is-a-code-test" id="accesscodecopy" class="offscreen" aria-hidden="true">
      <button id="copybutton" class="app-copy-button">Copy code</button>
    `;
        const button = document.getElementById("copybutton");
        copyAccessCode(button);
        expect(button.innerHTML).toBe("Copy code");
        button.click();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(button.innerHTML).toBe("Code copied");
        jest.advanceTimersByTime(4000);
        expect(button.innerHTML).toBe("Copy code");
    });
    test('it should not copy the code if the id is not found', () => {
        document.body.innerHTML = `
      <input readonly type="text" value="this-is-a-code-test" id="accesscodecopy" class="offscreen" aria-hidden="true">
      <button class="app-copy-button">Copy code</button>
    `;
        const button = document.getElementById("copybutton");
        copyAccessCode(button);
        expect(button).toBeNull();
    });
});

