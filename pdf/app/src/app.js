const bodyParser = require("body-parser");
const polka = require("polka");
import GeneratePdf from "./lib/generatePdf";

const port = 8080;

function stripAnchorTagsFromHtml(headers) {
  return headers["strip-anchor-tags"] !== undefined;
}

polka()
  .use(bodyParser.text({ type: "text/html", limit: "2000kb" }))
  .post("/generate-pdf", async (req, res) => {
    const result = await GeneratePdf(req.body, {
      stripTags: stripAnchorTagsFromHtml(req.headers)
    });

    res.writeHead(200, {
      "Content-Type": "application/pdf",
      "Content-Disposition": `attachment; filename=download.pdf`,
      "Content-Length": result.length
    });

    res.end(Buffer.from(result, "binary"));
  })
  .listen(port, err => {
    if (err) throw err;
    console.log(`> Running on localhost:${port}`);
  });
