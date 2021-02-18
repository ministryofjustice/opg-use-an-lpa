const puppeteer = require("puppeteer");
const htmlToPdf = async (html, options) => {
  const browser = await puppeteer.launch({
    executablePath: process.env.CHROMIUM_PATH,
    args: [
      // Required for Docker version of Puppeteer
      "--no-sandbox",
      "--disable-setuid-sandbox",
      // This will write shared memory files into /tmp instead of /dev/shm,
      // because Docker’s default for /dev/shm is 64MB
      "--disable-dev-shm-usage"
    ]
  });

  let pdf;

  try {
    const page = await browser.newPage();
    await page.emulateMediaType("screen");

    await page.setContent(html, options);
    pdf = await page.pdf({
      printBackground: true,
      width: 1100,
      height: 2000
    });
    await browser.close();
  } finally {
    await browser.close();
  }
  return pdf ? pdf.buffer : null;
};

export default htmlToPdf;
