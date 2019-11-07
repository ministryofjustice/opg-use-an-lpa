const pa11y = require('pa11y');
const cli = require('pa11y-reporter-cli');

(async function () {

    const config = {
        hideElements: 'svg', //svg fallback images currently cause issues due to fallback images beneath a role=presentation
    };    

    const full_results = await Promise.all([
        //actor side local homepage
        pa11y('http://localhost:9001', config),
        //view side local homepage
        pa11y('http://localhost:9002', config)
    ]);

    full_results.map(function(result){
        const cliResults = cli.results(result);
        console.log(cliResults);
    });

})();