import stripAnchorTags from "./stripAnchorTags";

test("HTML with no links should be exactly the same", async () => {
  const testHtml = `<html><head></head><body><p>Test with no links</p></body></html>`;
  const html = await stripAnchorTags(testHtml);

  expect(html).toBe(testHtml);
});

test("HTML with links but no href should be exactly the same", async () => {
  const testHtml = `<html><head></head><body><p><a class="govuk-link">Test with no links</a></p></body></html>`;
  const html = await stripAnchorTags(testHtml);

  expect(html).toBe(testHtml);
});

test("HTML with links but and a href should return without a href attribute", async () => {
  const testHtml = `<html><head></head><body><p><a href="/home" class="govuk-link">Test with no links</a></p></body></html>`;
  const outputHtml = `<html><head></head><body><p><a class="govuk-link">Test with no links</a></p></body></html>`;
  const html = await stripAnchorTags(testHtml);

  expect(html).toBe(outputHtml);
});
