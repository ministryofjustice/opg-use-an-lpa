import htmlToPdf from "./htmlToPdf";

const testHtml = `<html><head></head><body><p><a href="/home" class="govuk-link">Test with no links</a></p></body></html>`;

test("it should generate a PDF using puppeteer successfully", async () => {
  const pdf = await htmlToPdf(testHtml);

  expect(pdf).not.toBeNull();
  expect(pdf.length).toBe(24640);
  expect(pdf).toBeInstanceOf(Uint8Array);
});
