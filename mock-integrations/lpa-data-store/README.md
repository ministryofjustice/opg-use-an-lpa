## Adding data
1. Add new LPA json file to `src/lpas/` folder. Use last 4 digits of uid as filename.
2. Edit `lpas.mjs`
   1. Add import for new file. Import name can't have numbers, so spell them.
   2. Add imported object to `lpaData` array.
3. Rebuild javascript

## Building

```shell
# To update the OpenAPI spec file.
$ ./update.sh

# Ensure Node v20+ is installed and available.
$ node -v
v20.9.0

# Install dependencies
$ npm ci

# To rebuild the javascript
$ node build.mjs
# or
$ npm run build
```
