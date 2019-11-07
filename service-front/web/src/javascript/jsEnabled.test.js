import jsEnabled from './jsEnabled';

describe('Given you want to add a js-enabled class to the document.body', () => {
  test('It should exist in the body classList', () => {
    jsEnabled(document.body);

    expect(document.body.classList).toHaveLength(2);
    expect(document.body.classList).toContain('js-enabled');
  });
});
