# Service Front Web

The aim of this project is to create a shared resource of CSS and JS to be used in both the Actor and Viewer applications.

The service automatically runs on docker-compose up. However you can run it independently using the following commands

- `npm run build` (This runs a one off production build of all assets)
- `npm run build` (This runs webpack in watch mode for development meaning any changes will automatically get recompiled)

# Testing

The project uses Jest to test the javascript components. Only `index.js` is ignored due to its implementation not needing testing.

Jest also provides us with code coverage reports. There are currently no thresholds setup to fail tests but this could be done easily in the future.

Coverage is fed into codecov for easy reporting via Github and also hooked into CircleCI which will fail the build if any tests fail.

You can run the test by running the following

`npm run test` - This will run tests and generate a coverage report
`npm run test:watch` - This will run tests without coverage but in a watch state so you can get instant feedback when developing.

## SCSS

You should never write inline styles in your templates. Any styles that aren't provided by the exisitng design systems should go in their own SCSS file in the `scss` directory.

Always try and think about them in a component based way and keep them simple and in line.

If possible, always try and raise a ticket in the appropriate design system repo to integrate a new feature so others can reuse your work.

## Webpack

Webpack is responsible for the following things

- Copy assets from the GDS and MoJ GDS npm packages. This includes images and a robots.txt
- Compile all SCSS files from the npm packages and custom SCSS files
- Compile all JS from the npm packages and use babel to transpile them to code the browser can use

The repo uses 3 different webpack files.

### webpack.common.js

This file contains common actions that are taken across the production and development builds and does the following.

- Sets the entry point to the index.js file in src
- Looks for any SCSS files
- Converts the SCSS into CSS
- Writes it out to main.css
- Looks for any JS files
- Compiles all the JS to a single js file called javascript/bundle.js
- It also uses babel to transpile any ES6 syntax to code the browser can read

### webpack.development.js

This file sets up the location of where the files should be output to once process and sets some watch options for when running in watch mode in development.

### webpack.production.js

Like above, this too sets the directory to export too but it has to resolve the path correctly due to how it is run in production.

## Automatic CSS and Font Generation

There is a webpack configuration for the pdf service that on build will do the following.

- Parse the SCSS in the project
- Pull in all fonts
- base64 encode the fonts
- Add the fonts to the parsed CSS
- Save the output to a file called `pdf.css`

This means whenever styles change on the site, `pdf.css` will be updated in a compatible way to be embedded in a HTML document to be passed to this service.
