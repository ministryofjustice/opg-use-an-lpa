import stripAnchorTags from "./stripAnchorTags";

describe("Given you want to strip the href attribute from anchor tags in HTML", () => {
  test("It should generate HTML with no links should be exactly the same", async () => {
    const testHtml = `<html><head></head><body><p>Test with no links</p></body></html>`;
    const html = await stripAnchorTags(testHtml);

    expect(html).toBe(testHtml);
  });

  test("It should generate HTML with links but no href should be exactly the same", async () => {
    const testHtml = `<html><head></head><body><p><a class="govuk-link">Test with no links</a></p></body></html>`;
    const html = await stripAnchorTags(testHtml);

    expect(html).toBe(testHtml);
  });

  test("It should generate HTML with links but and a href should return without a href attribute", async () => {
    const testHtml = `<html><head></head><body><p><a href="/home" class="govuk-link">Test with no links</a></p></body></html>`;
    const outputHtml = `<html><head></head><body><p><a class="govuk-link">Test with no links</a></p></body></html>`;
    const html = await stripAnchorTags(testHtml);

    expect(html).toBe(outputHtml);
  });
});
