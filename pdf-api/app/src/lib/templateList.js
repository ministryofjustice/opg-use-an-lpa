const fs = require("fs");
const path = require("path");
const directoryPath = path.resolve(path.join(__dirname, "../templates"));

const templateList = () => {
  const fileList = fs
    .readdirSync(directoryPath)
    .map(fileName => {
      const fileStats = fs.lstatSync(path.join(directoryPath, fileName));
      const parsedFile = path.parse(fileName);
      if (fileStats.isFile() && parsedFile.ext === ".html") {
        return parsedFile.name;
      }
    })
    .filter(fileName => {
      return fileName !== undefined;
    });

  return fileList;
};

export default templateList;
