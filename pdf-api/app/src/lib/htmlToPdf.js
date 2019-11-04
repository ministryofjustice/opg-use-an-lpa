const puppeteer = require("puppeteer");
//TODO:  Error handling needed here?
const htmlToPdf = async html => {
  const browser = await puppeteer.launch({
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
    await page.emulateMedia("screen");

    await page.setContent(html, { waitUntil: "load" });
    pdf = await page.pdf({
      printBackground: true,
      width: 1100,
      height: 2000
    });
    await browser.close();
  } catch (error) {
    await browser.close();
  } finally {
    return pdf;
  }
};

export default htmlToPdf;
