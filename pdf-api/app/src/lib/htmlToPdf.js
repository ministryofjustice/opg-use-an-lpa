const puppeteer = require("puppeteer");

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

  const page = await browser.newPage();
  await page.emulateMedia("screen");
  page.addStyleTag({ path: "./src/templates/main.css" });
  await page.setContent(html, { waitUntil: "networkidle" });
  const pdf = await page.pdf({
    printBackground: true,
    width: 1100,
    height: 2000
  });
  await browser.close();

  return pdf;
};

export default htmlToPdf;
