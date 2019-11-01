const express = require("express");
const app = express();
const router = express.Router();
import GeneratePdf from "./lib/generatePdf";
import TemplateList from "./lib/templateList";

const port = 8080;
const templateList = TemplateList();

router.post("/:templateid", async (req, res) => {
  const templateId = req.params.templateid;
  if (templateList.indexOf(templateId) > -1) {
    const result = await GeneratePdf(templateId, req.body);
    res.writeHead(200, {
      "Content-Type": "application/pdf",
      "Content-Disposition": `attachment; filename=${templateId}.pdf`,
      "Content-Length": result.length
    });

    res.end(Buffer.from(result, "binary"));
  } else {
    res.status(404).send("Not found");
  }
});

app.use(express.json());
app.use("/", router);

app.listen(port, function() {});
