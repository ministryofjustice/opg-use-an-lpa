const fs = require("fs");
import htmlToPdf from "./htmlToPdf";
import htmlParser from "./htmlParser";
import { PDFDocument } from "pdf-lib";

const generatePdf = async (templateId, data) => {
  const html = fs.readFileSync(`./src/templates/${templateId}.html`, "utf8");
  const parsedHtml = await htmlParser(html, data);
  const pdf = await htmlToPdf(parsedHtml);

  const pdfDoc = await PDFDocument.load(pdf.buffer);

  pdfDoc.setTitle("View LPA - View a lasting power of attorney");
  const pdfBytes = await pdfDoc.save();
  return pdfBytes;
};

export default generatePdf;
