import disableButtonOnClick from './disableButtonOnClick';

describe('disableButtonOnClick', () => {
  // jsdom doesn't implement form submission so this will keep it from showing errors in the console
  window.HTMLFormElement.prototype.submit = () => {};

  describe('without the attribute', () => {
    test('clicking button will not disable it', () => {
      document.body.innerHTML = `
      <form name="test" onsubmit="return false">
        <button type="submit">Click</button>
      </form>
    `;

      disableButtonOnClick(document.getElementsByTagName('form'));

      const button = document.querySelector('button');
      expect(button.disabled).toBe(false);

      button.click();
      expect(button.disabled).toBe(false);
    });
  });

  describe('with the attribute', () => {
    test('clicking button will disable it', () => {
      document.body.innerHTML = `
      <form name="test" onsubmit="return false">
        <button type="submit" data-prevent-double-click="true">Click</button>
      </form>
    `;

      disableButtonOnClick(document.getElementsByTagName('form'));

      const button = document.querySelector('button');
      expect(button.disabled).toBe(false);

      button.click();
      expect(button.disabled).toBe(true);
    });
  });

  describe('with button reenable attribute', () => {
    test('clicking the download button should disable it and reenable after 5s', () => {

      jest.useFakeTimers();

      document.body.innerHTML = `
      <form name="test" onsubmit="return false">
        <button type="submit" data-prevent-double-click="true" data-reenable-after="5000">Click</button>
      </form>
    `;

      disableButtonOnClick(document.getElementsByTagName('form'));
      const button = document.querySelector('button');
      button.click();

      expect(button.disabled).toBe(true);

      jest.advanceTimersByTime(5000);

      expect(button.disabled).toBe(false);

      jest.useRealTimers();

    });
  });
});
