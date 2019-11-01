const express = require("express");
const app = express();
const router = express.Router();
import GeneratePdf from "./lib/generatePdf";

const port = 8080;

router.use(function(req, res, next) {
  next();
});

router.post("/:templateid", async (req, res) => {
  const result = await GeneratePdf(req.params.templateid, req.body);
  // TODO: Catch 404 or invalid data
  res.writeHead(200, {
    "Content-Type": "application/pdf",
    "Content-Disposition": `attachment; filename=${req.params.templateid}.pdf`,
    "Content-Length": result.length
  });

  res.end(Buffer.from(result, "binary"));
});

app.use(express.json());
app.use("/", router);

app.listen(port, function() {});
