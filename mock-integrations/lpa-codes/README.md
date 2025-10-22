## Adding/editing data
1. Add new codes (or edit existing) in the `src/codes/seeding-data.json` file
2. Rebuild javascript
3. Restart _mock-lpa-codes_
4. If you wish these changes to persist for everyone ensure you commit the changed `mock-responses.js` file

## Building

```shell
# To update the OpenAPI spec file from https://github.com/ministryofjustice/opg-data-lpa-codes
$ ./update.sh

# Ensure Node v20+ is installed and available.
$ node -v
v20.9.0

# Install dependencies
$ npm ci --ignore-scripts

# To rebuild the javascript
$ node build.mjs
# or
$ npm run build
```
