import copyAccessCode from './copyAccessCode';
describe('given a copy access code is found', () => {
    beforeEach(() => {
        document.execCommand = jest.fn();
    });
    jest.useFakeTimers();
    test('it should copy the code', () => {
        document.body.innerHTML = `
        <div class="js-accesscodecopy">
        <input readonly type="text" value="12345" class="js-accesscodecopy-value offscreen" aria-hidden="true">
        <button class="js-accesscodecopy-button govuk-button govuk-button__copy">
            <span class="js-accesscodecopy-default">{% trans %}Copy code{% endtrans %}</span>
            <span class="js-accesscodecopy-success hide">{% trans %}Code copied{% endtrans %}</span>
        </button>
      </div>
    `;
        const button = document.querySelector(".js-accesscodecopy-button");
        const deafultText = document.querySelector(".js-accesscodecopy-default");
        const successText = document.querySelector(".js-accesscodecopy-success");
        copyAccessCode();
        expect(deafultText.classList).toHaveLength(1);
        expect(deafultText.classList).not.toContain('hide');
        expect(successText.classList).toHaveLength(2);
        expect(successText.classList).toContain('hide');
        button.click();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(deafultText.classList).toHaveLength(2);
        expect(deafultText.classList).toContain('hide');
        expect(successText.classList).toHaveLength(1);
        expect(successText.classList).not.toContain('hide');
        jest.advanceTimersByTime(4000);
        expect(deafultText.classList).toHaveLength(1);
        expect(deafultText.classList).not.toContain('hide');
        expect(successText.classList).toHaveLength(2);
        expect(successText.classList).toContain('hide');
    });
});

