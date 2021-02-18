const fs = require("fs");
import htmlToPdf from "./htmlToPdf";
import stripAnchorTags from "./stripAnchorTags";
import { PDFDocument } from "pdf-lib";

const generatePdf = async (html, options) => {
  if (options.stripTags) {
    html = await stripAnchorTags(html);
  }
  const pdf = await htmlToPdf(html, { waitUntil: "load" });

  const pdfDoc = await PDFDocument.load(pdf);

  pdfDoc.setTitle("View LPA - View a lasting power of attorney");
  const pdfBytes = await pdfDoc.save();
  return pdfBytes;
};

export default generatePdf;
