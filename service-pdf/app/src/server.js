/* istanbul ignore file */

const app = require("./app");

const port = 80;
const server = app.listen(port, err => {
  if (err) throw err;
  console.log(`> Running on localhost:${port}`);
});

export default server;
