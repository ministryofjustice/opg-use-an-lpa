/* istanbul ignore file */

import app from "./app";

const port = 8080;
const server = app.listen(port, err => {
  if (err) throw err;
  console.log(`> Running on localhost:${port}`);
});

export default server;
